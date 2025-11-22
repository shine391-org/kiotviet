import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: './src/setupTests.js',
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html', 'clover', 'json'],
            reportsDirectory: './coverage',
            exclude: [
                'node_modules/',
                'src/setupTests.js',
                '**/*.test.{js,jsx}',
                '**/*.config.{js,ts}',
                '**/dist/**',
                '**/.{idea,git,cache,output,temp}/**',
                '**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*',
            ],
            // Enforce 70% coverage threshold
            thresholds: {
                statements: 70,
                branches: 70,
                functions: 70,
                lines: 70,
            },
        },
    },
    resolve: {
        alias: {
            '@': '/src',
        },
    },
});
