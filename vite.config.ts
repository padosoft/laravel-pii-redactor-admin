import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  build: {
    manifest: true,
    outDir: 'resources/dist',
    rollupOptions: {
      input: ['resources/js/app.tsx', 'resources/css/admin.css'],
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['tests/JavaScript/**/*.{test,spec}.{ts,tsx}'],
  },
});
