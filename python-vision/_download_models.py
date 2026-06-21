"""
Download the ONNX model weights for the Rangify vision service.

Uses direct HuggingFace `resolve` URLs with HTTP-range RESUME + retries,
because the hf_hub xet/LFS path stalls on some networks (e.g. from Iran).
Re-run any time; finished files are skipped, partial files resume.
"""
import os
import time
import urllib.request
import urllib.error

DEST = os.path.join(os.path.dirname(__file__), "models")
os.makedirs(DEST, exist_ok=True)

BASE = "https://huggingface.co/{repo}/resolve/main/{src}"
JOBS = [
    ("Xenova/segformer-b2-finetuned-ade-512-512", "config.json",                          "segformer_config.json"),
    ("Xenova/segformer-b2-finetuned-ade-512-512", "preprocessor_config.json",             "segformer_preprocessor.json"),
    ("Xenova/segformer-b2-finetuned-ade-512-512", "onnx/model.onnx",                       "segformer_b2.onnx"),
    ("Xenova/slimsam-77-uniform",                 "config.json",                          "sam_config.json"),
    ("Xenova/slimsam-77-uniform",                 "preprocessor_config.json",             "sam_preprocessor.json"),
    ("Xenova/slimsam-77-uniform",                 "onnx/vision_encoder.onnx",              "sam_encoder.onnx"),
    ("Xenova/slimsam-77-uniform",                 "onnx/prompt_encoder_mask_decoder.onnx", "sam_decoder.onnx"),
]


def remote_size(url):
    req = urllib.request.Request(url, method="HEAD", headers={"User-Agent": "rangify/1.0"})
    with urllib.request.urlopen(req, timeout=30) as r:
        return int(r.headers.get("Content-Length", 0))


def download(url, out, total, max_retries=30):
    for attempt in range(1, max_retries + 1):
        have = os.path.getsize(out) if os.path.exists(out) else 0
        if total and have >= total:
            return True
        headers = {"User-Agent": "rangify/1.0"}
        mode = "wb"
        if have:
            headers["Range"] = f"bytes={have}-"
            mode = "ab"
        try:
            req = urllib.request.Request(url, headers=headers)
            with urllib.request.urlopen(req, timeout=40) as r, open(out, mode) as f:
                t0 = time.time()
                while True:
                    chunk = r.read(131072)
                    if not chunk:
                        break
                    f.write(chunk)
                    have += len(chunk)
                    if total and (time.time() - t0) > 2.0:
                        pct = have * 100 // total
                        print(f"    {pct:3d}%  {have/1e6:6.1f}/{total/1e6:.1f} MB", flush=True)
                        t0 = time.time()
            if not total or os.path.getsize(out) >= total:
                return True
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            print(f"    retry {attempt}: {type(e).__name__} (resuming from {have/1e6:.1f} MB)", flush=True)
            time.sleep(2)
    return False


def main():
    for repo, src, dst in JOBS:
        out = os.path.join(DEST, dst)
        url = BASE.format(repo=repo, src=src)
        try:
            total = remote_size(url)
        except Exception as e:
            print(f"!! size check failed for {dst}: {e}", flush=True)
            total = 0
        if os.path.exists(out) and total and os.path.getsize(out) >= total:
            print(f"skip {dst} ({total/1e6:.1f} MB)", flush=True)
            continue
        print(f"downloading {dst} ({total/1e6:.1f} MB) ...", flush=True)
        ok = download(url, out, total)
        print(("done " if ok else "FAILED ") + f"{dst}", flush=True)
        if not ok:
            raise SystemExit(f"could not download {dst}")
    print("ALL DONE", flush=True)


if __name__ == "__main__":
    main()
