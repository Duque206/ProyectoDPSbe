const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                mukta: ['Mukta', 'sans-serif'],
                raleway: ['Raleway', 'sans-serif'],
            },
            colors: {
                'grayC': {
                    500: '#313131',
                    400: '#414141',
                    300: '#525252'
                },
                'pinkC': {
                    100: '#F4A5A3',
                    300: '#EF7876',
                    400: '#EC625F',
                },
                'whiteC': {
                    500: '#EEEEEE',
                    400: '#F1F6F5',
                }
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('tailwind-scrollbar')({ nocompatible: true }),
    ],
};
