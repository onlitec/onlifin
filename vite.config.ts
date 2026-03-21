import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import svgr from 'vite-plugin-svgr';
import path from 'path';

import { miaodaDevPlugin } from "miaoda-sc-plugin";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), svgr({
    svgrOptions: {
      icon: true, exportType: 'named', namedExport: 'ReactComponent',
    },
  }), miaodaDevPlugin()],
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
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) {
            return;
          }

          if (id.includes('recharts')) {
            return 'charts';
          }

          if (id.includes('@supabase') || id.includes('jwt-decode')) {
            return 'supabase';
          }

          if (id.includes('@radix-ui')) {
            return 'radix';
          }

          if (id.includes('date-fns') || id.includes('react-day-picker')) {
            return 'dates';
          }

          if (id.includes('lucide-react')) {
            return 'icons';
          }

          if (id.includes('react-hook-form') || id.includes('@hookform') || id.includes('/zod/')) {
            return 'forms';
          }

          if (id.includes('miaoda-auth-react')) {
            return 'miaoda-auth';
          }

          if (
            id.includes('axios') ||
            id.includes('/ky/') ||
            id.includes('qrcode')
          ) {
            return 'network-utils';
          }

          if (
            id.includes('cmdk') ||
            id.includes('sonner') ||
            id.includes('vaul') ||
            id.includes('embla-carousel-react') ||
            id.includes('react-dropzone') ||
            id.includes('react-resizable-panels') ||
            id.includes('next-themes')
          ) {
            return 'ux';
          }

          if (id.includes('eventsource-parser') || id.includes('streamdown') || id.includes('video-react')) {
            return 'assistant-media';
          }

          return 'vendor';
        },
      },
    },
  },
  server: {
    host: '0.0.0.0', // Listen on all network interfaces
    port: 80, // Default HTTP port for http://192.168.0.70/
    strictPort: true, // Fail if port 80 is already in use
    // Force full reload on any change
    hmr: {
      overlay: true,
      host: '192.168.0.70', // HMR host for external access
    },
  },
  // Add cache directory configuration
  cacheDir: 'node_modules/.vite-new',
});
