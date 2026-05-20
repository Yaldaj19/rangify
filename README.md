<div align="center">

# Rangify

**Wall Recolor Platform — try paint colors on your walls before you buy**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![React](https://img.shields.io/badge/React-18-61DAFB?style=flat&logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?style=flat&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![Inertia](https://img.shields.io/badge/Inertia.js-2-9553E9?style=flat&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=flat&logo=vite&logoColor=white)](https://vitejs.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-06B6D4?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

</div>

---

## 📖 About

**Rangify** is a wall recolor platform built with Laravel + React + Inertia. Upload a photo of a room, pick a paint color, and see how the walls will look — powered by a Python computer-vision pipeline.

---

## 🛠 Tech Stack

### Backend
- **Laravel 11** with PHP 8.2+
- **Inertia.js (Laravel adapter)** for SPA-style routing without a separate API
- **Laravel Sanctum** for authentication
- **Ziggy** for sharing Laravel routes with the frontend
- **Doctrine DBAL** for schema introspection

### Frontend
- **React 18** + **TypeScript 5**
- **Inertia.js React adapter**
- **Tailwind CSS 3** + `@tailwindcss/forms`
- **Headless UI**, **Alpine.js** (lightweight interactions)
- **Vite 6** as the bundler

### Vision Pipeline
- **Python** module under `python-vision/` — handles wall detection and recolor

---

## 🚀 Installation

### 1. Clone & enter
```bash
git clone https://github.com/Yaldaj19/rangify.git
cd rangify
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Install JS dependencies
```bash
npm install
```

### 4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```
Then edit `.env` with your database credentials and any service keys.

### 5. Run database migrations
```bash
php artisan migrate
```

### 6. (Optional) Set up the Python vision module
```bash
cd python-vision
pip install -r requirements.txt   # if present
```

### 7. Start dev servers
In two terminals:
```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (frontend hot reload)
npm run dev
```

Visit `http://localhost:8000`.

---

## 📂 Project Structure

```
rangify/
├── app/                 # Laravel application code
│   ├── Http/            # Controllers, middleware, requests
│   ├── Models/          # Eloquent models
│   └── Providers/
├── bootstrap/           # Framework bootstrap
├── config/              # Laravel config files
├── database/            # Migrations, seeders, factories
├── docs/                # Project documentation
├── public/              # Public webroot
├── python-vision/       # Python CV pipeline
├── resources/
│   ├── js/              # React + TypeScript frontend
│   ├── css/             # Tailwind entry
│   └── views/           # Blade templates (Inertia root)
├── routes/              # web.php, auth.php, console.php
├── storage/             # App storage (logs, cache, sessions)
└── tests/               # PHPUnit feature & unit tests
```

---

## 🧪 Running Tests

```bash
php artisan test
```

---

## ⚙️ Build for Production

```bash
npm run build           # Compile frontend assets
php artisan optimize    # Cache config, routes, views
```

---

## 📋 Requirements

- **PHP 8.2+**
- **Composer 2+**
- **Node.js 18+** and **npm**
- **Python 3.10+** (for the vision module)
- A relational database (MySQL / PostgreSQL / SQLite)

---

## 📜 License

Proprietary — © YJ19. All rights reserved.
