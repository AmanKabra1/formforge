import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ink: {
                    DEFAULT: '#0f0b1f',
                    soft: '#1c1633',
                },
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: 0, transform: 'translateY(10px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' },
                },
                pop: {
                    '0%': { transform: 'scale(.6)', opacity: 0 },
                    '60%': { transform: 'scale(1.12)', opacity: 1 },
                    '100%': { transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0) rotate(var(--r, 0deg))' },
                    '50%': { transform: 'translateY(-12px) rotate(var(--r, 0deg))' },
                },
                'gradient-x': {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
                flash: {
                    '0%': { boxShadow: '0 0 0 0 rgba(139, 92, 246, .55)' },
                    '100%': { boxShadow: '0 0 0 14px rgba(139, 92, 246, 0)' },
                },
                draw: {
                    '100%': { strokeDashoffset: 0 },
                },
                'spin-slow': {
                    '100%': { transform: 'rotate(360deg)' },
                },
            },
            animation: {
                'fade-up': 'fade-up .45s cubic-bezier(.2,.8,.2,1) both',
                pop: 'pop .4s cubic-bezier(.2,.8,.2,1) both',
                float: 'float 6s ease-in-out infinite',
                'gradient-x': 'gradient-x 6s ease infinite',
                shimmer: 'shimmer 1.6s infinite',
                flash: 'flash .9s ease-out 1',
                draw: 'draw .7s .2s ease-out forwards',
                'spin-slow': 'spin-slow 8s linear infinite',
            },
        },
    },

    plugins: [forms],
};
