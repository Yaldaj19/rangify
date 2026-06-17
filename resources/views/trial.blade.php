<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/rangify-icon.png') }}">
    <title>ادیتور رنگ — Rangify</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <style>
        /* Periodic attention pulse for the shortcuts button — fires for ~0.8s every 7s */
        @keyframes shortcuts-pulse {
            0%, 88%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.45), 0 4px 6px rgba(0,0,0,0.08); }
            92%           { transform: scale(1.18); box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.18), 0 4px 6px rgba(0,0,0,0.08); }
            96%           { transform: scale(0.96); box-shadow: 0 0 0 14px rgba(59, 130, 246, 0); }
        }
        .shortcuts-pulse { animation: shortcuts-pulse 7s ease-in-out infinite; }
    </style>
</head>
<body class="bg-ink-50 text-ink-900 antialiased h-screen flex flex-col overflow-hidden">

    <header class="border-b border-gray-200 bg-white flex-none">
        <div class="px-6 py-3 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/rangify-logo.png') }}" alt="Rangify" class="h-8 w-auto">
            </a>
            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 transition">بازگشت به داشبورد</a>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-brand-600">ورود</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600 transition">ثبت‌نام</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 flex flex-col md:flex-row-reverse overflow-hidden relative" x-data="editor()"
          x-on:keydown.window="onKeyDown($event)"
          x-on:keyup.window="onKeyUp($event)">

        {{-- Canvas area --}}
        <div class="flex-1 flex flex-col bg-gray-100 relative min-w-0">

            {{-- Upload state --}}
            <div x-show="!imageUrl" class="flex-1 flex items-center justify-center p-6">
                <label
                    class="block w-full max-w-2xl border-2 border-dashed border-gray-300 rounded-2xl p-16 text-center cursor-pointer hover:border-brand-400 hover:bg-white transition"
                    :class="dragging ? 'border-brand-500 bg-brand-50' : ''"
                    x-on:dragenter.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:dragover.prevent
                    x-on:drop.prevent="dragging = false; handleFile($event.dataTransfer.files[0])">
                    <div class="text-6xl mb-4">📷</div>
                    <p class="text-lg font-semibold text-gray-700 mb-2">عکس اتاقت رو اینجا بکش</p>
                    <p class="text-sm text-gray-500 mb-4">یا کلیک کن تا انتخاب کنی</p>
                    <p class="text-xs text-gray-400">JPG / PNG / WebP — حداکثر 10MB</p>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                           x-on:change="handleFile($event.target.files[0])">
                </label>
            </div>

            {{-- Canvas state --}}
            <div x-show="imageUrl"
                 x-ref="viewport"
                 x-on:wheel.prevent="onWheel($event)"
                 class="flex-1 flex items-center justify-center p-6 overflow-hidden relative">
                <div class="relative"
                     :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); transform-origin: center center; transition: ${isPanning ? 'none' : 'transform 100ms ease-out'};`">
                    <canvas x-ref="canvas"
                            x-on:click="onCanvasClick($event)"
                            x-on:mousedown="onMouseDown($event)"
                            x-on:mousemove="onMouseMove($event)"
                            x-on:mouseup="onMouseUp($event)"
                            x-on:mouseleave="onMouseLeave($event)"
                            x-on:touchstart.prevent="onTouchStart($event)"
                            x-on:touchmove.prevent="onTouchMove($event)"
                            x-on:touchend.prevent="onTouchEnd($event)"
                            x-on:touchcancel.prevent="onTouchEnd($event)"
                            x-on:contextmenu.prevent
                            :style="`${canvasCursor()}; touch-action: none;`"
                            class="rounded-xl shadow-lg bg-white select-none block"></canvas>
                </div>

                {{-- Loading overlay --}}
                <div x-show="loading"
                     class="absolute inset-0 flex items-center justify-center bg-black/40">
                    <div class="bg-white rounded-lg px-4 py-3 flex items-center gap-3 shadow-xl">
                        <div class="w-5 h-5 border-2 border-brand-500 border-t-transparent rounded-full animate-spin"></div>
                        <span x-text="loadingText" class="text-sm font-medium"></span>
                    </div>
                </div>

                {{-- Mobile FAB: open sidebar --}}
                <button x-show="imageUrl && !sidebarOpen"
                        x-on:click="sidebarOpen = true"
                        class="md:hidden fixed bottom-20 right-4 z-20 w-14 h-14 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-2xl flex items-center justify-center text-2xl active:scale-95 transition"
                        title="باز کردن پنل ادیتور">
                    🎨
                </button>

                {{-- Shortcuts hint card (collapsible, bottom-left) --}}
                <div x-show="imageUrl" class="absolute bottom-3 left-3 z-10">
                    <button x-on:click="showShortcuts = !showShortcuts"
                            :class="[
                                showShortcuts ? 'bg-brand-500 text-white' : 'bg-white/90 text-gray-700 hover:bg-white',
                                (!showShortcuts && imageUrl) ? 'shortcuts-pulse' : ''
                            ]"
                            class="w-9 h-9 rounded-full backdrop-blur border border-gray-200 shadow-md flex items-center justify-center text-sm font-bold transition"
                            title="میانبرها">⌨</button>
                    <div x-show="showShortcuts" x-transition.opacity
                         x-on:click.outside="showShortcuts = false"
                         class="absolute bottom-11 left-0 w-64 bg-white/95 backdrop-blur rounded-xl border border-gray-200 shadow-xl p-3">
                        <div class="text-xs font-bold text-gray-700 mb-2 flex items-center justify-between">
                            <span>⌨ میانبرهای صفحه‌کلید</span>
                            <button x-on:click="showShortcuts = false" class="text-gray-400 hover:text-gray-700 text-lg leading-none">×</button>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">بازگشت (Undo)</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">Ctrl+Z</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">جلو رفتن (Redo)</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">Ctrl+Y</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">جابجایی (موقت)</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">Space</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">زوم</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">چرخ ماوس</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">جابجایی (همیشگی)</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">میانه/راست-کلیک</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">حذف از انتخاب</span>
                                <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-[10px]">Alt+کلیک</kbd>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-100 text-[10px] text-gray-400 leading-relaxed">
                            💡 همه این میانبرها در نوار پایین هم به‌صورت دکمه موجودن.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom toolbar --}}
            <div x-show="imageUrl"
                 class="flex-none bg-gradient-to-r from-gray-50 via-white to-gray-50 border-t-2 border-gray-200 px-4 py-2.5 flex items-center gap-2.5 text-sm shadow-[0_-4px_12px_rgba(0,0,0,0.04)]">

                {{-- Navigation group: zoom + pan --}}
                <div class="flex items-center bg-white rounded-lg border border-gray-300 overflow-hidden shadow-sm">
                    <button x-on:click="zoomBy(0.8)" class="w-9 h-9 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 text-lg font-bold text-gray-700 transition" title="کوچک‌نمایی">−</button>
                    <div class="text-[11px] text-center text-gray-800 px-2 py-1 select-none font-mono font-semibold min-w-[3rem] border-x border-gray-200 bg-gray-50" x-text="Math.round(zoom * 100) + '%'"></div>
                    <button x-on:click="zoomBy(1.25)" class="w-9 h-9 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 text-lg font-bold text-gray-700 transition" title="بزرگ‌نمایی">+</button>
                    <button x-on:click="zoomFit()" class="w-9 h-9 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 text-sm text-gray-700 border-r border-gray-200 transition" title="اندازه صفحه">⛶</button>
                    <button x-on:click="togglePan()"
                            :class="panLocked ? 'bg-brand-500 text-white' : 'hover:bg-brand-50 hover:text-brand-600 text-gray-700'"
                            class="w-9 h-9 flex items-center justify-center text-base border-r border-gray-200 transition"
                            :title="panLocked ? 'حالت دست فعاله — کلیک برای خروج' : 'دست (جابجایی) — یا Space نگه دار'">✋</button>
                </div>

                {{-- Undo / Redo group --}}
                <div class="flex items-center bg-white rounded-lg border border-gray-300 overflow-hidden shadow-sm">
                    <button x-on:click="undo()" :disabled="!canUndo"
                            :class="canUndo ? 'text-gray-700 hover:bg-brand-50 hover:text-brand-600' : 'text-gray-300 cursor-not-allowed'"
                            class="w-9 h-9 flex items-center justify-center text-lg transition"
                            title="بازگشت — Ctrl+Z">↶</button>
                    <button x-on:click="redo()" :disabled="!canRedo"
                            :class="canRedo ? 'text-gray-700 hover:bg-brand-50 hover:text-brand-600' : 'text-gray-300 cursor-not-allowed'"
                            class="w-9 h-9 flex items-center justify-center text-lg border-r border-gray-200 transition"
                            title="جلو — Ctrl+Y">↷</button>
                </div>

                {{-- Inline action buttons --}}
                <div class="flex items-center gap-1">
                    <button x-on:click="clearMask()" x-show="hasMask"
                            class="px-2.5 h-9 rounded-md bg-white border border-gray-300 text-xs text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 flex items-center gap-1 shadow-sm transition"
                            title="پاک‌کردن انتخاب">
                        ✖ <span>پاک</span>
                    </button>
                    <button x-on:click="resetColor()"
                            class="px-2.5 h-9 rounded-md bg-white border border-gray-300 text-xs text-gray-700 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 flex items-center gap-1 shadow-sm transition"
                            title="برگشت به اصل">
                        ↺ <span>اصل</span>
                    </button>
                    <button x-on:mousedown="showBefore(true)"
                            x-on:mouseup="showBefore(false)"
                            x-on:mouseleave="showBefore(false)"
                            x-on:touchstart.prevent="showBefore(true)"
                            x-on:touchend="showBefore(false)"
                            class="px-2.5 h-9 rounded-md bg-white border border-gray-300 text-xs text-gray-700 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 flex items-center gap-1 select-none shadow-sm transition"
                            title="نگه‌دار تا تصویر اصلی رو ببینی">
                        👁 <span>قبل/بعد</span>
                    </button>
                </div>

                {{-- Status: centered --}}
                <div class="flex-1 text-center text-xs text-gray-600 truncate font-medium" x-text="statusText"></div>

                {{-- Right side: primary actions --}}
                <button x-on:click="downloadResult()"
                        class="px-3.5 h-9 rounded-md bg-gradient-to-r from-brand-500 to-brand-600 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm hover:shadow-md transition"
                        title="دانلود PNG">
                    📥 <span>دانلود</span>
                </button>
                <button x-on:click="reset()"
                        class="px-2.5 h-9 rounded-md bg-white border border-gray-300 text-xs text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 flex items-center gap-1 shadow-sm transition"
                        title="عکس جدید">
                    🗑️ <span>جدید</span>
                </button>
            </div>
        </div>

        {{-- Sidebar — desktop: right column 360px / mobile: bottom-sheet --}}
        {{-- Backdrop for mobile when open --}}
        <div x-show="sidebarOpen && imageUrl"
             x-on:click="sidebarOpen = false"
             x-transition.opacity
             class="md:hidden fixed inset-0 bg-black/30 z-30"></div>

        <aside
            :class="(sidebarOpen || !imageUrl) ? 'translate-y-0' : 'translate-y-full md:translate-y-0'"
            class="w-full md:w-[360px] flex-none bg-gradient-to-b from-gray-50 to-white border-l border-gray-200 overflow-y-auto
                   fixed md:relative bottom-0 left-0 right-0 z-40 md:z-auto
                   max-h-[80vh] md:max-h-full rounded-t-2xl md:rounded-none shadow-2xl md:shadow-none
                   transition-transform duration-300 ease-out">

            {{-- Mobile drag handle --}}
            <div class="md:hidden sticky top-0 bg-white/95 backdrop-blur z-10 pt-2 pb-1 px-4 border-b border-gray-100">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-2"
                     x-on:click="sidebarOpen = false"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-700">پنل ادیتور</span>
                    <button x-on:click="sidebarOpen = false" class="text-gray-500 text-xl leading-none w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">×</button>
                </div>
            </div>

            <div class="p-4 space-y-4">

                {{-- Mode selector --}}
                <section class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">۱. ابزار</h3>
                    <div class="grid grid-cols-5 gap-1">
                        <button x-on:click="switchMode('smart')"
                                :class="mode === 'smart' ? 'bg-brand-500 text-white shadow ring-2 ring-brand-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                class="rounded-lg px-0.5 py-2 text-[10px] font-medium transition flex flex-col items-center gap-0.5"
                                title="انتخاب هوشمند (Felzenszwalb) — هاور برای پیش‌نمایش، کلیک برای انتخاب">
                            <span class="text-base leading-none">🧠</span><span>هوشمند</span>
                        </button>
                        <button x-on:click="switchMode('wand')"
                                :class="mode === 'wand' ? 'bg-brand-500 text-white shadow ring-2 ring-brand-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                class="rounded-lg px-0.5 py-2 text-[10px] font-medium transition flex flex-col items-center gap-0.5"
                                title="عصای جادو — flood fill، رنگ مشابه فلود می‌شه">
                            <span class="text-base leading-none">🪄</span><span>عصا</span>
                        </button>
                        <button x-on:click="switchMode('brush')"
                                :class="mode === 'brush' ? 'bg-brand-500 text-white shadow ring-2 ring-brand-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                class="rounded-lg px-0.5 py-2 text-[10px] font-medium transition flex flex-col items-center gap-0.5"
                                title="قلم‌مو — drag، Alt+drag = حذف از انتخاب">
                            <span class="text-base leading-none">🖌</span><span>قلم‌مو</span>
                        </button>
                        <button x-on:click="switchMode('eraser')"
                                :class="mode === 'eraser' ? 'bg-red-500 text-white shadow ring-2 ring-red-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                class="rounded-lg px-0.5 py-2 text-[10px] font-medium transition flex flex-col items-center gap-0.5"
                                title="پاک‌کن — drag روی رنگ‌شده تا برگرده به اصل">
                            <span class="text-base leading-none">🧽</span><span>پاک‌کن</span>
                        </button>
                        <button x-on:click="switchMode('auto')"
                                :class="mode === 'auto' ? 'bg-brand-500 text-white shadow ring-2 ring-brand-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                class="rounded-lg px-0.5 py-2 text-[10px] font-medium transition flex flex-col items-center gap-0.5"
                                title="کشف خودکار — AI همه دیوارها رو یکجا پیدا می‌کنه">
                            <span class="text-base leading-none">🤖</span><span>خودکار</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed" x-show="mode === 'smart'">
                        🧠 <strong>ماوس رو روی تصویر ببر</strong> — نواحی با رنگ زرد پیش‌نمایش می‌شن. کلیک = انتخاب، Alt+کلیک = حذف.
                        <span class="block mt-1 text-[10px] text-gray-400">الگوریتم Felzenszwalb + ادغام رنگ LAB (بدون AI، local).</span>
                        <span x-show="!precomputeReady && precomputing" class="block mt-1 text-amber-600">⏳ در حال آماده‌سازی...</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed" x-show="mode === 'wand'">
                        🪄 روی هر نقطه کلیک کن — پیکسل‌های هم‌رنگ متصل انتخاب می‌شن (flood-fill).
                    </p>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed" x-show="mode === 'brush'">
                        🖌 mouse-down + drag روی ناحیه — هر جا قلم رد شد به انتخاب اضافه می‌شه. Alt+drag = حذف.
                    </p>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed" x-show="mode === 'eraser'">
                        🧽 روی هر بخش رنگ‌شده drag کن — رنگش حذف می‌شه و برمی‌گرده به تصویر اصلی.
                    </p>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed" x-show="mode === 'auto'">
                        🤖 <strong>AI</strong> (Gemini Vision) همه دیوارها و سقف رو یکجا پیدا می‌کنه — نیاز به اتصال اینترنت.
                    </p>

                    {{-- Auto AI trigger --}}
                    <button x-show="mode === 'auto' && imageUrl"
                            x-on:click="runAiSegmentation()"
                            :disabled="loading"
                            class="w-full mt-3 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50 transition">
                        🪄 کشف خودکار دیوار/سقف
                    </button>

                    {{-- Tolerance for wand --}}
                    <div x-show="mode === 'wand' && imageUrl" class="mt-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-600">حساسیت</span>
                            <span class="text-xs font-mono text-brand-600" x-text="tolerance"></span>
                        </div>
                        <input type="range" min="5" max="80" x-model.number="tolerance" class="w-full accent-brand-500">
                        <div class="flex justify-between text-[10px] text-gray-400 mt-0.5">
                            <span>دقیق</span>
                            <span>گسترده</span>
                        </div>
                    </div>

                    {{-- Brush / Eraser size (shared slider) --}}
                    <div x-show="(mode === 'brush' || mode === 'eraser') && imageUrl" class="mt-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-600" x-text="mode === 'eraser' ? 'سایز پاک‌کن' : 'سایز قلم'"></span>
                            <span class="text-xs font-mono" :class="mode === 'eraser' ? 'text-red-600' : 'text-brand-600'" x-text="brushSize + ' px'"></span>
                        </div>
                        <input type="range" min="6" max="80" x-model.number="brushSize"
                               class="w-full"
                               :class="mode === 'eraser' ? 'accent-red-500' : 'accent-brand-500'">
                    </div>
                </section>

                {{-- Color picker --}}
                <section class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">۲. انتخاب رنگ</h3>

                    {{-- Big preview swatch + native picker + hex + eyedropper, in two clean rows --}}
                    <div class="flex items-stretch gap-2">
                        <label class="relative w-14 h-14 rounded-lg border-2 border-gray-200 flex-none overflow-hidden cursor-pointer shadow-inner ring-1 ring-black/5"
                               :style="`background-color: ${selectedColor}`"
                               title="کلیک: انتخاب رنگ از پنل سیستم">
                            <input type="color" x-model="selectedColor"
                                   class="absolute inset-0 opacity-0 cursor-pointer">
                        </label>
                        <div class="flex-1 flex flex-col gap-1 min-w-0">
                            <input type="text" x-model="selectedColor"
                                   x-on:blur="normalizeHex()"
                                   x-on:keydown.enter="normalizeHex()"
                                   dir="ltr"
                                   maxlength="7"
                                   class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm font-mono uppercase text-center focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                            <button x-on:click="pickerActive = !pickerActive"
                                    :class="pickerActive ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-white'"
                                    class="w-full rounded-md border px-2 py-1 text-[11px] font-medium flex items-center justify-center gap-1 transition">
                                <span>💧</span>
                                <span x-text="pickerActive ? 'کلیک روی تصویر' : 'قطره‌چکان'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Texture toggle --}}
                    <div class="mt-2.5 pt-2.5 border-t border-gray-100">
                        <div class="text-[10px] font-semibold text-gray-500 mb-1.5">حالت رنگ</div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button x-on:click="preserveTexture = true"
                                    :class="preserveTexture ? 'bg-brand-500 text-white border-brand-500 shadow' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="rounded-md border px-2 py-1.5 text-[11px] font-medium transition flex flex-col items-center gap-0.5"
                                    title="بافت دیوار، سایه‌ها و طرح‌های روی دیوار قابل مشاهده می‌مونن">
                                <span>🪵 رنگ + بافت</span>
                                <span class="text-[9px] opacity-75">طرح‌ها دیده می‌شن</span>
                            </button>
                            <button x-on:click="preserveTexture = false"
                                    :class="!preserveTexture ? 'bg-brand-500 text-white border-brand-500 shadow' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="rounded-md border px-2 py-1.5 text-[11px] font-medium transition flex flex-col items-center gap-0.5"
                                    title="رنگ کاملاً یکدست، بدون سایه و بافت دیوار">
                                <span>🎨 رنگ تخت</span>
                                <span class="text-[9px] opacity-75">طرح‌ها پوشیده می‌شن</span>
                            </button>
                        </div>
                    </div>

                    {{-- Recent colors --}}
                    <div x-show="recentColors.length > 0" class="mt-2.5 pt-2.5 border-t border-gray-100">
                        <div class="text-[10px] font-semibold text-gray-500 mb-1">رنگ‌های اخیر</div>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="c in recentColors" :key="c">
                                <button x-on:click="selectedColor = c"
                                        :style="`background-color: ${c}`"
                                        :title="c"
                                        class="w-6 h-6 rounded border border-gray-200 hover:scale-110 transition"></button>
                            </template>
                        </div>
                    </div>
                </section>

                {{-- Palette groups — compact rows --}}
                <section class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">۳. پالت‌های آماده</h3>
                    <div class="space-y-1.5">
                        <template x-for="group in paletteGroups" :key="group.name">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-gray-500 w-10 flex-none text-left" x-text="group.name"></span>
                                <div class="flex-1 grid grid-cols-6 gap-1">
                                    <template x-for="c in group.colors" :key="c.hex">
                                        <button x-on:click="selectedColor = c.hex"
                                                :style="`background-color: ${c.hex}`"
                                                :class="selectedColor.toLowerCase() === c.hex.toLowerCase() ? 'ring-2 ring-offset-1 ring-brand-500 scale-105' : ''"
                                                class="w-full aspect-square rounded-md border border-gray-200 hover:scale-110 transition"
                                                :title="`${c.name} — ${c.hex}`"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                {{-- Apply button --}}
                <section>
                    <button x-on:click="applyColor()"
                            :disabled="!hasMask"
                            class="w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-3.5 text-sm font-bold text-white shadow-md hover:shadow-lg disabled:opacity-40 disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-400 disabled:shadow-none transition">
                        🎨 اعمال رنگ روی بخش انتخاب‌شده
                    </button>
                    <p x-show="!hasMask" class="text-xs text-gray-400 mt-2 text-center">
                        اول یه بخش انتخاب کن
                    </p>
                </section>

                {{-- CTA --}}
                <section class="bg-brand-50/60 rounded-xl border border-brand-100 p-3">
                    <p class="text-xs text-gray-700 mb-2 leading-relaxed">
                        💡 برای ذخیره پروژه‌ها و خروجی با کیفیت بالا ثبت‌نام کن.
                    </p>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center rounded-lg bg-white border border-brand-500 text-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-50 transition">
                        ثبت‌نام رایگان
                    </a>
                </section>

            </div>
        </aside>
    </main>

    <script>
        function editor() {
            return {
                imageUrl: null,
                originalImageData: null,
                coloredImageData: null,
                mask: null,
                maskCount: 0,
                dragging: false,
                mode: 'smart',
                tolerance: 30,
                brushSize: 24,
                loading: false,
                loadingText: '',
                statusText: '',
                selectedColor: '#3B82F6',
                isBrushing: false,
                brushSubtract: false,
                lastBrushPoint: null,
                _renderQueued: false,
                zoom: 1,
                panX: 0,
                panY: 0,
                isPanning: false,
                panStart: null,
                pickerActive: false,
                // ---- hover-preview state (smart mode) ----
                labelMap: null,        // Uint8Array (lw*lh) of region ids; 0 = bg
                labelW: 0,
                labelH: 0,
                labelScaleX: 1,        // label-map width / canvas width
                labelScaleY: 1,
                regionInfo: [],        // [{id, area, centroid:[x,y], ...}]
                hoverLabel: 0,         // currently hovered region id
                precomputing: false,
                precomputeReady: false,
                // ---- undo/redo history ----
                historyStack: [],      // array of {mask: Uint8Array, count: number}
                historyIndex: -1,
                historyLimit: 30,
                spacePan: false,       // is space-bar currently held for temp pan
                panLocked: false,      // pan-tool toggle from bottom toolbar (independent of selection mode)
                showShortcuts: false,  // shortcuts hint card visibility
                shortcutsAutoShown: false, // one-shot guard for auto-open
                sidebarOpen: window.innerWidth >= 768, // desktop: always; mobile: closed by default
                // ---- touch state ----
                touchMode: null,       // 'select' | 'pan' | 'pinch'
                touchStart: null,      // { x, y, panX, panY, dist?, zoom?, tapTime, tapX, tapY }
                lastTouchTap: 0,       // for tap-vs-drag discrimination
                // ---- paint memory: lets us match existing color when filling gaps ----
                paintedMask: null,     // Uint8Array — 1 = this pixel has ever been painted
                lastAppliedColor: null,// last hex color the user applied (lowercased)
                preserveTexture: true, // true = keep wall texture/shadows; false = flat color
                recentColors: [],
                paletteGroups: [
                    {
                        name: 'نوترال', colors: [
                            { hex: '#FFFFFF', name: 'سفید مات' },
                            { hex: '#F5F1EB', name: 'استخوانی' },
                            { hex: '#E8E2D6', name: 'بژ روشن' },
                            { hex: '#C8C0B0', name: 'شنی' },
                            { hex: '#8E867A', name: 'خاکستری گرم' },
                            { hex: '#3E3A35', name: 'زغالی' },
                        ],
                    },
                    {
                        name: 'گرم', colors: [
                            { hex: '#FAE3D9', name: 'هلویی' },
                            { hex: '#F4B6A1', name: 'نسکافه‌ای' },
                            { hex: '#E07A5F', name: 'مرجانی' },
                            { hex: '#C9485B', name: 'گل‌ سرخی' },
                            { hex: '#8B3A3A', name: 'گوجه‌ای' },
                            { hex: '#D4A574', name: 'کاراملی' },
                        ],
                    },
                    {
                        name: 'سرد', colors: [
                            { hex: '#D6E4EC', name: 'آسمانی روشن' },
                            { hex: '#A3C4D9', name: 'آبی پودری' },
                            { hex: '#5B8BA8', name: 'فیروزه‌ای' },
                            { hex: '#264653', name: 'سرمه‌ای عمیق' },
                            { hex: '#B5C9B0', name: 'سبز سیج' },
                            { hex: '#3D6B5F', name: 'یشمی' },
                        ],
                    },
                    {
                        name: 'پاستل', colors: [
                            { hex: '#FDE2E4', name: 'صورتی روشن' },
                            { hex: '#FAD2E1', name: 'گل‌محمدی' },
                            { hex: '#E2ECE9', name: 'نعنایی' },
                            { hex: '#F0EFEB', name: 'ابری' },
                            { hex: '#DBE7E4', name: 'یخی' },
                            { hex: '#FFF1E6', name: 'وانیلی' },
                        ],
                    },
                ],

                get hasMask() {
                    return this.maskCount > 0;
                },

                get canUndo() {
                    return this.historyIndex > 0;
                },

                get canRedo() {
                    return this.historyIndex >= 0 && this.historyIndex < this.historyStack.length - 1;
                },

                /**
                 * Snapshot current editor state onto the undo stack. Call BEFORE mutating.
                 * Snapshots: mask + maskCount + coloredImageData + paintedMask.
                 * Heavier than mask-only but lets us undo color application AND eraser too.
                 */
                pushHistory() {
                    if (!this.mask) return;
                    // Drop any redo branch
                    if (this.historyIndex < this.historyStack.length - 1) {
                        this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
                    }
                    this.historyStack.push({
                        mask: new Uint8Array(this.mask),
                        count: this.maskCount,
                        coloredData: this.coloredImageData ? new Uint8ClampedArray(this.coloredImageData.data) : null,
                        paintedMask: this.paintedMask ? new Uint8Array(this.paintedMask) : null,
                        lastAppliedColor: this.lastAppliedColor,
                    });
                    if (this.historyStack.length > this.historyLimit) {
                        this.historyStack.shift();
                    }
                    this.historyIndex = this.historyStack.length - 1;
                },

                restoreSnapshot(snap) {
                    this.mask = new Uint8Array(snap.mask);
                    this.maskCount = snap.count;
                    if (snap.coloredData && this.coloredImageData) {
                        this.coloredImageData.data.set(snap.coloredData);
                    }
                    if (snap.paintedMask) {
                        this.paintedMask = new Uint8Array(snap.paintedMask);
                    }
                    this.lastAppliedColor = snap.lastAppliedColor;
                    this.renderCanvas();
                },

                undo() {
                    if (!this.canUndo) return;
                    this.historyIndex--;
                    this.restoreSnapshot(this.historyStack[this.historyIndex]);
                    this.statusText = `↶ undo`;
                },

                redo() {
                    if (!this.canRedo) return;
                    this.historyIndex++;
                    this.restoreSnapshot(this.historyStack[this.historyIndex]);
                    this.statusText = `↷ redo`;
                },

                resetHistory() {
                    this.historyStack = [];
                    this.historyIndex = -1;
                    // Seed with empty mask state
                    if (this.mask) this.pushHistory();
                },

                togglePan() {
                    this.panLocked = !this.panLocked;
                    if (this.hoverLabel !== 0) {
                        this.hoverLabel = 0;
                        if (this.coloredImageData) this.renderCanvas();
                    }
                },

                handleFile(file) {
                    if (!file || !file.type.startsWith('image/')) return;
                    if (file.size > 10 * 1024 * 1024) {
                        alert('حجم فایل نباید بیشتر از 10MB باشد.');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.imageUrl = e.target.result;
                        this.$nextTick(() => this.loadToCanvas());
                    };
                    reader.readAsDataURL(file);
                },

                loadToCanvas() {
                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');
                    const img = new Image();
                    img.onload = () => {
                        const maxW = window.innerWidth - 360 - 48;
                        const maxH = window.innerHeight - 200;
                        const ratio = Math.min(maxW / img.width, maxH / img.height, 1);
                        canvas.width = Math.round(img.width * ratio);
                        canvas.height = Math.round(img.height * ratio);
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        this.coloredImageData = new ImageData(
                            new Uint8ClampedArray(this.originalImageData.data),
                            canvas.width, canvas.height
                        );
                        this.mask = new Uint8Array(canvas.width * canvas.height);
                        this.maskCount = 0;
                        this.paintedMask = new Uint8Array(canvas.width * canvas.height);
                        this.lastAppliedColor = null;
                        this.statusText = `${canvas.width}×${canvas.height} px`;
                        this.zoomFit();
                        this.resetHistory();
                        // First image of the session → auto-open shortcuts so user discovers them
                        if (!this.shortcutsAutoShown) {
                            this.shortcutsAutoShown = true;
                            setTimeout(() => {
                                this.showShortcuts = true;
                                // Auto-close after 6 seconds so it doesn't block the workflow
                                setTimeout(() => { this.showShortcuts = false; }, 6000);
                            }, 700);
                        }
                        // Reset hover-preview state and kick off precompute in background
                        this.labelMap = null;
                        this.labelW = 0;
                        this.labelH = 0;
                        this.regionInfo = [];
                        this.hoverLabel = 0;
                        this.precomputeReady = false;
                        // پیش‌محاسبه‌ی label-map (Felzenszwalb) کیفیت انتخابش از GrabCut پایین‌تر است،
                        // پس غیرفعال شد تا کلیک‌های حالت هوشمند از GrabCut دقیقِ سمت سرور استفاده کنند.
                        // (برای فعال‌سازی دوباره‌ی پیش‌نمایش فوری، این خط را برگردانید.)
                        // this.precomputeRegions();
                    };
                    img.src = this.imageUrl;
                },

                async precomputeRegions() {
                    if (this.precomputing) return;
                    this.precomputing = true;
                    this.statusText = '🔍 در حال آماده‌سازی پیش‌نمایش هوشمند...';
                    try {
                        const w = this.originalImageData.width;
                        const h = this.originalImageData.height;
                        const temp = document.createElement('canvas');
                        temp.width = w;
                        temp.height = h;
                        temp.getContext('2d').putImageData(this.originalImageData, 0, 0);
                        const imageDataUrl = temp.toDataURL('image/jpeg', 0.85);

                        const res = await fetch('{{ route('ai.precompute') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ image: imageDataUrl }),
                        });
                        if (!res.ok) {
                            // Service likely down — silently skip; smart mode falls back to per-click API
                            this.statusText = `${w}×${h} px (پیش‌نمایش غیرفعال — سرویس پایتون بالا نیست)`;
                            return;
                        }
                        const payload = await res.json();
                        if (!payload?.label_map) return;

                        // Decode the label-map PNG to a Uint8Array of region ids
                        const lw = payload.width, lh = payload.height;
                        const lmImg = new Image();
                        await new Promise((resolve, reject) => {
                            lmImg.onload = resolve;
                            lmImg.onerror = reject;
                            lmImg.src = payload.label_map;
                        });
                        const lc = document.createElement('canvas');
                        lc.width = lw; lc.height = lh;
                        const lctx = lc.getContext('2d');
                        lctx.drawImage(lmImg, 0, 0);
                        const lmData = lctx.getImageData(0, 0, lw, lh).data;
                        const labels = new Uint8Array(lw * lh);
                        for (let i = 0, p = 0; i < lw * lh; i++, p += 4) {
                            labels[i] = lmData[p]; // R channel = label id (single-channel PNG decodes to grayscale in R)
                        }
                        this.labelMap = labels;
                        this.labelW = lw;
                        this.labelH = lh;
                        this.labelScaleX = lw / w;
                        this.labelScaleY = lh / h;
                        this.regionInfo = payload.regions || [];
                        this.precomputeReady = true;
                        this.statusText = `✨ ${this.regionInfo.length} ناحیه شناسایی شد — رو هر کدوم بِبر تا ببینی`;
                    } catch (e) {
                        // non-fatal
                        console.warn('precompute failed', e);
                    } finally {
                        this.precomputing = false;
                    }
                },

                labelAtCanvas(x, y) {
                    if (!this.labelMap) return 0;
                    const lx = Math.min(this.labelW - 1, Math.max(0, Math.floor(x * this.labelScaleX)));
                    const ly = Math.min(this.labelH - 1, Math.max(0, Math.floor(y * this.labelScaleY)));
                    return this.labelMap[ly * this.labelW + lx];
                },

                eventToCanvasXY(e) {
                    const canvas = this.$refs.canvas;
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: Math.floor((e.clientX - rect.left) * (canvas.width / rect.width)),
                        y: Math.floor((e.clientY - rect.top) * (canvas.height / rect.height)),
                    };
                },

                onCanvasClick(e) {
                    if (!this.originalImageData) return;
                    // Pan lock or Space held: clicks just navigate, no selection
                    if (this.panLocked || this.spacePan) return;
                    const { x, y } = this.eventToCanvasXY(e);

                    if (this.pickerActive) {
                        const data = this.coloredImageData.data;
                        const k = (y * this.coloredImageData.width + x) * 4;
                        const toHex = (n) => n.toString(16).padStart(2, '0').toUpperCase();
                        this.selectedColor = '#' + toHex(data[k]) + toHex(data[k + 1]) + toHex(data[k + 2]);
                        this.pickerActive = false;
                        this.statusText = `رنگ ${this.selectedColor} انتخاب شد`;
                        return;
                    }

                    if (this.mode === 'wand') {
                        this.pushHistory();
                        const added = this.floodFillFromPoint(x, y, this.tolerance);
                        this.maskCount += added;
                        this.renderCanvas();
                        if (added > 0) {
                            this.statusText = `${this.maskCount.toLocaleString('fa-IR')} پیکسل انتخاب شده`;
                        }
                        return;
                    }

                    if (this.mode === 'smart') {
                        // If we have a precomputed label-map, use the hovered region
                        // for an INSTANT selection — no API call needed.
                        if (this.precomputeReady) {
                            const lbl = this.labelAtCanvas(x, y);
                            if (lbl > 0) {
                                this.pushHistory();
                                const added = this.commitHoverRegion(lbl, e.altKey);
                                if (added !== 0) {
                                    this.statusText = `✅ ناحیه ${lbl} ${e.altKey ? 'حذف شد' : 'افزوده شد'} — ${this.maskCount.toLocaleString('fa-IR')} پیکسل`;
                                } else {
                                    // No change → drop the snapshot we just pushed
                                    this.historyStack.pop();
                                    this.historyIndex = this.historyStack.length - 1;
                                }
                                return;
                            }
                        }
                        // Fallback: precompute failed/in-progress → call the API
                        this.smartSelectAt(x, y);
                    }
                },

                /**
                 * Commit the hovered region to the persistent mask.
                 * Alt-click subtracts. Returns delta in selected-pixel count.
                 */
                commitHoverRegion(label, subtract = false) {
                    if (!this.labelMap || label === 0 || !this.mask) return 0;
                    const w = this.coloredImageData.width;
                    const h = this.coloredImageData.height;
                    const setVal = subtract ? 0 : 1;
                    let delta = 0;
                    for (let y = 0; y < h; y++) {
                        // map canvas y → label y once per row
                        const ly = Math.min(this.labelH - 1, Math.floor(y * this.labelScaleY));
                        const rowOff = ly * this.labelW;
                        for (let x = 0; x < w; x++) {
                            const lx = Math.min(this.labelW - 1, Math.floor(x * this.labelScaleX));
                            if (this.labelMap[rowOff + lx] !== label) continue;
                            const i = y * w + x;
                            if (this.mask[i] !== setVal) {
                                this.mask[i] = setVal;
                                delta += setVal === 1 ? 1 : -1;
                            }
                        }
                    }
                    this.maskCount = Math.max(0, this.maskCount + delta);
                    this.hoverLabel = 0; // clear ghost so confirmed selection is visible
                    this.renderCanvas();
                    return delta;
                },

                onMouseDown(e) {
                    // Pan triggers: middle/right click (any mode), OR left-click when pan is locked / Space held
                    const isPanRequest = e.button === 1 || e.button === 2 ||
                        (e.button === 0 && (this.panLocked || this.spacePan));
                    if (isPanRequest) {
                        e.preventDefault();
                        this.isPanning = true;
                        this.panStart = { x: e.clientX, y: e.clientY, panX: this.panX, panY: this.panY };
                        return;
                    }
                    if ((this.mode !== 'brush' && this.mode !== 'eraser') || !this.originalImageData) return;
                    e.preventDefault();
                    this.pushHistory(); // snapshot before stroke begins
                    this.isBrushing = true;
                    this.brushSubtract = (this.mode === 'brush') && e.altKey;
                    const { x, y } = this.eventToCanvasXY(e);
                    if (this.mode === 'eraser') {
                        this.eraseAt(x, y);
                    } else {
                        this.paintBrushAt(x, y);
                    }
                    this.scheduleRender(); // show the first dot immediately
                    this.lastBrushPoint = { x, y };
                },

                onMouseMove(e) {
                    if (this.isPanning && this.panStart) {
                        this.panX = this.panStart.panX + (e.clientX - this.panStart.x);
                        this.panY = this.panStart.panY + (e.clientY - this.panStart.y);
                        return;
                    }
                    // Smart-mode hover preview: highlight region under cursor (skip if pan is active)
                    if (this.mode === 'smart' && this.precomputeReady && !this.isBrushing && !this.panLocked && !this.spacePan) {
                        const { x, y } = this.eventToCanvasXY(e);
                        const lbl = this.labelAtCanvas(x, y);
                        if (lbl !== this.hoverLabel) {
                            this.hoverLabel = lbl;
                            this.scheduleRender();
                        }
                        return;
                    }
                    if (!this.isBrushing) return;
                    e.preventDefault();
                    const { x, y } = this.eventToCanvasXY(e);
                    const isEraser = this.mode === 'eraser';
                    const paintFn = isEraser ? (px, py) => this.eraseAt(px, py) : (px, py) => this.paintBrushAt(px, py);
                    // Interpolate between last and current point so fast strokes don't leave gaps
                    if (this.lastBrushPoint) {
                        const dx = x - this.lastBrushPoint.x;
                        const dy = y - this.lastBrushPoint.y;
                        const dist = Math.hypot(dx, dy);
                        const step = Math.max(1, this.brushSize / 3);
                        const n = Math.max(1, Math.ceil(dist / step));
                        for (let i = 1; i <= n; i++) {
                            const t = i / n;
                            paintFn(
                                Math.round(this.lastBrushPoint.x + dx * t),
                                Math.round(this.lastBrushPoint.y + dy * t)
                            );
                        }
                    } else {
                        paintFn(x, y);
                    }
                    this.lastBrushPoint = { x, y };
                    this.scheduleRender();
                },

                onMouseUp(e) {
                    if (this.isPanning) {
                        this.isPanning = false;
                        this.panStart = null;
                        return;
                    }
                    if (!this.isBrushing) return;
                    this.isBrushing = false;
                    this.lastBrushPoint = null;
                    this.renderCanvas();
                    if (this.maskCount > 0) {
                        this.statusText = `${this.maskCount.toLocaleString('fa-IR')} پیکسل انتخاب شده`;
                    }
                },

                onMouseLeave(e) {
                    // Behaves like mouseup but also clears smart-mode hover ghost
                    this.onMouseUp(e);
                    if (this.hoverLabel !== 0) {
                        this.hoverLabel = 0;
                        this.renderCanvas();
                    }
                },

                onKeyDown(e) {
                    // Ignore when typing in inputs
                    const tag = (e.target.tagName || '').toLowerCase();
                    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

                    // Space = temporary pan
                    if (e.code === 'Space' && !this.spacePan) {
                        e.preventDefault();
                        this.spacePan = true;
                        return;
                    }

                    // Ctrl/Cmd + Z = undo, Ctrl/Cmd + Shift + Z OR Ctrl + Y = redo
                    if ((e.ctrlKey || e.metaKey) && !e.altKey) {
                        const key = e.key.toLowerCase();
                        if (key === 'z' && !e.shiftKey) {
                            e.preventDefault();
                            this.undo();
                            return;
                        }
                        if (key === 'y' || (key === 'z' && e.shiftKey)) {
                            e.preventDefault();
                            this.redo();
                            return;
                        }
                    }
                },

                onKeyUp(e) {
                    if (e.code === 'Space') {
                        this.spacePan = false;
                    }
                },

                // ---- Touch handlers (mirror mouse logic + pinch-zoom) ----
                onTouchStart(e) {
                    if (!this.originalImageData) return;
                    const ts = e.touches;
                    if (ts.length === 2) {
                        // Pinch start
                        this.touchMode = 'pinch';
                        const dx = ts[0].clientX - ts[1].clientX;
                        const dy = ts[0].clientY - ts[1].clientY;
                        this.touchStart = {
                            dist: Math.hypot(dx, dy),
                            zoom: this.zoom,
                            midX: (ts[0].clientX + ts[1].clientX) / 2,
                            midY: (ts[0].clientY + ts[1].clientY) / 2,
                            panX: this.panX,
                            panY: this.panY,
                        };
                        return;
                    }
                    if (ts.length !== 1) return;
                    const t = ts[0];
                    const fakeEvent = { clientX: t.clientX, clientY: t.clientY, button: 0, altKey: false, preventDefault: () => {} };

                    // Brush / Eraser: drag-paint, no tap-detection
                    if (this.mode === 'brush' || this.mode === 'eraser') {
                        this.touchMode = 'brush';
                        this.onMouseDown({ ...fakeEvent, button: 0 });
                        return;
                    }

                    // Default: treat as potential tap (commit on touchend) OR drag-pan
                    this.touchMode = 'tap-or-pan';
                    this.touchStart = {
                        x: t.clientX, y: t.clientY,
                        panX: this.panX, panY: this.panY,
                        tapTime: Date.now(),
                        tapX: t.clientX, tapY: t.clientY,
                        fakeEvent,
                    };
                },

                onTouchMove(e) {
                    if (!this.touchStart) return;
                    const ts = e.touches;

                    if (this.touchMode === 'pinch' && ts.length === 2) {
                        const dx = ts[0].clientX - ts[1].clientX;
                        const dy = ts[0].clientY - ts[1].clientY;
                        const newDist = Math.hypot(dx, dy);
                        const scale = newDist / this.touchStart.dist;
                        const newZoom = Math.max(0.1, Math.min(8, this.touchStart.zoom * scale));
                        // Zoom around the midpoint
                        const f = newZoom / this.zoom;
                        this.panX = this.touchStart.midX - f * (this.touchStart.midX - this.touchStart.panX);
                        this.panY = this.touchStart.midY - f * (this.touchStart.midY - this.touchStart.panY);
                        this.zoom = newZoom;
                        return;
                    }

                    if (ts.length !== 1) return;
                    const t = ts[0];

                    if (this.touchMode === 'brush') {
                        this.onMouseMove({ clientX: t.clientX, clientY: t.clientY, altKey: false, preventDefault: () => {} });
                        return;
                    }

                    if (this.touchMode === 'tap-or-pan' && this.touchStart) {
                        const dx = t.clientX - this.touchStart.x;
                        const dy = t.clientY - this.touchStart.y;
                        // After 8px movement, commit to pan
                        if (this.touchMode === 'tap-or-pan' && Math.hypot(dx, dy) > 8) {
                            this.touchMode = 'pan';
                            this.isPanning = true;
                        }
                        if (this.touchMode === 'pan') {
                            this.panX = this.touchStart.panX + dx;
                            this.panY = this.touchStart.panY + dy;
                        }
                    }
                },

                onTouchEnd(e) {
                    if (!this.touchStart) {
                        this.touchMode = null;
                        return;
                    }
                    if (this.touchMode === 'brush') {
                        this.onMouseUp({ preventDefault: () => {} });
                    } else if (this.touchMode === 'tap-or-pan') {
                        // It was a tap (no significant movement) → trigger click logic
                        const dt = Date.now() - this.touchStart.tapTime;
                        if (dt < 500) {
                            this.onCanvasClick(this.touchStart.fakeEvent);
                        }
                    } else if (this.touchMode === 'pan') {
                        this.isPanning = false;
                    }
                    this.touchStart = null;
                    this.touchMode = null;
                },

                /**
                 * Eraser: for each pixel in the brush circle, copy the ORIGINAL
                 * image color back into coloredImageData and clear paintedMask.
                 * Writes directly to canvas (no mask overlay for eraser).
                 */
                eraseAt(cx, cy) {
                    if (!this.originalImageData) return;
                    const orig = this.originalImageData.data;
                    const out = this.coloredImageData.data;
                    const w = this.coloredImageData.width;
                    const h = this.coloredImageData.height;
                    const r = this.brushSize / 2;
                    const r2 = r * r;
                    const x1 = Math.max(0, Math.floor(cx - r));
                    const x2 = Math.min(w - 1, Math.ceil(cx + r));
                    const y1 = Math.max(0, Math.floor(cy - r));
                    const y2 = Math.min(h - 1, Math.ceil(cy + r));
                    for (let y = y1; y <= y2; y++) {
                        const dy = y - cy;
                        for (let x = x1; x <= x2; x++) {
                            const dx = x - cx;
                            if (dx * dx + dy * dy > r2) continue;
                            const i = y * w + x;
                            const k = i * 4;
                            out[k]     = orig[k];
                            out[k + 1] = orig[k + 1];
                            out[k + 2] = orig[k + 2];
                            if (this.paintedMask) this.paintedMask[i] = 0;
                        }
                    }
                },

                paintBrushAt(cx, cy) {
                    if (!this.mask) return;
                    const w = this.coloredImageData.width;
                    const h = this.coloredImageData.height;
                    const r = this.brushSize / 2;
                    const r2 = r * r;
                    const x1 = Math.max(0, Math.floor(cx - r));
                    const x2 = Math.min(w - 1, Math.ceil(cx + r));
                    const y1 = Math.max(0, Math.floor(cy - r));
                    const y2 = Math.min(h - 1, Math.ceil(cy + r));
                    const setVal = this.brushSubtract ? 0 : 1;
                    let delta = 0;
                    for (let y = y1; y <= y2; y++) {
                        const dy = y - cy;
                        for (let x = x1; x <= x2; x++) {
                            const dx = x - cx;
                            if (dx * dx + dy * dy > r2) continue;
                            const i = y * w + x;
                            if (this.mask[i] !== setVal) {
                                this.mask[i] = setVal;
                                delta += setVal === 1 ? 1 : -1;
                            }
                        }
                    }
                    this.maskCount = Math.max(0, this.maskCount + delta);
                },

                async smartSelectAt(x, y) {
                    if (!this.originalImageData || this.loading) return;
                    this.loading = true;
                    this.loadingText = 'در حال تحلیل دیوار...';
                    try {
                        const w = this.originalImageData.width;
                        const h = this.originalImageData.height;

                        const temp = document.createElement('canvas');
                        temp.width = w;
                        temp.height = h;
                        temp.getContext('2d').putImageData(this.originalImageData, 0, 0);
                        const imageDataUrl = temp.toDataURL('image/jpeg', 0.88);

                        const res = await fetch('{{ route('ai.smart-point') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                image: imageDataUrl,
                                points: [[x / w, y / h]],
                                labels: [1],
                            }),
                        });

                        const payload = await res.json().catch(() => null);
                        if (!res.ok || !payload || !payload.mask) {
                            const msg = payload?.detail || payload?.error || ('HTTP ' + res.status);
                            const detail = typeof msg === 'string' ? msg : JSON.stringify(msg);
                            alert('انتخاب هوشمند ناموفق: ' + String(detail).substring(0, 300));
                            return;
                        }

                        this.pushHistory();
                        await this.applyMaskFromImageUrl(payload.mask, w, h);
                        const took = payload.elapsed_ms ? ` در ${(payload.elapsed_ms / 1000).toFixed(1)}s` : '';
                        this.statusText = `✅ ${payload.provider || 'AI'}${took} — ${this.maskCount.toLocaleString('fa-IR')} پیکسل`;
                    } catch (e) {
                        alert('خطای شبکه: ' + e.message);
                    } finally {
                        this.loading = false;
                    }
                },

                applyMaskFromImageUrl(maskUrl, targetW, targetH) {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        img.onload = () => {
                            const c = document.createElement('canvas');
                            c.width = targetW;
                            c.height = targetH;
                            const ctx = c.getContext('2d');
                            ctx.drawImage(img, 0, 0, targetW, targetH);
                            const data = ctx.getImageData(0, 0, targetW, targetH).data;
                            // Mask pixel = ON if alpha > 64 OR (alpha=255 and R/G/B > 128 — grayscale mask)
                            let added = 0;
                            for (let i = 0, p = 0; i < targetW * targetH; i++, p += 4) {
                                const isMask = data[p + 3] > 64 && (data[p + 3] < 200 || (data[p] + data[p + 1] + data[p + 2]) > 128);
                                if (isMask && !this.mask[i]) {
                                    this.mask[i] = 1;
                                    added++;
                                }
                            }
                            this.maskCount += added;
                            this.renderCanvas();
                            resolve(added);
                        };
                        img.onerror = reject;
                        img.src = maskUrl;
                    });
                },

                onWheel(e) {
                    if (!this.imageUrl) return;
                    const delta = e.deltaY < 0 ? 1.15 : 1 / 1.15;
                    const newZoom = Math.max(0.1, Math.min(8, this.zoom * delta));
                    const f = newZoom / this.zoom;
                    const vp = this.$refs.viewport.getBoundingClientRect();
                    const dx = e.clientX - (vp.left + vp.width / 2);
                    const dy = e.clientY - (vp.top + vp.height / 2);
                    this.panX = dx - f * (dx - this.panX);
                    this.panY = dy - f * (dy - this.panY);
                    this.zoom = newZoom;
                },

                zoomBy(factor) {
                    const newZoom = Math.max(0.1, Math.min(8, this.zoom * factor));
                    const f = newZoom / this.zoom;
                    // Scale pan around viewport center so content stays anchored
                    this.panX *= f;
                    this.panY *= f;
                    this.zoom = newZoom;
                },


                zoomFit() {
                    this.zoom = 1;
                    this.panX = 0;
                    this.panY = 0;
                },

                canvasCursor() {
                    if (this.isPanning) return 'cursor: grabbing';
                    if (this.panLocked || this.spacePan) return 'cursor: grab';
                    if (this.pickerActive) return 'cursor: cell';
                    if (this.mode === 'wand') return 'cursor: crosshair';
                    if (this.mode === 'brush') return 'cursor: crosshair';
                    if (this.mode === 'eraser') return 'cursor: crosshair';
                    if (this.mode === 'smart') return 'cursor: pointer';
                    return 'cursor: default';
                },

                processAiRegion(region, w, h, lumaBuf) {
                    if (!region) return 0;

                    const norm = (v) => {
                        const n = Number(v);
                        return n > 1.5 ? n / Math.max(w, h, 1000) : n;
                    };

                    // Build polygon outline (preferred) or fall back to bbox rectangle
                    let polyPoints = null;
                    if (Array.isArray(region.polygon) && region.polygon.length >= 3) {
                        polyPoints = region.polygon
                            .filter(p => Array.isArray(p) && p.length >= 2)
                            .map(p => [norm(p[0]) * w, norm(p[1]) * h]);
                    } else if (Array.isArray(region.bbox) && region.bbox.length === 4) {
                        const bb = region.bbox.map(norm);
                        const x1 = Math.min(bb[0], bb[2]) * w;
                        const y1 = Math.min(bb[1], bb[3]) * h;
                        const x2 = Math.max(bb[0], bb[2]) * w;
                        const y2 = Math.max(bb[1], bb[3]) * h;
                        polyPoints = [[x1, y1], [x2, y1], [x2, y2], [x1, y2]];
                    }
                    if (!polyPoints || polyPoints.length < 3) return 0;

                    const boundaryMask = this.buildPolygonMask(polyPoints, w, h);

                    let boundaryCount = 0;
                    for (let i = 0; i < boundaryMask.length; i++) if (boundaryMask[i]) boundaryCount++;
                    if (boundaryCount < 20) return 0;

                    const expected = this.hexToRgb(region.color_hex || '#ffffff');
                    const data = this.coloredImageData.data;
                    const colorMatchTol = 70;
                    const fillTol = 32;
                    const edgeTol = 38;

                    const seeds = Array.isArray(region.seeds) ? region.seeds : [];
                    let totalAdded = 0;
                    let anySeedHit = false;

                    for (const s of seeds) {
                        if (!Array.isArray(s) || s.length < 2) continue;
                        const sx = Math.max(0, Math.min(w - 1, Math.floor(norm(s[0]) * w)));
                        const sy = Math.max(0, Math.min(h - 1, Math.floor(norm(s[1]) * h)));
                        if (!boundaryMask[sy * w + sx]) continue;

                        const i = (sy * w + sx) * 4;
                        const dR = Math.abs(data[i] - expected.r);
                        const dG = Math.abs(data[i + 1] - expected.g);
                        const dB = Math.abs(data[i + 2] - expected.b);
                        if (dR > colorMatchTol || dG > colorMatchTol || dB > colorMatchTol) continue;

                        anySeedHit = true;
                        totalAdded += this.floodFillFromPoint(sx, sy, fillTol, boundaryMask, lumaBuf, edgeTol);
                    }

                    return totalAdded;
                },

                buildLumaBuffer(imageData) {
                    const data = imageData.data;
                    const n = imageData.width * imageData.height;
                    const buf = new Uint8Array(n);
                    for (let i = 0, k = 0; i < n; i++, k += 4) {
                        const r = data[k], g = data[k + 1], b = data[k + 2];
                        buf[i] = ((Math.max(r, g, b) + Math.min(r, g, b)) >> 1);
                    }
                    return buf;
                },

                morphClose(mask, w, h, iterations = 1) {
                    // Closing = dilate then erode (3x3 kernel, 4-neighbor)
                    const tmp = new Uint8Array(mask.length);
                    for (let it = 0; it < iterations; it++) {
                        // Dilate: any pixel with at least one set neighbor becomes set
                        for (let y = 0; y < h; y++) {
                            for (let x = 0; x < w; x++) {
                                const i = y * w + x;
                                if (mask[i]) { tmp[i] = 1; continue; }
                                if ((x > 0 && mask[i - 1]) ||
                                    (x < w - 1 && mask[i + 1]) ||
                                    (y > 0 && mask[i - w]) ||
                                    (y < h - 1 && mask[i + w])) {
                                    tmp[i] = 1;
                                } else {
                                    tmp[i] = 0;
                                }
                            }
                        }
                        // Erode: pixel stays set only if all 4 neighbors are set
                        for (let y = 0; y < h; y++) {
                            for (let x = 0; x < w; x++) {
                                const i = y * w + x;
                                if (!tmp[i]) { mask[i] = 0; continue; }
                                if (x === 0 || x === w - 1 || y === 0 || y === h - 1) {
                                    mask[i] = tmp[i];
                                } else {
                                    mask[i] = (tmp[i - 1] && tmp[i + 1] && tmp[i - w] && tmp[i + w]) ? 1 : 0;
                                }
                            }
                        }
                    }
                },

                buildPolygonMask(points, w, h) {
                    const mask = new Uint8Array(w * h);
                    if (points.length < 3) return mask;

                    let minY = Infinity, maxY = -Infinity;
                    for (const [, y] of points) {
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                    }
                    minY = Math.max(0, Math.floor(minY));
                    maxY = Math.min(h - 1, Math.ceil(maxY));

                    const n = points.length;
                    for (let y = minY; y <= maxY; y++) {
                        const xs = [];
                        for (let i = 0, j = n - 1; i < n; j = i++) {
                            const [xi, yi] = points[i];
                            const [xj, yj] = points[j];
                            if ((yi > y) !== (yj > y)) {
                                const x = xj + (y - yj) * (xi - xj) / (yi - yj);
                                xs.push(x);
                            }
                        }
                        xs.sort((a, b) => a - b);
                        for (let k = 0; k < xs.length - 1; k += 2) {
                            const xStart = Math.max(0, Math.ceil(xs[k]));
                            const xEnd = Math.min(w - 1, Math.floor(xs[k + 1]));
                            for (let x = xStart; x <= xEnd; x++) {
                                mask[y * w + x] = 1;
                            }
                        }
                    }
                    return mask;
                },

                findSeedInsidePolygon(points, polyMask, w, h) {
                    let sx = 0, sy = 0;
                    for (const [x, y] of points) { sx += x; sy += y; }
                    const cx = Math.floor(sx / points.length);
                    const cy = Math.floor(sy / points.length);
                    if (cx >= 0 && cy >= 0 && cx < w && cy < h && polyMask[cy * w + cx]) {
                        return { x: cx, y: cy };
                    }
                    for (let i = 0; i < polyMask.length; i++) {
                        if (polyMask[i]) return { x: i % w, y: Math.floor(i / w) };
                    }
                    return null;
                },

                floodFillFromPoint(startX, startY, tol, boundaryMask = null, lumaBuf = null, edgeTol = 0) {
                    const w = this.coloredImageData.width;
                    const h = this.coloredImageData.height;
                    if (startX < 0 || startY < 0 || startX >= w || startY >= h) return 0;
                    if (boundaryMask && !boundaryMask[startY * w + startX]) return 0;
                    const data = this.coloredImageData.data;
                    const startI = (startY * w + startX) * 4;
                    const tR = data[startI], tG = data[startI + 1], tB = data[startI + 2];
                    const useEdge = lumaBuf !== null && edgeTol > 0;
                    let added = 0;
                    const stack = [[startX, startY, -1]];
                    while (stack.length) {
                        const [x, y, fromL] = stack.pop();
                        if (x < 0 || y < 0 || x >= w || y >= h) continue;
                        const m = y * w + x;
                        if (this.mask[m]) continue;
                        if (boundaryMask && !boundaryMask[m]) continue;
                        const i = m * 4;
                        if (Math.abs(data[i] - tR) > tol ||
                            Math.abs(data[i + 1] - tG) > tol ||
                            Math.abs(data[i + 2] - tB) > tol) continue;
                        if (useEdge && fromL >= 0) {
                            const curL = lumaBuf[m];
                            if (Math.abs(curL - fromL) > edgeTol) continue;
                        }
                        this.mask[m] = 1;
                        added++;
                        const myL = useEdge ? lumaBuf[m] : -1;
                        stack.push([x + 1, y, myL], [x - 1, y, myL], [x, y + 1, myL], [x, y - 1, myL]);
                    }
                    return added;
                },

                async runAiSegmentation() {
                    if (!this.originalImageData) return;
                    this.loading = true;
                    this.loadingText = 'هوش مصنوعی داره عکس رو می‌بینه...';
                    try {
                        const temp = document.createElement('canvas');
                        temp.width = this.originalImageData.width;
                        temp.height = this.originalImageData.height;
                        temp.getContext('2d').putImageData(this.originalImageData, 0, 0);
                        const imageDataUrl = temp.toDataURL('image/jpeg', 0.85);

                        const res = await fetch('{{ route('ai.segment') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ image: imageDataUrl }),
                        });

                        const payload = await res.json().catch(() => null);
                        if (!res.ok || !payload) {
                            const msg = (payload && (payload.detail || payload.error)) || ('HTTP ' + res.status);
                            alert('خطا در تشخیص: ' + String(msg).substring(0, 250));
                            return;
                        }
                        if (!Array.isArray(payload.regions) || payload.regions.length === 0) {
                            alert('هوش مصنوعی هیچ دیوار یا سقفی پیدا نکرد. حالت دستی رو امتحان کن.');
                            return;
                        }

                        const w = this.originalImageData.width;
                        const h = this.originalImageData.height;

                        this.pushHistory();
                        this.mask.fill(0);
                        this.maskCount = 0;
                        let regionsHit = 0;
                        let regionsSkipped = 0;

                        // Pre-compute luminance buffer once for all regions (edge-aware fill)
                        const lumaBuf = this.buildLumaBuffer(this.coloredImageData);

                        for (const r of payload.regions) {
                            const result = this.processAiRegion(r, w, h, lumaBuf);
                            if (result > 0) {
                                this.maskCount += result;
                                regionsHit++;
                            } else {
                                regionsSkipped++;
                            }
                        }

                        // Morphological closing: fills small holes (around switches/outlets)
                        // and smooths the mask boundary for a cleaner look
                        if (this.maskCount > 0) {
                            this.morphClose(this.mask, w, h, 1);
                            let recount = 0;
                            for (let i = 0; i < this.mask.length; i++) if (this.mask[i]) recount++;
                            this.maskCount = recount;
                        }

                        this.renderCanvas();
                        const skipNote = regionsSkipped > 0 ? ` (${regionsSkipped} رد شد)` : '';
                        this.statusText = `${regionsHit}/${payload.regions.length} ناحیه اعمال شد${skipNote} — ${this.maskCount.toLocaleString('fa-IR')} پیکسل`;
                    } catch (e) {
                        alert('خطای شبکه: ' + e.message);
                    } finally {
                        this.loading = false;
                    }
                },

                renderCanvas() {
                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');
                    const w = canvas.width;
                    const h = canvas.height;
                    const out = new ImageData(
                        new Uint8ClampedArray(this.coloredImageData.data),
                        w, h
                    );
                    const data = out.data;

                    // Layer 1 — hover ghost (yellow tint over hovered region in smart mode)
                    if (this.mode === 'smart' && this.hoverLabel > 0 && this.labelMap) {
                        const sx = this.labelScaleX;
                        const sy = this.labelScaleY;
                        const lbl = this.hoverLabel;
                        for (let y = 0; y < h; y++) {
                            const ly = Math.min(this.labelH - 1, Math.floor(y * sy));
                            const rowOff = ly * this.labelW;
                            for (let x = 0; x < w; x++) {
                                const lx = Math.min(this.labelW - 1, Math.floor(x * sx));
                                if (this.labelMap[rowOff + lx] !== lbl) continue;
                                const k = (y * w + x) * 4;
                                // soft yellow tint = ghost preview
                                data[k]     = data[k]     * 0.65 + 255 * 0.35;
                                data[k + 1] = data[k + 1] * 0.65 + 215 * 0.35;
                                data[k + 2] = data[k + 2] * 0.65 +   0 * 0.35;
                            }
                        }
                    }

                    // Layer 2 — confirmed selection: paint with the ACTUAL selected color
                    // at near-full opacity so user sees exactly what they'll get.
                    // No edge blending — hard binary boundary, no perceived "fade".
                    const tc = this.hexToRgb(this.selectedColor);
                    for (let i = 0; i < this.mask.length; i++) {
                        if (this.mask[i]) {
                            const k = i * 4;
                            data[k]     = data[k]     * 0.1 + tc.r * 0.9;
                            data[k + 1] = data[k + 1] * 0.1 + tc.g * 0.9;
                            data[k + 2] = data[k + 2] * 0.1 + tc.b * 0.9;
                        }
                    }
                    ctx.putImageData(out, 0, 0);
                },

                /**
                 * رندر را با فریم مرورگر هماهنگ می‌کند: چند mousemove پشت‌سرهم
                 * فقط یک‌بار در هر فریم رندر می‌شوند تا UI حین کشیدنِ قلم هنگ نکند
                 * و preview زنده دیده شود.
                 */
                scheduleRender() {
                    if (this._renderQueued) return;
                    this._renderQueued = true;
                    requestAnimationFrame(() => {
                        this._renderQueued = false;
                        this.renderCanvas();
                    });
                },

                switchMode(newMode) {
                    if (this.isBrushing) {
                        this.isBrushing = false;
                        this.lastBrushPoint = null;
                    }
                    // Switching to a selection tool auto-releases pan lock
                    if (this.panLocked) this.panLocked = false;
                    // Clear hover ghost when leaving smart mode
                    if (this.mode === 'smart' && newMode !== 'smart') {
                        this.hoverLabel = 0;
                    }
                    this.mode = newMode;
                    if (this.coloredImageData) this.renderCanvas();
                },

                applyColor() {
                    if (!this.hasMask) return;
                    if (!this.paintedMask) this.paintedMask = new Uint8Array(this.mask.length);
                    this.pushHistory(); // snapshot so user can Ctrl+Z back

                    const target = this.hexToRgb(this.selectedColor);
                    const targetHsl = this.rgbToHsl(target.r, target.g, target.b);
                    const orig = this.originalImageData.data;
                    const w = this.coloredImageData.width;
                    const h = this.coloredImageData.height;

                    // CURRENT colored state (before this paint) — needed because:
                    // 1. Feather edge blends into CURRENT (not original) → no halo when repainting
                    // 2. Fill-in mode samples neighbors' current color to match seamlessly
                    const cur = new Uint8ClampedArray(this.coloredImageData.data);
                    const out = this.coloredImageData.data;

                    // "Fill-in mode": user is painting with the SAME color as last time
                    // → most likely they're filling missed gaps. Match the surrounding shade
                    // exactly instead of recomputing from luminance (which would give a
                    // different brightness for a white gap pixel vs. a gray wall pixel).
                    const isFillIn = !!this.lastAppliedColor
                                  && this.lastAppliedColor.toLowerCase() === this.selectedColor.toLowerCase()
                                  && this.paintedMask.some(v => v === 1);

                    // Mean luminance — INCLUDE all previously-painted pixels too so the
                    // anchor is consistent across multiple paint passes (a small white-gap
                    // brush won't drag avgL up to 0.95 by itself).
                    let sumL = 0, cnt = 0;
                    for (let i = 0; i < this.mask.length; i++) {
                        if (this.mask[i]) {
                            sumL += this.rgbToL(orig[i*4], orig[i*4+1], orig[i*4+2]);
                            cnt++;
                        }
                    }
                    for (let i = 0; i < this.paintedMask.length; i++) {
                        if (this.paintedMask[i] && !this.mask[i]) {
                            sumL += this.rgbToL(orig[i*4], orig[i*4+1], orig[i*4+2]);
                            cnt++;
                        }
                    }
                    const avgL = cnt > 0 ? sumL / cnt : 0.5;

                    for (let i = 0; i < this.mask.length; i++) {
                        if (!this.mask[i]) continue;
                        const k = i * 4;
                        let r, g, b;

                        if (!this.preserveTexture) {
                            // FLAT mode: just the picked color, no shadow/texture
                            r = target.r; g = target.g; b = target.b;
                        } else if (isFillIn) {
                            const neighbor = this.samplePaintedNeighbor(i, w, h, 6, cur);
                            if (neighbor) {
                                r = neighbor.r; g = neighbor.g; b = neighbor.b;
                            } else {
                                // No painted neighbor in radius → fall back to standard
                                const origL = this.rgbToL(orig[k], orig[k+1], orig[k+2]);
                                const newL = Math.max(0, Math.min(1, targetHsl.l + (origL - avgL)));
                                const rgb = this.hslToRgb(targetHsl.h, targetHsl.s, newL);
                                r = rgb.r; g = rgb.g; b = rgb.b;
                            }
                        } else {
                            // TEXTURE-PRESERVE: shift the pixel's luminance to match target color
                            const origL = this.rgbToL(orig[k], orig[k+1], orig[k+2]);
                            const newL = Math.max(0, Math.min(1, targetHsl.l + (origL - avgL)));
                            const rgb = this.hslToRgb(targetHsl.h, targetHsl.s, newL);
                            r = rgb.r; g = rgb.g; b = rgb.b;
                        }

                        // FULL color at every selected pixel — no feather, no halo
                        out[k]     = r;
                        out[k + 1] = g;
                        out[k + 2] = b;

                        this.paintedMask[i] = 1;
                    }

                    this.lastAppliedColor = this.selectedColor;
                    this.mask.fill(0);
                    this.maskCount = 0;
                    this.$refs.canvas.getContext('2d').putImageData(this.coloredImageData, 0, 0);
                    this.pushRecentColor(this.selectedColor);
                    this.statusText = isFillIn ? '✨ رنگ تکمیل شد (match با نواحی قبلی)' : 'رنگ اعمال شد';
                },

                /**
                 * Average color of painted pixels within `radius` of `idx`.
                 * Used in fill-in mode so a new brush stroke inherits the existing shade.
                 */
                samplePaintedNeighbor(idx, w, h, radius, cur) {
                    if (!this.paintedMask) return null;
                    const x0 = idx % w;
                    const y0 = Math.floor(idx / w);
                    let sumR = 0, sumG = 0, sumB = 0, count = 0;
                    const x1 = Math.max(0, x0 - radius);
                    const x2 = Math.min(w - 1, x0 + radius);
                    const y1 = Math.max(0, y0 - radius);
                    const y2 = Math.min(h - 1, y0 + radius);
                    for (let y = y1; y <= y2; y++) {
                        for (let x = x1; x <= x2; x++) {
                            const i = y * w + x;
                            if (i === idx || !this.paintedMask[i]) continue;
                            const k = i * 4;
                            sumR += cur[k]; sumG += cur[k+1]; sumB += cur[k+2];
                            count++;
                        }
                    }
                    if (count === 0) return null;
                    return {
                        r: Math.round(sumR / count),
                        g: Math.round(sumG / count),
                        b: Math.round(sumB / count),
                    };
                },

                /**
                 * For each pixel inside the mask, compute a feather weight
                 * based on its distance from the mask boundary.
                 * Implemented via a fast iterative erosion: pixels that survive
                 * N erosions get weight 1.0; those at distance d get d/N.
                 */
                computeFeatherWeights(mask, w, h, maxDist) {
                    const result = new Float32Array(mask.length);
                    // result[i] = distance to boundary (in [0, maxDist]) for pixels in mask
                    // Start: pixels touching outside get 0 distance
                    let prev = new Uint8Array(mask);
                    for (let d = 0; d <= maxDist; d++) {
                        const layer = (d / maxDist) || 0;
                        const next = new Uint8Array(mask.length);
                        for (let y = 1; y < h - 1; y++) {
                            for (let x = 1; x < w - 1; x++) {
                                const i = y * w + x;
                                if (!prev[i]) continue;
                                // Erode: keep ON only if all 4 neighbors are ON
                                if (prev[i - 1] && prev[i + 1] && prev[i - w] && prev[i + w]) {
                                    next[i] = 1;
                                    if (result[i] === 0) result[i] = layer;
                                } else {
                                    // boundary pixel at this iteration
                                    if (result[i] === 0) result[i] = layer;
                                }
                            }
                        }
                        prev = next;
                    }
                    // Pixels that survived all erosions = 1.0 (deep interior)
                    for (let i = 0; i < mask.length; i++) {
                        if (mask[i] && prev[i]) result[i] = 1.0;
                        else if (mask[i] && result[i] === 0) result[i] = 0.35; // singleton
                    }
                    return result;
                },

                pushRecentColor(hex) {
                    const upper = (hex || '').toUpperCase();
                    if (!upper) return;
                    this.recentColors = [upper, ...this.recentColors.filter(c => c !== upper)].slice(0, 6);
                },

                rgbToL(r, g, b) {
                    return (Math.max(r, g, b) + Math.min(r, g, b)) / 510;
                },

                rgbToHsl(r, g, b) {
                    r /= 255; g /= 255; b /= 255;
                    const max = Math.max(r, g, b), min = Math.min(r, g, b);
                    const l = (max + min) / 2;
                    let h = 0, s = 0;
                    if (max !== min) {
                        const d = max - min;
                        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                        if (max === r) h = ((g - b) / d + (g < b ? 6 : 0));
                        else if (max === g) h = (b - r) / d + 2;
                        else h = (r - g) / d + 4;
                        h /= 6;
                    }
                    return { h, s, l };
                },

                hslToRgb(h, s, l) {
                    let r, g, b;
                    if (s === 0) {
                        r = g = b = l;
                    } else {
                        const hue2rgb = (p, q, t) => {
                            if (t < 0) t += 1;
                            if (t > 1) t -= 1;
                            if (t < 1 / 6) return p + (q - p) * 6 * t;
                            if (t < 1 / 2) return q;
                            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
                            return p;
                        };
                        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                        const p = 2 * l - q;
                        r = hue2rgb(p, q, h + 1 / 3);
                        g = hue2rgb(p, q, h);
                        b = hue2rgb(p, q, h - 1 / 3);
                    }
                    return {
                        r: Math.round(r * 255),
                        g: Math.round(g * 255),
                        b: Math.round(b * 255),
                    };
                },

                clearMask() {
                    if (!this.mask) return;
                    if (this.maskCount > 0) this.pushHistory();
                    this.mask.fill(0);
                    this.maskCount = 0;
                    this.$refs.canvas.getContext('2d').putImageData(this.coloredImageData, 0, 0);
                    this.statusText = '';
                },

                showBefore(active) {
                    if (!this.originalImageData) return;
                    const canvas = this.$refs.canvas;
                    const ctx = canvas.getContext('2d');
                    if (active) {
                        ctx.putImageData(this.originalImageData, 0, 0);
                    } else {
                        this.renderCanvas();
                    }
                },

                downloadResult() {
                    if (!this.coloredImageData) return;
                    // Render to a clean canvas without mask overlay
                    const tmp = document.createElement('canvas');
                    tmp.width = this.coloredImageData.width;
                    tmp.height = this.coloredImageData.height;
                    tmp.getContext('2d').putImageData(this.coloredImageData, 0, 0);
                    tmp.toBlob((blob) => {
                        if (!blob) return;
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `rangify-${Date.now()}.png`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        setTimeout(() => URL.revokeObjectURL(url), 1000);
                    }, 'image/png');
                },

                resetColor() {
                    if (!this.originalImageData) return;
                    this.coloredImageData = new ImageData(
                        new Uint8ClampedArray(this.originalImageData.data),
                        this.originalImageData.width,
                        this.originalImageData.height
                    );
                    this.mask.fill(0);
                    this.maskCount = 0;
                    if (this.paintedMask) this.paintedMask.fill(0);
                    this.lastAppliedColor = null;
                    this.$refs.canvas.getContext('2d').putImageData(this.coloredImageData, 0, 0);
                    this.statusText = 'برگشت به تصویر اصلی';
                },

                reset() {
                    this.imageUrl = null;
                    this.originalImageData = null;
                    this.coloredImageData = null;
                    this.mask = null;
                    this.maskCount = 0;
                    this.statusText = '';
                    this.zoomFit();
                },

                normalizeHex() {
                    let v = (this.selectedColor || '').trim();
                    if (!v.startsWith('#')) v = '#' + v;
                    if (/^#[0-9A-Fa-f]{3}$/.test(v)) {
                        v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
                    }
                    if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
                        this.selectedColor = v.toUpperCase();
                    }
                },

                hexToRgb(hex) {
                    const m = hex.match(/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i);
                    return m
                        ? { r: parseInt(m[1], 16), g: parseInt(m[2], 16), b: parseInt(m[3], 16) }
                        : { r: 0, g: 0, b: 0 };
                },
            };
        }
    </script>

</body>
</html>
