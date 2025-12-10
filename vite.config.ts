import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import svgr from 'vite-plugin-svgr';
import path from 'path';

import { miaodaDevPlugin } from "miaoda-sc-plugin";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), svgr({
      svgrOptions: {
        icon: true, exportType: 'named', namedExport: 'ReactComponent', }, }), miaodaDevPlugin()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
    dedupe: ['react', 'react-dom'],
  },
  optimizeDeps: {
    force: true, // Force re-optimization of dependencies
    include: ['react', 'react-dom'], // Explicitly include React
    esbuildOptions: {
      // Force esbuild to rebuild
      logLevel: 'info',
    },
  },
  server: {
    // Force full reload on any change
    hmr: {
      overlay: true,
    },
  },
  // Add cache directory configuration
  cacheDir: 'node_modules/.vite-new',
});
