import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public',
            base: '/',
            includeAssets: ['favicon.ico', 'icons/*.png'],
            manifest: {
                name: 'DroneLog',
                short_name: 'DroneLog',
                description: 'Part 107 drone flight logging',
                theme_color: '#1e40af',
                background_color: '#0f172a',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/icon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                clientsClaim: true,
                skipWaiting: true,
                navigateFallback: null,
                runtimeCaching: [
                    {
                        // Cache the PHP-rendered HTML shell for offline navigation
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'app-shell-cache',
                            networkTimeoutSeconds: 5,
                        },
                    },
                    {
                        urlPattern: /\/api\/v1\/(drones|batteries|accessories|checklist-templates)/,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'api-fleet-cache',
                            expiration: { maxAgeSeconds: 86400 },
                        },
                    },
                    {
                        urlPattern: /\/api\/v1\/flights/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-flights-cache',
                            networkTimeoutSeconds: 5,
                            expiration: { maxEntries: 200, maxAgeSeconds: 2592000 },
                        },
                    },
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
