import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter Variable', 'Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                brand: {
                    DEFAULT: 'var(--brand-color, #16A34A)',
                    dark: 'color-mix(in srgb, var(--brand-color, #16A34A) 80%, black)',
                    soft: 'color-mix(in srgb, var(--brand-color, #16A34A) 15%, white)',
                    subtle: 'color-mix(in srgb, var(--brand-color, #16A34A) 5%, white)',
                },
            },
        },
    },

    plugins: [forms],
};
