# راهنمای Deploy رنگی‌فای (Rangify)

ابزار رنگ‌آمیزی دیوار — Laravel 11 + React + TypeScript + Inertia + Vite.

---

## 🌐 آدرس‌ها

| | |
|---|---|
| **Production** | https://rangifyapp.yaldajahanshahi.ir |
| **Repo (public)** | https://github.com/Yaldaj19/rangify |
| **Local (XAMPP)** | http://localhost/projects/rangify.site/public |
| **Local path** | `C:\xampp\htdocs\projects\rangify.site` |
| **Host path** | `~/rangifyapp.yaldajahanshahi.ir` (cPanel SSH) |
| **PHP host** | PHP 8.3 (همون `php` در SSH) |
| **Composer host** | `php ~/composer.phar` (در PATH نیست، phar در home هست) |
| **Document root** | `~/rangifyapp.yaldajahanshahi.ir/public` |

---

## ⚠️ مهم‌ترین نکته: شاخه (branch)

روی لوکال معمولاً روی شاخه‌ی **`changes`** کار می‌کنیم، **ولی هاست از `main` می‌کشه**.
پس commit روی `changes` به‌تنهایی روی هاست **دیده نمی‌شه**. قبل از هر deploy باید کد را به `main` هم برسانی.

---

## 🧑‍💻 مرحله ۱ — روی سیستم خودت (قبل از deploy)

```powershell
cd C:\xampp\htdocs\projects\rangify.site
# اگر بلِید/کلاس‌های Tailwind را تغییر دادی، CSS را دوباره بساز (هاست Node ندارد):
./tools/tailwindcss.exe -i resources/css/app.css -o public/css/app.css --minify
git add -A
git commit -m "توضیح تغییر"
```

سپس کد را روی هر دو شاخه ببر و push کن:

```powershell
git push origin changes
git checkout main
git merge --ff-only changes
git push origin main
git checkout changes
```

> اگر `merge --ff-only` خطا داد یعنی شاخه‌ها واگرا شده‌اند؛ آن موقع `git merge changes` بزن.

---

## 🚀 مرحله ۲ — روی SSH هاست (بعد از push)

```bash
cd ~/rangifyapp.yaldajahanshahi.ir
git pull
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### حالت‌های خاص

- **اگر `composer.json` تغییر کرد** (پکیج جدید): قبل از migrate بزن:
  ```bash
  php ~/composer.phar install --no-dev --optimize-autoloader
  ```
- **اگر فقط `.env` تغییر کرد**:
  ```bash
  php artisan optimize:clear && php artisan config:cache
  ```

---

## 🩺 نکات مهم (یادداشت‌های لازم)

### 1. فرانت‌اند = Blade + Alpine (نه React/Inertia)
صفحات واقعی Blade هستند و CSS را از `public/css/app.css` با `asset('css/app.css')` لود می‌کنند
(نه `@vite`، نه `public/build`). پوشه‌ی `resources/js/**/*.tsx` و مسیر Vite/`npm run build` **مرده/بلااستفاده** است؛ سراغش نرو.

### 2. ساخت CSS (مهم) — با باینری standalone Tailwind v4
هاست Node ندارد، پس CSS باید **محلی** ساخته و `public/css/app.css` در گیت commit شود:
```powershell
./tools/tailwindcss.exe -i resources/css/app.css -o public/css/app.css --minify
```
`resources/css/app.css` گرامر **Tailwind v4** دارد (`@import "tailwindcss"` + `@theme` + `@source "../views/**"`).
دستش نزن که به v3 برگردد. فونت‌ها در `public/fonts/` و مسیرشان `url('../fonts/...')` (نسبت به `public/css/`) است.
(`tools/*.exe` در `.gitignore` است و فقط محلی وجود دارد.)

### 3. دیتابیس MySQL/MariaDB هاست قدیمی است
سقف طول کلید ۱۰۰۰ بایت است. به همین خاطر در `AppServiceProvider::boot()` مقدار
`Schema::defaultStringLength(125)` ست شده تا ایندکس‌های دوستونه‌ی utf8mb4 (مثل spatie permissions) نترکند.
**این خط را حذف نکن.**

### 4. دیتابیس تازه (نصب اولیه یا ریست کامل)
```bash
php artisan migrate:fresh --seed --force
```
> این همه‌ی جدول‌ها را پاک می‌کند. فقط روی نصب اولیه یا وقتی داده‌ی مهمی نداری.
> رمز ادمین از `ADMIN_PASSWORD` در `.env` خوانده می‌شود. `firstOrCreate` رمز کاربر موجود را عوض نمی‌کند.

### 5. نقش‌ها (spatie/laravel-permission)
- `super-admin` (`yaldaj.619@gmail.com`): همه‌چیز
- `admin`: فقط کاربران ساخته‌ی خودش
- `user`: فقط پروژه‌های خودش

### 6. python-vision (تشخیص دیوار با AI)
سرویس `python-vision/` روی هاست اشتراکی **اجرا نمی‌شود** (نیاز به اجرای دائمی روی پورت 8001).
به‌جایش `SmartSelectController` به API ابری fallback می‌زند: یکی از این‌ها را در `.env` ست کن:
```
REPLICATE_API_TOKEN=...
# یا
HUGGINGFACE_API_KEY=...
```
بازرنگ‌آمیزی با AI هم به `OPENROUTER_API_KEY` در `.env` نیاز دارد.

### 7. Document root
ساب‌دامین باید به `~/rangifyapp.yaldajahanshahi.ir/public` اشاره کند (نه ریشه‌ی پروژه)،
وگرنه سورس‌کد لو می‌رود یا سایت بالا نمی‌آید. (در cPanel → Domains تنظیم می‌شود.)

### 8. مجوزها
`storage/` و `bootstrap/cache/` باید نوشتنی باشند:
```bash
chmod -R 775 storage bootstrap/cache
```

### 9. storage:link روی هاست کار نمی‌کند (exec غیرفعال است)
`php artisan storage:link` روی هاست خطای `Call to undefined function ...exec()` می‌دهد چون
cPanel تابع `exec` را بسته است. به‌جایش symlink را دستی بساز (فقط یک‌بار در نصب اولیه لازم است):
```bash
cd ~/rangifyapp.yaldajahanshahi.ir/public
ln -s ../storage/app/public storage
```

### 10. نصب کاملاً تازه روی هاست (از صفر)
```bash
cd ~/rangifyapp.yaldajahanshahi.ir
git init && git remote add origin https://github.com/Yaldaj19/rangify.git
git fetch origin && git checkout main
php ~/composer.phar install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# سپس DB و APP_ENV/APP_DEBUG/APP_URL را در .env تنظیم کن (به بخش‌های بالا نگاه کن)
php artisan migrate:fresh --seed --force
cd public && ln -s ../storage/app/public storage && cd ..
# و document root ساب‌دامین را در cPanel روی پوشه‌ی public بگذار
```

---

## 🆘 مشکل پیش آمد

1. `php artisan optimize:clear` و دوباره cache بساز.
2. لاگ: `tail -50 storage/logs/laravel.log`
3. صفحه‌ی سفید/خطای 500 → معمولاً `.env` ناقص است یا config کش‌شده قدیمی.
4. استایل‌ها نمی‌آیند → `public/css/app.css` در گیت نیست یا با `tools/tailwindcss.exe` دوباره نساخته‌ای (به نکته ۲ نگاه کن).
