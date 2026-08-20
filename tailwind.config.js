import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Palette de marque de l'application : ces tokens (bg-primary, text-accent,
                // border-danger...) sont utilisés dans des dizaines de vues mais n'avaient
                // jamais été déclarés ici, donc Tailwind ne générait aucune classe pour eux.
                primary: {
                    DEFAULT: '#2563eb',
                    dark: '#1d4ed8',
                },
                accent: '#16a34a',
                danger: '#dc2626',
            },
        },
    },

    plugins: [forms],
};
