import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
    build: {
        // Do not expose source maps in production
        sourcemap: false,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks: {
                    // Core React runtime
                    'vendor-react': ['react', 'react-dom'],
                    // Inertia + routing
                    'vendor-inertia': ['@inertiajs/react'],
                    // Charts (heaviest single dep)
                    'vendor-recharts': ['recharts'],
                    // Form & validation
                    'vendor-forms': ['react-hook-form', '@hookform/resolvers', 'zod'],
                    // Table
                    'vendor-table': ['@tanstack/react-table'],
                    // UI utilities
                    'vendor-ui': ['@headlessui/react', 'lucide-react', 'date-fns', 'clsx', 'tailwind-merge', 'class-variance-authority'],
                },
            },
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
});
