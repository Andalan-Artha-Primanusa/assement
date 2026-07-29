import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'bg-sky-50',
        'text-sky-700',
        'ring-sky-200',
        'bg-amber-50',
        'text-amber-700',
        'ring-amber-200',
        'bg-cyan-50',
        'text-cyan-700',
        'ring-cyan-200',
        'bg-rose-50',
        'text-rose-700',
        'ring-rose-200',
        'bg-gray-50',
        'text-gray-700',
        'ring-gray-200',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
