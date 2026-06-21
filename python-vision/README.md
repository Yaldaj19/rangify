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

## دانلود مدل‌های هوشمند (یک‌بار، الزامی)

تشخیص قوی دیوار از دو مدل **deep-learning محلی** استفاده می‌کنه که با `onnxruntime`
روی CPU اجرا می‌شن (بدون torch، بدون API ابری، کاملاً آفلاین و رایگان):

- **Segformer-b2 (ADE20K)** — تشخیص معنایی: واقعاً می‌فهمه کجا `wall / ceiling / floor / window / door / cabinet` هست.
- **SlimSAM-77 (خانواده SAM)** — انتخاب دقیق با کلیک (جایگزین GrabCut).

وزن‌ها در گیت نیستن (~۱۵۰MB). یک‌بار دانلودشون کن:

```cmd
.venv\Scripts\python.exe _download_models.py
```

فایل‌ها در `models\` ذخیره می‌شن. دانلودر resume و retry داره (برای کانکشن‌های کند/قطع‌وصلی).
اگه HuggingFace باز نشد، VPN لازمه.

## endpoint ها

| route | کاربرد | مدل | میانگین زمان (CPU) |
|---|---|---|---|
| `GET  /health` | چک سرویس | — | <10ms |
| `POST /semantic` | **تشخیص خودکار همه سطوح** (بدون کلیک) | Segformer | 2-4s |
| `POST /semantic-point` | **کلیک → کل دیوار/سقف/کف** (پیش‌فرض) | Segformer | 1.5-3s |
| `POST /sam-point` | کلیک → انتخاب دقیق شیء | SlimSAM | 4-7s |
| `POST /grabcut` | کلیک کلاسیک (fallback) | OpenCV | 300-800ms |
| `POST /watershed` | چند نقطه FG + BG → جداسازی | OpenCV | 200-500ms |
| `POST /flood-smart` | flood fill با احترام به لبه‌های Canny | OpenCV | 100-300ms |
| `POST /slic-superpixels` | دیوار بافت‌دار / گرادیان | OpenCV | 500-1200ms |
| `POST /segment-all` | کشف همه نواحی (رنگ‌محور) | scikit-image | 800-2000ms |

> در لاراول، `smart-point` به‌صورت پیش‌فرض زنجیره‌ی `semantic-point → sam-point → grabcut`
> رو امتحان می‌کنه؛ یعنی اگه کاربر روی چیزی کلیک کنه که سطح شناخته‌شده نیست،
> خودکار به SAM و بعد GrabCut نزول می‌کنه.

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
