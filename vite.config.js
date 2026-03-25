import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: false,
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: {
        app: 'resources/js/app.js',
      },
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
  },
});
