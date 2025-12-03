import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin/app.css',
                'resources/css/client/app.css',
                'resources/css/pages/auth.css',
                
                'resources/js/admin/app.js',
                'resources/js/client/app.js',
                // 'resources/js/public/app.js', // JS Public (Nanti)
            ],
            refresh: true,
        }),
    ],
});
