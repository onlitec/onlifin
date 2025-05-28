import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build'
        }),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                style: 'resources/css/app.css',
            },
            external: [
                'flowbite'
            ],
            output: {
                manualChunks: {
                    vendor: ['alpinejs', '@alpinejs/mask', '@alpinejs/focus']
                }
            }
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: {
        host: true,
        hmr: {
            host: 'dev.onlifin.onlitec.com.br'
        }
    },
    base: '/',
});
