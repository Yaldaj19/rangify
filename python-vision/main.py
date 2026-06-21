"""
Rangify Vision Service
======================
FastAPI microservice for image segmentation used by Laravel `SmartSelectController`.

Endpoints
---------
GET  /health           — liveness probe
POST /grabcut          — click-to-select (best for wall/floor regions)
POST /watershed        — markers-based segmentation
POST /flood-smart      — edge-aware flood fill (Canny + cv.floodFill)
POST /slic-superpixels — return SLIC superpixel labels for a click
POST /segment-all      — auto-segment all flat regions (walls + ceiling + floor)
POST /precompute       — single-call: returns a label-map PNG for instant hover-preview
                          (each pixel value = region id; client looks up id on mousemove)

All endpoints accept JSON body with `image` as data-URL or raw base64,
and return `{ mask: data:image/png;base64,..., width, height, method, elapsed_ms }`
where mask is a single-channel PNG (255 = inside selection, 0 = outside).
"""
from __future__ import annotations

import base64
import io
import re
import time
from typing import List, Optional

import cv2
import numpy as np
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from PIL import Image
from pydantic import BaseModel, Field
from skimage.segmentation import slic, felzenszwalb

app = FastAPI(
    title="Rangify Vision Service",
    description="OpenCV + scikit-image segmentation for wall recolor",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


# ---------- Pydantic schemas ----------
class Point(BaseModel):
    x: float = Field(..., description="Normalized 0..1 OR pixel coord; auto-detected")
    y: float


class GrabCutReq(BaseModel):
    image: str
    points: List[List[float]] = Field(..., min_length=1, max_length=16)
    labels: Optional[List[int]] = None
    iterations: int = 5
    rect_padding: float = 0.18  # padding around clicked points to seed bbox


class WatershedReq(BaseModel):
    image: str
    points: List[List[float]]
    bg_points: Optional[List[List[float]]] = None


class FloodReq(BaseModel):
    image: str
    points: List[List[float]]
    tolerance: int = 18  # 0..255, color distance


class SlicReq(BaseModel):
    image: str
    points: List[List[float]]
    n_segments: int = 600
    compactness: float = 12.0


class SegmentAllReq(BaseModel):
    image: str
    scale: int = 250          # felzenszwalb scale
    min_size: int = 1200      # min region size in px
    merge_similar: bool = True


class PrecomputeReq(BaseModel):
    image: str
    scale: int = 280
    min_size: int = 900
    max_dim: int = 1280       # downscale for speed; client maps coords back
    merge_lab_threshold: float = 7.0


class SemanticReq(BaseModel):
    image: str
    refine: bool = True       # edge-snap each mask to image edges


class SemanticPointReq(BaseModel):
    image: str
    points: List[List[float]]
    refine: bool = True


class SamPointReq(BaseModel):
    image: str
    points: List[List[float]] = Field(..., min_length=1, max_length=16)
    labels: Optional[List[int]] = None
    refine: bool = True


# ---------- helpers ----------
DATA_URL_RE = re.compile(r"^data:image/\w+;base64,")


def decode_image(image_str: str) -> np.ndarray:
    """Decode data-URL or raw base64 to BGR ndarray."""
    raw = DATA_URL_RE.sub("", image_str)
    try:
        binary = base64.b64decode(raw, validate=False)
    except Exception as e:
        raise HTTPException(400, f"invalid base64: {e}")
    pil = Image.open(io.BytesIO(binary)).convert("RGB")
    rgb = np.array(pil)
    return cv2.cvtColor(rgb, cv2.COLOR_RGB2BGR)


def encode_mask_png(mask: np.ndarray) -> str:
    """Encode binary mask (0/255 uint8) as data-URL PNG."""
    if mask.dtype != np.uint8:
        mask = mask.astype(np.uint8)
    if mask.max() == 1:
        mask = mask * 255
    ok, buf = cv2.imencode(".png", mask)
    if not ok:
        raise HTTPException(500, "failed to encode mask")
    b64 = base64.b64encode(buf.tobytes()).decode("ascii")
    return f"data:image/png;base64,{b64}"


def to_pixel_points(points: List[List[float]], w: int, h: int) -> List[tuple[int, int]]:
    """Auto-detect normalized vs pixel coords and return pixel tuples."""
    if not points:
        return []
    pixel = []
    for p in points:
        x, y = float(p[0]), float(p[1])
        if 0.0 <= x <= 1.0 and 0.0 <= y <= 1.0:
            pixel.append((int(round(x * w)), int(round(y * h))))
        else:
            pixel.append((int(round(x)), int(round(y))))
    return pixel


def cleanup_mask(mask: np.ndarray, kernel_size: int = 5) -> np.ndarray:
    """Morphological closing + remove small holes."""
    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (kernel_size, kernel_size))
    closed = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel, iterations=2)
    opened = cv2.morphologyEx(closed, cv2.MORPH_OPEN, kernel, iterations=1)
    return opened


def refine_mask_edges(mask: np.ndarray, img_bgr: np.ndarray,
                      edge_window: int = 8, bilateral: bool = True) -> np.ndarray:
    """
    Two-stage edge refinement:
      1. Bilateral filter on the mask itself — smooths pixel-level zig-zags
         while still respecting binary structure.
      2. Edge-snap: for every mask boundary pixel, look in a small window
         for the strongest Canny edge in the underlying image; pull the
         mask boundary toward that edge.
    Returns a binary mask (0 / 255).
    """
    if mask.dtype != np.uint8:
        mask = mask.astype(np.uint8)
    if mask.max() == 1:
        mask = mask * 255

    if bilateral:
        # Bilateral on the (binary) mask smooths jaggies without bleeding
        smoothed = cv2.bilateralFilter(mask, d=7, sigmaColor=80, sigmaSpace=80)
        mask = np.where(smoothed > 127, 255, 0).astype(np.uint8)

    # Edge snap: find Canny edges in the source image, pull mask boundary toward them
    gray = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 60, 150)

    # Boundary band of the mask
    kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3, 3))
    dilated = cv2.dilate(mask, kernel, iterations=edge_window)
    eroded = cv2.erode(mask, kernel, iterations=edge_window)
    boundary_band = cv2.bitwise_xor(dilated, eroded)

    # Inside the boundary band, prefer to follow image edges:
    # if a pixel is on a strong edge AND inside the band → flip to mask side
    # by comparing distance to original mask vs to edge.
    # Simpler heuristic: within the band, snap to whichever side has more
    # nearby mask pixels (majority filter weighted by edge proximity).
    edge_in_band = cv2.bitwise_and(edges, boundary_band)
    if edge_in_band.sum() == 0:
        return mask

    # Build a snapped mask: start from eroded (safe inside), then expand
    # outward until we hit Canny edges OR the dilated boundary.
    # Use distance transform from the edge map.
    inv_edges = cv2.bitwise_not(edge_in_band)
    dist_to_edge = cv2.distanceTransform(inv_edges, cv2.DIST_L2, 5)

    # For each pixel in the boundary band, keep it ON if it's closer
    # to the original mask's interior than to nearest edge.
    interior = (mask == 255).astype(np.uint8) * 255
    dist_to_interior = cv2.distanceTransform(cv2.bitwise_not(interior), cv2.DIST_L2, 5)

    snapped = np.where(
        boundary_band == 255,
        np.where(dist_to_interior < dist_to_edge, 255, 0),
        mask
    ).astype(np.uint8)

    # Final small cleanup
    snapped = cv2.morphologyEx(snapped, cv2.MORPH_CLOSE, kernel, iterations=1)
    return snapped


def largest_connected(mask: np.ndarray, seed_xy: tuple[int, int]) -> np.ndarray:
    """Keep only the connected component containing seed_xy."""
    num, labels, stats, _ = cv2.connectedComponentsWithStats((mask > 0).astype(np.uint8), 8)
    if num <= 1:
        return mask
    sx, sy = seed_xy
    sx = max(0, min(labels.shape[1] - 1, sx))
    sy = max(0, min(labels.shape[0] - 1, sy))
    target = labels[sy, sx]
    if target == 0:  # seed fell on background; keep largest non-bg component
        sizes = stats[1:, cv2.CC_STAT_AREA]
        target = 1 + int(np.argmax(sizes))
    out = np.zeros_like(mask, dtype=np.uint8)
    out[labels == target] = 255
    return out


# ---------- endpoints ----------
@app.get("/health")
def health():
    return {
        "status": "ok",
        "opencv": cv2.__version__,
        "numpy": np.__version__,
    }


@app.post("/grabcut")
def grabcut(req: GrabCutReq):
    """
    Click-to-select via OpenCV GrabCut.
    Strategy:
      - Build a rect around the foreground click(s) with padding
      - Seed FG = small disk under each fg click; BG = same for bg clicks (label=0)
      - Run GrabCut with INIT_WITH_MASK if any seeds, else INIT_WITH_RECT
    """
    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    pts = to_pixel_points(req.points, w, h)
    labels = req.labels or [1] * len(pts)

    fg_pts = [p for p, l in zip(pts, labels) if l == 1]
    bg_pts = [p for p, l in zip(pts, labels) if l == 0]
    if not fg_pts:
        raise HTTPException(400, "at least one foreground point required")

    # bounding rect of FG points + padding
    xs = [p[0] for p in fg_pts]
    ys = [p[1] for p in fg_pts]
    pad_w = int(w * req.rect_padding)
    pad_h = int(h * req.rect_padding)
    x0 = max(0, min(xs) - pad_w)
    y0 = max(0, min(ys) - pad_h)
    x1 = min(w - 1, max(xs) + pad_w)
    y1 = min(h - 1, max(ys) + pad_h)
    rect = (x0, y0, max(1, x1 - x0), max(1, y1 - y0))

    mask = np.full((h, w), cv2.GC_PR_BGD, np.uint8)
    mask[y0:y1, x0:x1] = cv2.GC_PR_FGD

    r_disk = max(4, int(min(w, h) * 0.012))
    for (px, py) in fg_pts:
        cv2.circle(mask, (px, py), r_disk, cv2.GC_FGD, -1)
    for (px, py) in bg_pts:
        cv2.circle(mask, (px, py), r_disk, cv2.GC_BGD, -1)

    bgd_model = np.zeros((1, 65), np.float64)
    fgd_model = np.zeros((1, 65), np.float64)

    try:
        cv2.grabCut(img, mask, rect, bgd_model, fgd_model, req.iterations, cv2.GC_INIT_WITH_MASK)
    except cv2.error as e:
        raise HTTPException(500, f"grabcut failed: {e}")

    binary = np.where((mask == cv2.GC_FGD) | (mask == cv2.GC_PR_FGD), 255, 0).astype(np.uint8)
    binary = cleanup_mask(binary)
    binary = largest_connected(binary, fg_pts[0])
    binary = refine_mask_edges(binary, img)  # bilateral + edge-snap

    return {
        "mask": encode_mask_png(binary),
        "width": w,
        "height": h,
        "method": "grabcut+refined",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/watershed")
def watershed(req: WatershedReq):
    """Watershed with user-provided FG (sure foreground) and optional BG markers."""
    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    fg_pts = to_pixel_points(req.points, w, h)
    bg_pts = to_pixel_points(req.bg_points or [], w, h)

    markers = np.zeros((h, w), np.int32)
    r = max(4, int(min(w, h) * 0.015))
    for i, (px, py) in enumerate(fg_pts, start=2):
        cv2.circle(markers, (px, py), r, i, -1)
    if not bg_pts:
        # auto-bg: a few pixels at image corners
        bg_pts = [(2, 2), (w - 3, 2), (2, h - 3), (w - 3, h - 3)]
    for (px, py) in bg_pts:
        cv2.circle(markers, (px, py), r, 1, -1)

    ws = cv2.watershed(img, markers.copy())
    seed_label = markers[fg_pts[0][1], fg_pts[0][0]]
    binary = np.where(ws == seed_label, 255, 0).astype(np.uint8)
    binary = cleanup_mask(binary)
    binary = largest_connected(binary, fg_pts[0])
    binary = refine_mask_edges(binary, img)

    return {
        "mask": encode_mask_png(binary),
        "width": w,
        "height": h,
        "method": "watershed+refined",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/flood-smart")
def flood_smart(req: FloodReq):
    """Edge-aware flood fill: Canny edges block the flood, so it stops at object borders."""
    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    pts = to_pixel_points(req.points, w, h)

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 50, 140)
    edges = cv2.dilate(edges, np.ones((2, 2), np.uint8), iterations=1)

    # cv.floodFill needs mask of size h+2,w+2; non-zero pixels are barriers
    flood_mask = np.zeros((h + 2, w + 2), np.uint8)
    flood_mask[1:-1, 1:-1] = (edges > 0).astype(np.uint8)

    accum = np.zeros((h, w), np.uint8)
    tol = (req.tolerance,) * 3
    for (px, py) in pts:
        m = flood_mask.copy()
        try:
            cv2.floodFill(
                img.copy(),
                m,
                (px, py),
                (255, 255, 255),
                tol, tol,
                flags=4 | (255 << 8) | cv2.FLOODFILL_MASK_ONLY | cv2.FLOODFILL_FIXED_RANGE,
            )
        except cv2.error as e:
            raise HTTPException(500, f"flood failed: {e}")
        filled = (m[1:-1, 1:-1] == 255).astype(np.uint8) * 255
        accum = cv2.bitwise_or(accum, filled)

    accum = cleanup_mask(accum, kernel_size=3)
    accum = largest_connected(accum, pts[0])

    return {
        "mask": encode_mask_png(accum),
        "width": w,
        "height": h,
        "method": "flood-smart",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/slic-superpixels")
def slic_superpixels(req: SlicReq):
    """
    SLIC superpixels then merge the segment(s) under the click, plus neighbors
    with similar mean color (LAB delta < 8). Great for textured walls.
    """
    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    pts = to_pixel_points(req.points, w, h)

    rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    segments = slic(rgb, n_segments=req.n_segments, compactness=req.compactness, start_label=0, channel_axis=-1)
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)

    # mean LAB per segment
    seg_ids = np.unique(segments)
    means = {}
    for s in seg_ids:
        mask_s = (segments == s)
        means[int(s)] = lab[mask_s].mean(axis=0)

    # seed segments under clicks
    seed_segs = set()
    for (px, py) in pts:
        seed_segs.add(int(segments[py, px]))

    # grow: include any segment with LAB delta < threshold from any seed mean
    THRESH = 9.0
    seed_means = [means[s] for s in seed_segs]
    chosen = set(seed_segs)
    for s in seg_ids:
        if int(s) in chosen:
            continue
        m = means[int(s)]
        for sm in seed_means:
            if np.linalg.norm(m - sm) < THRESH:
                chosen.add(int(s))
                break

    binary = np.isin(segments, list(chosen)).astype(np.uint8) * 255
    binary = cleanup_mask(binary, kernel_size=5)
    binary = largest_connected(binary, pts[0])

    return {
        "mask": encode_mask_png(binary),
        "width": w,
        "height": h,
        "method": "slic-superpixels",
        "elapsed_ms": int((time.time() - t0) * 1000),
        "segment_count": int(len(seg_ids)),
    }


@app.post("/segment-all")
def segment_all(req: SegmentAllReq):
    """
    Felzenszwalb segmentation → returns each region as a separate mask.
    Useful for the 🤖 auto mode: get every flat surface in one shot.
    """
    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

    segs = felzenszwalb(rgb, scale=req.scale, sigma=0.7, min_size=req.min_size, channel_axis=-1)
    regions = []
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)

    for s in np.unique(segs):
        mask_s = (segs == s)
        area = int(mask_s.sum())
        if area < req.min_size:
            continue
        m_uint = (mask_s.astype(np.uint8)) * 255
        m_clean = cleanup_mask(m_uint, kernel_size=5)
        mean_lab = lab[mask_s].mean(axis=0)
        mean_bgr = cv2.cvtColor(np.uint8([[mean_lab]]), cv2.COLOR_LAB2BGR)[0, 0]
        ys, xs = np.where(mask_s)
        regions.append({
            "id": int(s),
            "mask": encode_mask_png(m_clean),
            "area": area,
            "centroid": [int(xs.mean()), int(ys.mean())],
            "mean_color_hex": f"#{mean_bgr[2]:02x}{mean_bgr[1]:02x}{mean_bgr[0]:02x}",
        })

    regions.sort(key=lambda r: r["area"], reverse=True)

    return {
        "regions": regions[:24],  # cap to top 24 biggest
        "width": w,
        "height": h,
        "method": "felzenszwalb",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/precompute")
def precompute(req: PrecomputeReq):
    """
    One-shot segmentation for hover preview.

    Strategy
    --------
    1. Felzenszwalb to oversegment.
    2. Greedy merge of touching segments whose LAB centroids are close
       (so a single wall split into 5 lighting bands becomes one region).
    3. Relabel from 1 (largest area first) — cap at 254 regions (label 0 = bg).
    4. Encode the label map as a single-channel PNG. The client decodes once
       and uses pixel value at mouse position to look up the hovered region.

    Returns
    -------
    {
        "label_map": "data:image/png;base64,...",  # uint8 single channel
        "regions": [{ "id":1, "area":12345, "centroid":[x,y], "bbox":[x,y,w,h] }, ...],
        "width": W, "height": H,                    # label-map size (may be downscaled)
        "orig_width": W0, "orig_height": H0,        # original image size
        "scale_x": W/W0, "scale_y": H/H0,
        "method": "felzenszwalb+merge",
        "elapsed_ms": ...
    }
    """
    t0 = time.time()
    img = decode_image(req.image)
    h0, w0 = img.shape[:2]

    # Downscale if needed (Felzenszwalb is O(N log N), so big images get slow)
    scale_factor = min(1.0, req.max_dim / max(w0, h0))
    if scale_factor < 1.0:
        new_w = int(round(w0 * scale_factor))
        new_h = int(round(h0 * scale_factor))
        img_small = cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)
    else:
        img_small = img

    h, w = img_small.shape[:2]
    rgb = cv2.cvtColor(img_small, cv2.COLOR_BGR2RGB)
    lab = cv2.cvtColor(img_small, cv2.COLOR_BGR2LAB)

    segs = felzenszwalb(rgb, scale=req.scale, sigma=0.7, min_size=req.min_size, channel_axis=-1)

    # Merge neighbors with similar LAB
    seg_ids = np.unique(segs)
    means = {int(s): lab[segs == s].mean(axis=0) for s in seg_ids}

    # Build neighbor adjacency by scanning shifted views
    neighbors: dict[int, set[int]] = {int(s): set() for s in seg_ids}
    a = segs
    edges_h = (a[:, :-1] != a[:, 1:])
    for (y, x) in zip(*np.where(edges_h)):
        s1, s2 = int(a[y, x]), int(a[y, x + 1])
        neighbors[s1].add(s2); neighbors[s2].add(s1)
    edges_v = (a[:-1, :] != a[1:, :])
    for (y, x) in zip(*np.where(edges_v)):
        s1, s2 = int(a[y, x]), int(a[y + 1, x])
        neighbors[s1].add(s2); neighbors[s2].add(s1)

    # Union-find
    parent = {int(s): int(s) for s in seg_ids}

    def find(x):
        while parent[x] != x:
            parent[x] = parent[parent[x]]
            x = parent[x]
        return x

    def union(x, y):
        rx, ry = find(x), find(y)
        if rx != ry:
            parent[ry] = rx

    THRESH = req.merge_lab_threshold
    for s, nbrs in neighbors.items():
        for n in nbrs:
            if np.linalg.norm(means[s] - means[n]) < THRESH:
                union(s, n)

    # Build remap: root -> new label sorted by area desc
    root_areas: dict[int, int] = {}
    for s in seg_ids:
        r = find(int(s))
        root_areas[r] = root_areas.get(r, 0) + int((segs == s).sum())

    sorted_roots = sorted(root_areas.items(), key=lambda kv: kv[1], reverse=True)
    root_to_label: dict[int, int] = {}
    for i, (root, _) in enumerate(sorted_roots[:254]):
        root_to_label[root] = i + 1  # 1..254; 0 = background

    # Build the label map (uint8)
    label_map = np.zeros((h, w), dtype=np.uint8)
    for s in seg_ids:
        r = find(int(s))
        if r in root_to_label:
            label_map[segs == s] = root_to_label[r]

    # Per-region info
    regions = []
    for root, area in sorted_roots[:254]:
        lbl = root_to_label[root]
        mask = (label_map == lbl)
        if not mask.any():
            continue
        ys, xs = np.where(mask)
        mean_bgr = cv2.cvtColor(np.uint8([[lab[mask].mean(axis=0)]]), cv2.COLOR_LAB2BGR)[0, 0]
        regions.append({
            "id": int(lbl),
            "area": int(area),
            "centroid": [int(xs.mean()), int(ys.mean())],
            "bbox": [int(xs.min()), int(ys.min()),
                     int(xs.max() - xs.min() + 1), int(ys.max() - ys.min() + 1)],
            "mean_color_hex": f"#{mean_bgr[2]:02x}{mean_bgr[1]:02x}{mean_bgr[0]:02x}",
        })

    ok, buf = cv2.imencode('.png', label_map)
    if not ok:
        raise HTTPException(500, "failed to encode label map")
    b64 = base64.b64encode(buf.tobytes()).decode("ascii")

    return {
        "label_map": f"data:image/png;base64,{b64}",
        "regions": regions,
        "width": w,
        "height": h,
        "orig_width": w0,
        "orig_height": h0,
        "scale_x": w / w0,
        "scale_y": h / h0,
        "method": "felzenszwalb+merge",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


# --------------------------------------------------------------------------- #
#  Deep-learning endpoints (Segformer + SAM, local CPU via onnxruntime)        #
# --------------------------------------------------------------------------- #
@app.post("/semantic")
def semantic(req: SemanticReq):
    """
    Auto-detect every paintable surface (wall / ceiling / floor / window /
    door / cabinet) in one shot using Segformer (ADE20K). Returns one region
    per connected surface with its semantic label — no clicking required.
    """
    import vision_models as vm

    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    regions_raw = vm.segment_surfaces(img)

    regions = []
    for r in regions_raw:
        m = r["mask"]
        if req.refine:
            m = refine_mask_edges(m, img)
        regions.append({
            "surface": r["surface"],
            "class_id": r["class_id"],
            "mask": encode_mask_png(m),
            "area": r["area"],
            "centroid": r["centroid"],
            "bbox": r["bbox"],
        })

    return {
        "regions": regions,
        "width": w,
        "height": h,
        "method": "segformer-b2-ade20k",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/semantic-point")
def semantic_point(req: SemanticPointReq):
    """
    Click a point -> return the semantic surface under it (whole wall/floor/...),
    using Segformer. Best for 'paint this entire wall' with a single click.
    """
    import vision_models as vm

    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    pts = to_pixel_points(req.points, w, h)
    if not pts:
        raise HTTPException(400, "at least one point required")

    res = vm.surface_at_point(img, pts[0][0], pts[0][1])
    if res is None:
        raise HTTPException(422, "no recognised surface under the clicked point")

    m = res["mask"]
    if req.refine:
        m = refine_mask_edges(m, img)

    return {
        "mask": encode_mask_png(m),
        "surface": res["surface"],
        "class_id": res["class_id"],
        "width": w,
        "height": h,
        "method": "segformer-point",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


@app.post("/sam-point")
def sam_point(req: SamPointReq):
    """
    Promptable click-to-select via SlimSAM (SAM family). Precise object mask
    for the clicked point — the strong replacement for /grabcut.
    """
    import vision_models as vm

    t0 = time.time()
    img = decode_image(req.image)
    h, w = img.shape[:2]
    pts = to_pixel_points(req.points, w, h)
    if not pts:
        raise HTTPException(400, "at least one point required")

    try:
        binary = vm._Sam.decode(img, pts[0][0], pts[0][1])
    except Exception as e:
        raise HTTPException(500, f"sam failed: {e}")

    binary = cleanup_mask(binary)
    binary = largest_connected(binary, pts[0])
    if req.refine:
        binary = refine_mask_edges(binary, img)

    return {
        "mask": encode_mask_png(binary),
        "width": w,
        "height": h,
        "method": "slimsam-77",
        "elapsed_ms": int((time.time() - t0) * 1000),
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8001, log_level="info")
