"""
Smoke-test the new deep-learning segmentation.
Downloads one sample interior photo (once), runs Segformer + SlimSAM,
and writes visual outputs to ./test_out/ for inspection.
"""
import os
import time
import urllib.request

import cv2
import numpy as np

HERE = os.path.dirname(__file__)
OUT = os.path.join(HERE, "test_out")
os.makedirs(OUT, exist_ok=True)
SAMPLE = os.path.join(OUT, "room.jpg")

# A plainly-licensed interior photo from Wikimedia Commons.
SAMPLE_URL = (
    "https://commons.wikimedia.org/wiki/Special:FilePath/"
    "Modern_Living_Room.jpg?width=1024"
)


def ensure_sample():
    if os.path.exists(SAMPLE) and os.path.getsize(SAMPLE) > 10000:
        return
    print("downloading sample room image ...")
    req = urllib.request.Request(SAMPLE_URL, headers={"User-Agent": "rangify-test/1.0"})
    with urllib.request.urlopen(req, timeout=30) as r, open(SAMPLE, "wb") as f:
        f.write(r.read())
    print("  saved", SAMPLE, os.path.getsize(SAMPLE), "bytes")


def color_overlay(img, mask, bgr, alpha=0.5):
    out = img.copy()
    sel = mask > 0
    out[sel] = (out[sel] * (1 - alpha) + np.array(bgr) * alpha).astype(np.uint8)
    return out


def main():
    ensure_sample()
    import vision_models as vm

    img = cv2.imread(SAMPLE)
    h, w = img.shape[:2]
    print(f"\nimage: {w}x{h}")

    # ---- Segformer: detect all surfaces ----
    t = time.time()
    regions = vm.segment_surfaces(img)
    print(f"\n[Segformer] {len(regions)} regions in {int((time.time()-t)*1000)} ms")
    palette = {
        "wall": (80, 80, 255), "floor": (80, 200, 80), "ceiling": (255, 180, 80),
        "window": (255, 255, 80), "door": (200, 80, 200), "cabinet": (80, 220, 220),
    }
    overlay = img.copy()
    for r in regions:
        print(f"   - {r['surface']:8s} area={r['area']:8d}  centroid={r['centroid']}")
        overlay = color_overlay(overlay, r["mask"], palette.get(r["surface"], (200, 200, 200)))
    cv2.imwrite(os.path.join(OUT, "segformer_overlay.png"), overlay)
    print("   -> wrote segformer_overlay.png")

    # ---- Segformer point pick: biggest wall centroid ----
    walls = [r for r in regions if r["surface"] == "wall"]
    if walls:
        cx, cy = walls[0]["centroid"]
        res = vm.surface_at_point(img, cx, cy)
        if res:
            ov = color_overlay(img, res["mask"], (0, 0, 255))
            cv2.circle(ov, (cx, cy), 8, (255, 255, 255), 2)
            cv2.imwrite(os.path.join(OUT, "semantic_point_wall.png"), ov)
            print(f"   -> semantic point pick: {res['surface']} ({res['area']} px)")

        # ---- SAM at same point ----
        t = time.time()
        try:
            m = vm._Sam.decode(img, cx, cy)
            ov = color_overlay(img, m, (0, 255, 0))
            cv2.circle(ov, (cx, cy), 8, (255, 255, 255), 2)
            cv2.imwrite(os.path.join(OUT, "sam_point_wall.png"), ov)
            print(f"\n[SlimSAM] mask at ({cx},{cy}) in {int((time.time()-t)*1000)} ms"
                  f"  -> {int((m>0).sum())} px, wrote sam_point_wall.png")
        except Exception as e:
            print(f"\n[SlimSAM] FAILED: {e}")

    print("\nDONE. Inspect python-vision/test_out/*.png")


if __name__ == "__main__":
    main()
