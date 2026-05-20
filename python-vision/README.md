# Rangify Vision Service

Microsrvice پایتون با FastAPI برای segmentation تصاویر. توسط `SmartSelectController` فراخوانی می‌شه.

## نصب و راه‌اندازی (یک‌بار)

```cmd
cd C:\xampp\htdocs\projects\rangify.site\python-vision
start.bat
```

اسکریپت `start.bat` خودکار:
1. venv می‌سازه (`.venv\`)
2. dependency ها رو نصب می‌کنه (~150 MB)
3. سرویس رو روی `http://127.0.0.1:8001` بالا میاره

## endpoint ها

| route | کاربرد | میانگین زمان |
|---|---|---|
| `GET  /health` | چک سرویس | <10ms |
| `POST /grabcut` | کلیک روی دیوار → mask دقیق | 300-800ms |
| `POST /watershed` | چند نقطه FG + BG → جداسازی | 200-500ms |
| `POST /flood-smart` | flood fill با احترام به لبه‌های Canny | 100-300ms |
| `POST /slic-superpixels` | برای دیوار بافت‌دار / گرادیان | 500-1200ms |
| `POST /segment-all` | کشف همه نواحی (auto mode) | 800-2000ms |

## شکل ورودی/خروجی

**ورودی** (مثال grabcut):
```json
{
  "image": "data:image/jpeg;base64,...",
  "points": [[0.42, 0.31]],
  "labels": [1],
  "iterations": 5
}
```

`points` می‌تونه normalized (0..1) یا pixel coords باشه — auto detect می‌شه.
`labels`: 1 = foreground (انتخاب شو)، 0 = background (انتخاب نشو).

**خروجی**:
```json
{
  "mask": "data:image/png;base64,...",
  "width": 1920,
  "height": 1080,
  "method": "grabcut",
  "elapsed_ms": 540
}
```

## stop کردن

`Ctrl+C` در پنجره cmd.

## بروزرسانی dependency ها

```cmd
.venv\Scripts\python.exe -m pip install -r requirements.txt --upgrade
```

## troubleshooting

- **OpenCV import error روی Windows**: `pip install opencv-python --force-reinstall`
- **port 8001 اشغاله**: یا process قبلی رو ببند، یا port رو در `main.py` و `.env` Laravel عوض کن
- **سرعت کم**: مدل‌های MEDIUM/HIGH رو reduce کن (`scale=180`, `n_segments=400`)
