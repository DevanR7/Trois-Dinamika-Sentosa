import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    // 1. PENTING: Aktifkan mode 'class' agar tombol switch di navbar berfungsi
    darkMode: 'class', 

    // 2. Pastikan tailwind memindai semua file view dan js Anda
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            // 3. Mengatur Font Utama ke 'Inter' (Sesuai request)
            fontFamily: {
                sans: ['Inter', 'sans-serif', ...defaultTheme.fontFamily.sans],
            },
            
            // 4. Konfigurasi Warna Tambahan
            colors: {
                // Kita tambahkan warna khusus untuk background dark mode yang 'deep'
                // Ini agar tidak terlalu hitam pekat, tapi biru malam yang elegan
                slate: {
                    850: '#151f32', 
                }
            },
            
            // 5. Konfigurasi Z-Index (Opsional, untuk keamanan overlay)
            zIndex: {
                '60': '60',
                '70': '70',
                '100': '100', 
            }
        },
    },

    // Kita kosongkan plugins karena kita sudah membuat custom style input di app.css
    // agar tampilan lebih bersih dan tidak bentrok dengan @tailwindcss/forms
    plugins: [],
};