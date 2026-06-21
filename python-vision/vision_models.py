"""
ONNX inference for Rangify Vision Service
=========================================
Local, free, CPU-only deep-learning segmentation — no torch, no cloud API.

Two models (downloaded once into ./models, run via onnxruntime):

  * Segformer-b2 (ADE20K)  -> SEMANTIC surface detection.
        Knows real classes: wall / floor / ceiling / window / door / cabinet.
        Used for "auto-detect every surface" with one shot.

  * SlimSAM-77 (SAM family) -> PROMPTABLE click-to-select.
        User clicks a point, returns a precise mask for that object.
        Replaces the old GrabCut path.

Models are lazy-loaded on first use and cached for the process lifetime.
"""
from __future__ import annotations

import os
import threading
from typing import Optional

import cv2
import numpy as np
import onnxruntime as ort

MODELS_DIR = os.path.join(os.path.dirname(__file__), "models")

# ADE20K class ids (Segformer id2label) that matter for a wall-recolor app.
# label -> human-friendly surface name
SURFACE_CLASSES = {
    0: "wall",
    3: "floor",
    5: "ceiling",
    8: "window",
    14: "door",
    10: "cabinet",
}

# ImageNet normalization (from segformer_preprocessor.json)
_IMAGENET_MEAN = np.array([0.485, 0.456, 0.406], dtype=np.float32)
_IMAGENET_STD = np.array([0.229, 0.224, 0.225], dtype=np.float32)
_SEG_SIZE = 512  # model input H=W

# SAM preprocessing (transformers.js / SlimSAM)
_SAM_LONG_SIDE = 1024
_SAM_MEAN = np.array([123.675, 116.28, 103.53], dtype=np.float32)
_SAM_STD = np.array([58.395, 57.12, 57.375], dtype=np.float32)


def _ort_session(path: str) -> ort.InferenceSession:
    so = ort.SessionOptions()
    so.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL
    so.intra_op_num_threads = max(1, (os.cpu_count() or 4))
    return ort.InferenceSession(path, sess_options=so, providers=["CPUExecutionProvider"])


# --------------------------------------------------------------------------- #
#  Segformer — semantic segmentation                                          #
# --------------------------------------------------------------------------- #
class _Segformer:
    _lock = threading.Lock()
    _sess: Optional[ort.InferenceSession] = None
    _in_name: str = ""
    _out_name: str = ""

    @classmethod
    def session(cls) -> ort.InferenceSession:
        if cls._sess is None:
            with cls._lock:
                if cls._sess is None:
                    path = os.path.join(MODELS_DIR, "segformer_b2.onnx")
                    if not os.path.exists(path):
                        raise FileNotFoundError(
                            "segformer_b2.onnx missing — run _download_models.py"
                        )
                    s = _ort_session(path)
                    cls._sess = s
                    cls._in_name = s.get_inputs()[0].name
                    cls._out_name = s.get_outputs()[0].name
        return cls._sess

    @classmethod
    def label_map(cls, img_bgr: np.ndarray) -> np.ndarray:
        """Return a full-resolution (H,W) int32 array of ADE20K class ids."""
        s = cls.session()
        h0, w0 = img_bgr.shape[:2]

        rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)
        resized = cv2.resize(rgb, (_SEG_SIZE, _SEG_SIZE), interpolation=cv2.INTER_LINEAR)
        x = resized.astype(np.float32) / 255.0
        x = (x - _IMAGENET_MEAN) / _IMAGENET_STD
        x = np.transpose(x, (2, 0, 1))[None, ...].astype(np.float32)  # NCHW

        logits = s.run([cls._out_name], {cls._in_name: x})[0]  # (1, 150, h', w')
        logits = logits[0]
        labels_small = np.argmax(logits, axis=0).astype(np.int32)  # (h', w')

        # upsample label map to original size (nearest preserves class ids)
        labels = cv2.resize(
            labels_small, (w0, h0), interpolation=cv2.INTER_NEAREST
        ).astype(np.int32)
        return labels


def segment_surfaces(img_bgr: np.ndarray, min_area_frac: float = 0.004) -> list[dict]:
    """
    Run Segformer and return one entry per detected surface region.
    Each region = a connected component of a surface class big enough to matter.

    Returns list of:
        { class_id, surface, area, centroid:[x,y], bbox:[x,y,w,h], mask:<uint8 HxW 0/255> }
    (mask is a raw ndarray; caller PNG-encodes.)
    """
    labels = _Segformer.label_map(img_bgr)
    h, w = labels.shape
    min_area = int(h * w * min_area_frac)
    out: list[dict] = []

    for class_id, name in SURFACE_CLASSES.items():
        class_mask = (labels == class_id).astype(np.uint8)
        if class_mask.sum() == 0:
            continue
        # split into connected components so two separate walls become two regions
        num, comp, stats, cents = cv2.connectedComponentsWithStats(class_mask, 8)
        for i in range(1, num):
            area = int(stats[i, cv2.CC_STAT_AREA])
            if area < min_area:
                continue
            m = (comp == i).astype(np.uint8) * 255
            x, y, bw, bh = (
                int(stats[i, cv2.CC_STAT_LEFT]),
                int(stats[i, cv2.CC_STAT_TOP]),
                int(stats[i, cv2.CC_STAT_WIDTH]),
                int(stats[i, cv2.CC_STAT_HEIGHT]),
            )
            out.append(
                {
                    "class_id": class_id,
                    "surface": name,
                    "area": area,
                    "centroid": [int(cents[i][0]), int(cents[i][1])],
                    "bbox": [x, y, bw, bh],
                    "mask": m,
                }
            )

    out.sort(key=lambda r: r["area"], reverse=True)
    return out


def surface_at_point(img_bgr: np.ndarray, px: int, py: int) -> Optional[dict]:
    """
    Semantic pick: return the surface region whose class covers pixel (px,py),
    restricted to the connected component under the click.
    """
    labels = _Segformer.label_map(img_bgr)
    h, w = labels.shape
    px = max(0, min(w - 1, px))
    py = max(0, min(h - 1, py))
    cid = int(labels[py, px])
    if cid not in SURFACE_CLASSES:
        return None
    class_mask = (labels == cid).astype(np.uint8)
    num, comp, stats, cents = cv2.connectedComponentsWithStats(class_mask, 8)
    target = int(comp[py, px])
    if target == 0:
        return None
    m = (comp == target).astype(np.uint8) * 255
    return {
        "class_id": cid,
        "surface": SURFACE_CLASSES[cid],
        "area": int(stats[target, cv2.CC_STAT_AREA]),
        "centroid": [int(cents[target][0]), int(cents[target][1])],
        "mask": m,
    }


# --------------------------------------------------------------------------- #
#  SAM (SlimSAM-77) — promptable click-to-select                              #
# --------------------------------------------------------------------------- #
class _Sam:
    _lock = threading.Lock()
    _enc: Optional[ort.InferenceSession] = None
    _dec: Optional[ort.InferenceSession] = None

    @classmethod
    def _ensure(cls):
        if cls._enc is None or cls._dec is None:
            with cls._lock:
                if cls._enc is None:
                    p = os.path.join(MODELS_DIR, "sam_encoder.onnx")
                    if not os.path.exists(p):
                        raise FileNotFoundError("sam_encoder.onnx missing")
                    cls._enc = _ort_session(p)
                if cls._dec is None:
                    p = os.path.join(MODELS_DIR, "sam_decoder.onnx")
                    if not os.path.exists(p):
                        raise FileNotFoundError("sam_decoder.onnx missing")
                    cls._dec = _ort_session(p)

    @staticmethod
    def _preprocess(img_bgr: np.ndarray):
        """Resize longest side to 1024, normalize, pad to 1024x1024 (top-left)."""
        h0, w0 = img_bgr.shape[:2]
        scale = _SAM_LONG_SIDE / max(h0, w0)
        nh, nw = int(round(h0 * scale)), int(round(w0 * scale))
        rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)
        resized = cv2.resize(rgb, (nw, nh), interpolation=cv2.INTER_LINEAR).astype(np.float32)
        norm = (resized - _SAM_MEAN) / _SAM_STD
        padded = np.zeros((_SAM_LONG_SIDE, _SAM_LONG_SIDE, 3), dtype=np.float32)
        padded[:nh, :nw] = norm
        x = np.transpose(padded, (2, 0, 1))[None, ...].astype(np.float32)  # 1,3,1024,1024
        return x, scale, (nh, nw)

    @classmethod
    def encode(cls, img_bgr: np.ndarray):
        cls._ensure()
        x, scale, (nh, nw) = cls._preprocess(img_bgr)
        in_name = cls._enc.get_inputs()[0].name
        outs = cls._enc.run(None, {in_name: x})
        out_names = [o.name for o in cls._enc.get_outputs()]
        emb = {name: val for name, val in zip(out_names, outs)}
        return emb, scale

    @classmethod
    def decode(cls, img_bgr: np.ndarray, px: int, py: int) -> np.ndarray:
        """Return a full-res binary mask (0/255) for the object under (px,py)."""
        cls._ensure()
        h0, w0 = img_bgr.shape[:2]
        emb, scale = cls.encode(img_bgr)

        # point in the resized (1024-longest) space
        pt = np.array([[[[px * scale, py * scale]]]], dtype=np.float32)  # 1,1,1,2
        lbl = np.array([[[1]]], dtype=np.int64)  # 1,1,1 (foreground); decoder wants int64

        dec_inputs = {i.name: i for i in cls._dec.get_inputs()}
        feed = {}
        # image embeddings produced by the encoder (names vary by export)
        for key, val in emb.items():
            if key in dec_inputs:
                feed[key] = val
        # common transformers.js SAM decoder input names
        if "input_points" in dec_inputs:
            feed["input_points"] = pt
        if "input_labels" in dec_inputs:
            feed["input_labels"] = lbl

        out_names = [o.name for o in cls._dec.get_outputs()]
        outs = cls._dec.run(None, feed)
        res = {n: v for n, v in zip(out_names, outs)}

        # find the masks output: shape (...,num_masks,H,W); pick best by iou
        masks = None
        iou = None
        for n, v in res.items():
            if v.ndim >= 4 and v.shape[-1] >= 64 and v.shape[-2] >= 64:
                masks = v
            elif v.ndim <= 3 and v.size <= 8:
                iou = v
        if masks is None:
            raise RuntimeError(f"SAM decoder produced no mask; outputs={[(n, v.shape) for n, v in res.items()]}")

        m = masks.reshape(-1, masks.shape[-2], masks.shape[-1])  # (k,H,W)
        if iou is not None and iou.size == m.shape[0]:
            best = int(np.argmax(iou.reshape(-1)))
        else:
            best = 0
        low = m[best]  # (H,W) logits, usually 256x256

        # upsample to 1024, crop to the resized region, then back to original
        up = cv2.resize(low, (_SAM_LONG_SIDE, _SAM_LONG_SIDE), interpolation=cv2.INTER_LINEAR)
        nh, nw = int(round(h0 * scale)), int(round(w0 * scale))
        up = up[:nh, :nw]
        full = cv2.resize(up, (w0, h0), interpolation=cv2.INTER_LINEAR)
        binary = (full > 0).astype(np.uint8) * 255
        return binary
