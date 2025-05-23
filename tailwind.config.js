import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#3b82f6', // blue-500
                    dark: '#2563eb', // blue-600
                    light: '#60a5fa', // blue-400
                },
                secondary: {
                    DEFAULT: '#4B5563', // gray-600
                    dark: '#374151', // gray-700
                    light: '#6B7280', // gray-500
                },
                accent: {
                    DEFAULT: '#10B981', // green-500
                    dark: '#059669', // green-600
                    light: '#34D399', // green-400
                },
                dark: {
                    bg: '#121212',
                    card: '#1C1C1C',
                    text: '#EAEAEA',
                    border: '#2D2D2D',
                },
                light: {
                    bg: '#FFFFFF',
                    card: '#F9FAFB',
                    text: '#111827',
                    border: '#E5E7EB',
                }
            },
        },
    },

    plugins: [forms],
};
