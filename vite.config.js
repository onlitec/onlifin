import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

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
        emptyOutDir: false,
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                style: 'resources/css/app.css',
            },
            external: ['imask'],
        },
    },
    base: 'http://onlifin.onlitec.com.br/build/',
});
