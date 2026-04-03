// vite.config.js
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => ({
  plugins: [
    // Use the React plugin with default settings. The plugin will enable
    // fast refresh automatically in development; explicitly toggling the
    // option has caused compatibility issues in some plugin versions.
    react(),
  ],
  build: {
    outDir: '../public/sales-register',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',
      },
    },
  },
  server: {
    port: 5173,
    host: true,
    cors: true,
  },
}));