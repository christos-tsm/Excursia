import defaultTheme from 'tailwindcss/defaultTheme';
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
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    300: '#43ABFF',
                    400: '#3892DA',
                    500: '#2E74B1',
                },
                typo: {
                    300: '#0F293D',
                    400: '#091926',
                }
            }
        },
    },

    plugins: [forms],
};
