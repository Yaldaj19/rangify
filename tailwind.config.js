import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#f97316',
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },
                ink: {
                    50: '#f8fafc',
                    900: '#0f172a',
                },
            },
        },
    },

    plugins: [forms],
};
