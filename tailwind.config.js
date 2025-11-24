import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Tambahkan path JS/Vue jika nanti kamu pakai
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                // SAYA UBAH: Menyesuaikan dengan font di app.css kamu
                sans: ['"Hedvig Letters Sans"', ...defaultTheme.fontFamily.sans],
            },
            // SAYA TAMBAH: Warna custom dari app.css kamu agar bisa dipanggil via Tailwind
            colors: {
                sidebar: {
                    DEFAULT: '#111827',     // var(--sidebar-bg) -> bg-sidebar
                    hover: '#374151',       // var(--sidebar-link-hover) -> bg-sidebar-hover
                    active: '#4f46e5',      // var(--sidebar-link-active) -> bg-sidebar-active
                    text: '#adb5bd',        // var(--sidebar-link-text) -> text-sidebar-text
                    'text-active': '#ffffff', 
                    heading: '#6b7280',
                },
                body: {
                    bg: '#f8f9fa',          // var(--body-bg) -> bg-body-bg
                }
            }
        },
    },

    plugins: [
        forms, 
        // Pastikan plugin ini sudah terinstall via npm
    ],
};