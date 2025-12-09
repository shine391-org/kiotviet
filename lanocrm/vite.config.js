import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const proxyTarget =
    process.env.VITE_API_URL ||
    env.VITE_API_URL ||
    env.VITE_API_PROXY_TARGET ||
    (env.VITE_API_BASE_URL?.startsWith('http') ? env.VITE_API_BASE_URL : '') ||
    'http://localhost:8000';

  console.log('[Vite Config] Proxy target:', proxyTarget);
  console.log('[Vite Config] process.env.VITE_API_URL:', process.env.VITE_API_URL);

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src'),
      },
    },
    server: {
      host: '0.0.0.0',
      port: parseInt(env.VITE_PORT || '3000'),
      proxy: {
        '/api': {
          target: proxyTarget.replace(/\/api\/?$/, ''),
          changeOrigin: true,
          secure: false,
          configure: (proxy) => {
            proxy.on('proxyReq', (proxyReq, req) => {
              console.log('[Proxy]', req.method, req.url, '->', proxyReq.path);
            });
            proxy.on('error', (err, req) => {
              console.error('[Proxy Error]', req.url, err.message);
            });
          },
        },
      },
    },
    build: {
      outDir: 'build',
    },
    test: {
      globals: true,
      environment: 'jsdom',
      setupFiles: './vitest.setup.js',
      css: true,
    },
  };
});
