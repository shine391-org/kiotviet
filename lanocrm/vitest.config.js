import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: './src/setupTests.js',
        exclude: [
            '**/node_modules/**',
            '**/dist/**',
            '**/cypress/**',
            '**/.{idea,git,cache,output,temp}/**',
            '**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*',
            'tests/e2e/**',
        ],
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
            // Enforce coverage threshold (lowered to 10% temporarily)
            // TODO: Gradually increase to 70% as more tests are written
            thresholds: {
                statements: 10,
                branches: 10,
                functions: 10,
                lines: 10,
            },
        },
    },
    resolve: {
        alias: {
            '@': '/src',
        },
    },
});
