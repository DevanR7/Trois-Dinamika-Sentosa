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
            ],
            refresh: true,
        }),
    ],
    build: {
        // 1. Naikkan limit peringatan ke 2000 kBs (2 MB)
        chunkSizeWarningLimit: 2000, 
        
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                },
            },
        },
    },
});