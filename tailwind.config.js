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
                    DEFAULT: '#16A34A',
                    dark: '#15803D',
                    soft: '#DCFCE7',
                    subtle: '#F0FDF4',
                },
            },
        },
    },

    plugins: [forms],
};
