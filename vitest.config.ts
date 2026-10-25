import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

// Separate from vite.config.ts so the tests do not start the Laravel build plugin
export default defineConfig({
    plugins: [react()],
    resolve: { alias: { '@': path.resolve(__dirname, './resources/js') } },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.ts'],
        include: ['resources/js/tests/**/*.test.{ts,tsx}'],
    },
});
