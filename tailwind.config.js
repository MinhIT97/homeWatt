import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './Modules/**/*.blade.php',
        './Modules/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
                outfit: ['"Outfit"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    650: '#6d35e0',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    850: '#4e1ca0',
                    900: '#4c1d95',
                    950: '#2e1065',
                },
                accent: {
                    50: '#ecfeff',
                    100: '#cffafe',
                    200: '#a5f3fc',
                    300: '#67e8f9',
                    400: '#22d3ee',
                    500: '#06b6d4',
                    600: '#0891b2',
                    650: '#077b99',
                    700: '#0e7490',
                    800: '#155e75',
                    900: '#164e63',
                    950: '#083344',
                },
                red: {
                    ...defaultTheme.colors.red,
                    650: '#d93838',
                },
                green: {
                    ...defaultTheme.colors.green,
                    650: '#1e9e55',
                },
                blue: {
                    ...defaultTheme.colors.blue,
                    150: '#dbeafe',
                    650: '#2563eb',
                },
                slate: {
                    ...defaultTheme.colors.slate,
                    150: '#e9eef3',
                    550: '#5a6d80',
                    650: '#445669',
                    750: '#2d3a4a',
                    850: '#1a2332',
                },
                yellow: {
                    ...defaultTheme.colors.yellow,
                    250: '#fde68a',
                },
                orange: {
                    ...defaultTheme.colors.orange,
                },
            },
            spacing: {
                ...defaultTheme.spacing,
                '4.5': '1.125rem',
            },
        },
    },

    plugins: [forms],
};
