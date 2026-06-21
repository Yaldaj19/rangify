"""End-to-end test: hit the real Laravel routes (through Apache)."""
import base64, json, os, urllib.request, urllib.error

HERE = os.path.dirname(__file__)
APP = "http://localhost/projects/rangify.site/public"
img = "data:image/jpeg;base64," + base64.b64encode(
    open(os.path.join(HERE, "test_out", "room.jpg"), "rb").read()
).decode()


def post(path, payload):
    body = json.dumps(payload).encode()
    req = urllib.request.Request(
        APP + path, data=body,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()


print("== /api/ai/smart-point (semantic default + fallback) ==")
st, body = post("/api/ai/smart-point", {"image": img, "points": [[0.5, 0.28]], "labels": [1]})
j = json.loads(body)
print("HTTP", st, "| method=", j.get("method"), "provider=", j.get("provider"),
      "surface=", j.get("surface"), "mask_kb=", len(j.get("mask", "")) // 1000,
      "err=", j.get("error"))

print("\n== /api/ai/segment-surfaces (auto-detect all) ==")
st, body = post("/api/ai/segment-surfaces", {"image": img})
j = json.loads(body)
if "regions" in j:
    print("HTTP", st, "|", len(j["regions"]), "regions:",
          ", ".join(f"{r['surface']}({r['area']})" for r in j["regions"][:6]))
else:
    print("HTTP", st, "|", body[:300])
