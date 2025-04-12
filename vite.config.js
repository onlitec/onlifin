import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: {
                app: 'resources/js/app.js',
                style: 'resources/css/app.css',
            },
            refresh: true,
        }),
    ],
    base: '/build/',
    build: {
        target: 'esnext',
        minify: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs', 'chart.js']
                }
            }
        }
    }
});
