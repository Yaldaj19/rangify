# Rangify — Stack Reference

> این فایل یک نقشه سریع از stack برای ایجنت‌هاست.
> منبع نهایی: [CLAUDE.md](../CLAUDE.md)

## Backend
- PHP 8.2+ / Laravel 11.x
- MySQL 8 / utf8mb4_unicode_ci
- Inertia.js server-side adapter
- Intervention Image v3

## Frontend
- React 18 + TypeScript (strict)
- Inertia.js client
- Vite + pnpm
- Zustand state
- Tailwind 3 + SASS + GSAP
- react-hook-form + zod

## Image Editor
- Fabric.js v6+
- Konva.js
- @techstark/opencv-js

## 3D
- three + @react-three/fiber + @react-three/drei

## AI (فاز ۱۰)
- OpenRouter
  - `google/gemini-2.0-flash-exp:free` (اولویت)
  - `google/gemini-2.5-flash-image`
  - `openai/gpt-4-vision-*`
  - `anthropic/claude-*`

## Convention
- File: kebab-case (general)، PascalCase (Component)، snake_case (migration)
- TypeScript strict، no `any`
- PHP PSR-12 + `declare(strict_types=1)`
- RTL + Vazirmatn فونت
- pnpm (نه npm)
- composer (نه pip و ...)

## محیط
- XAMPP local
- DB: `rangify_db` @ `localhost:3306` / root / (no pass)
- URL: `http://localhost/projects/rangify.site/public/`
