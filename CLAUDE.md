# Rangify — راهنمای پروژه برای Claude

> این فایل دستورات بنیادی برای Claude در این پروژه است.
> **هر تغییری** که در پروژه انجام می‌شود باید با این قوانین سازگار باشد.

---

## 🎯 درباره پروژه

**Rangify** یک پلتفرم آنلاین برای **رنگ‌آمیزی مجازی دیوار** است. کاربر یک عکس از دیوار خانه‌اش آپلود می‌کند، رنگ‌های مختلف را روی آن آزمایش می‌کند، و خروجی نهایی را به صورت تصویر و **تور سه‌بعدی** دریافت می‌کند. لایه AI در فاز آخر اضافه می‌شود.

**Domain (آینده):** `rangify.site`
**محیط فعلی:** XAMPP محلی روی Windows 11

---

## 🛠️ Tech Stack

### Backend
- **PHP:** 8.2+
- **Framework:** Laravel 11.x
- **Database:** MySQL 8 → `localhost:3306` / DB `rangify_db` / charset `utf8mb4_unicode_ci`
- **Auth:** Laravel Breeze (Inertia + React stack)
- **Storage:** local disk (محلی) → آینده S3-compatible
- **Queue:** `database` driver (محلی) → آینده Redis
- **Image (server):** Intervention Image v3
- **Package Manager:** Composer

### Frontend
- **Framework:** React 18 + **TypeScript**
- **Bridge:** Inertia.js (بدون REST API جدا)
- **Build:** Vite
- **State:** Zustand
- **Styling:** Tailwind CSS 3 (همراه SASS برای کیس‌های خاص)
- **Animations:** GSAP
- **Font:** Vazirmatn (فارسی، با پشتیبانی کامل RTL)
- **Dark Mode:** Tailwind `dark:` class strategy
- **Package Manager:** **pnpm** (نه npm و نه yarn)

### Image Editor (Manual — بدون AI)
- **Fabric.js v6+** → canvas editor + polygon/lasso
- **Konva.js** → layer-based editing (اختیاری)
- **OpenCV.js** → flood fill، edge detection (Magic Wand)
- **الگوریتم:** HSL recolor با حفظ luminance (بافت و سایه دیوار باید حفظ بشه)

### 3D
- **Three.js** + **React Three Fiber (R3F)** + **@react-three/drei**
- ساخت تور سه‌بعدی از روی Depth Map
- OrbitControls + parallax + camera animation

### AI (فاز ۱۰ — آخر)
- **OpenRouter** (یک API key واحد در `.env`)
- **مدل‌های هدف:**
  - `google/gemini-2.5-flash-image` (image gen/edit)
  - `openai/gpt-4-vision-*` (image understanding)
  - `anthropic/claude-*` (reasoning)
- **اولویت:** اگه مدل رایگان (مثلا `google/gemini-2.0-flash-exp:free`) جواب کار رو می‌ده، اول اون رو استفاده کن
- **API key** هرگز commit نشود — فقط در `.env` که در `.gitignore` است

---

## 🌐 محیط محلی

```
http://localhost/projects/rangify.site/public/
```

```env
APP_URL=http://localhost/projects/rangify.site/public
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rangify_db
DB_USERNAME=root
DB_PASSWORD=

OPENROUTER_API_KEY=
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_DEFAULT_MODEL=google/gemini-2.0-flash-exp:free
```

---

## 🧭 Workflow ایجنت‌ها

```
کاربر → project-supervisor → یکی از 7 ایجنت کارگر
```

| ایجنت | حوزه مسئولیت |
|---|---|
| `project-supervisor` | Plan + Task + گزارش رنگی. خودش کد نمی‌نویسه |
| `backend-architect` | Laravel: Controller, Model, Route, Service, Job |
| `frontend-architect` | React + Inertia: Page, Component, State, Hook |
| `ui-designer` | Tailwind, SASS, GSAP, dark mode, RTL, فونت فارسی |
| `database-architect` | MySQL: migration, seeder, factory, index |
| `ai-integration` | OpenRouter calls, prompt engineering, image AI |
| `3d-engineer` | Three.js, R3F, depth map, camera, WebGL perf |
| `image-processing-engineer` | Fabric/Konva/OpenCV.js — بدون AI |

**قانون:** Supervisor خودش هرگز فایل تولید نمی‌کنه — فقط plan/task/گزارش.

---

## 📋 فازهای پروژه

```
1.  Foundation Setup         → Laravel + Inertia + Tailwind + Vite + TypeScript
2.  Database Schema          → migrations + seeders + factories
3.  Authentication           → Breeze + Guest Trial
4.  Upload Flow              → drag-drop + validation + Storage
5.  Manual Color Editor      → Fabric.js + polygon select
6.  Color Editor UI          → color picker + palette + history
7.  Image Processing & Export → HSL recolor + mask + download
8.  Simple 3D Preview        → Three.js + parallax
9.  Polish & Deploy Prep     → SEO + error pages + production build
10. AI Enhancement Layer     → OpenRouter integration (آخر)
```

**Milestoneهای فرعی:** حساب admin، Guest Trial، Magic Wand + OpenCV.js wall detection، Photopea-style editor، HSL recolor با حفظ luminance، 3D Tour fix.

---

## 📜 Conventions

### File Naming
- **PHP / Laravel:** PascalCase برای کلاس (`ProjectController.php`)، snake_case برای migration
- **React Component:** PascalCase (`ColorPicker.tsx`)
- **سایر فایل‌های JS/TS:** kebab-case (`use-canvas.ts`، `image-utils.ts`)
- **CSS/SCSS:** kebab-case (`color-picker.scss`)

### Code Style
- **TypeScript:** strict mode، interface > type alias برای object shapes
- **React:** functional components + hooks، نه class
- **Prettier:** 2 space indent، single quote، no semi (مگه اجباری)
- **PHP:** PSR-12
- **Import order:** external → internal absolute → relative

### Git
- **Branch:** `main` (production)، `feature/<name>` (کار جدید)
- **Commit:** کوتاه، توصیفی، به انگلیسی
- **هرگز:** `.env`، `vendor/`، `node_modules/`، `storage/app/uploads/` commit نشن

---

## 🔒 امنیت

- **هرگز** API key یا secret رو در کد یا commit نذار
- همه ورودی کاربر (آپلود، فرم، URL param) **validate** بشه
- آپلود تصویر: فقط `jpg/jpeg/png/webp`، حداکثر 10MB، چک magic bytes نه فقط extension
- CSRF token برای همه فرم‌ها
- XSS protection — هرگز `dangerouslySetInnerHTML` بدون sanitize
- SQL injection — فقط Eloquent یا Query Builder، هرگز raw query با concatenation

---

## 🗣️ زبان

- **پاسخ به کاربر:** فارسی (مگه کاربر انگلیسی بپرسه)
- **کامنت کد:** انگلیسی
- **متغیر/فانکشن:** انگلیسی
- **UI متن:** فارسی (RTL)
- **اصطلاحات فنی:** انگلیسی نگه دار (component, hook, API, ...)

---

## ⚠️ نکات حیاتی

1. **قبل از تغییرات بزرگ** — Plan ارائه بده، تأیید بگیر
2. **برای destructive actions** (حذف، drop table، force push) — حتماً اول تأیید کاربر
3. **اگه UI تغییر کرد** — به کاربر بگو خودش تو مرورگر چک کنه
4. **toolchain:** pnpm نه npm، composer نه قبل از artisan
5. **TypeScript strict** — `any` ممنوع مگه ضرورت واقعی
6. **هرگز** فاز آینده رو شروع نکن قبل از تأیید فاز فعلی

---

© YJ19 — 2026

<!-- code-review-graph MCP tools -->
## MCP Tools: code-review-graph

**IMPORTANT: This project has a knowledge graph. ALWAYS use the
code-review-graph MCP tools BEFORE using Grep/Glob/Read to explore
the codebase.** The graph is faster, cheaper (fewer tokens), and gives
you structural context (callers, dependents, test coverage) that file
scanning cannot.

### When to use graph tools FIRST

- **Exploring code**: `semantic_search_nodes` or `query_graph` instead of Grep
- **Understanding impact**: `get_impact_radius` instead of manually tracing imports
- **Code review**: `detect_changes` + `get_review_context` instead of reading entire files
- **Finding relationships**: `query_graph` with callers_of/callees_of/imports_of/tests_for
- **Architecture questions**: `get_architecture_overview` + `list_communities`

Fall back to Grep/Glob/Read **only** when the graph doesn't cover what you need.

### Key Tools

| Tool | Use when |
| ------ | ---------- |
| `detect_changes` | Reviewing code changes — gives risk-scored analysis |
| `get_review_context` | Need source snippets for review — token-efficient |
| `get_impact_radius` | Understanding blast radius of a change |
| `get_affected_flows` | Finding which execution paths are impacted |
| `query_graph` | Tracing callers, callees, imports, tests, dependencies |
| `semantic_search_nodes` | Finding functions/classes by name or keyword |
| `get_architecture_overview` | Understanding high-level codebase structure |
| `refactor_tool` | Planning renames, finding dead code |

### Workflow

1. The graph auto-updates on file changes (via hooks).
2. Use `detect_changes` for code review.
3. Use `get_affected_flows` to understand impact.
4. Use `query_graph` pattern="tests_for" to check coverage.
