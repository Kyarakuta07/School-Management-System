import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    // Base public path - adjust if deploying to subdirectory
    base: './',

    // Root directory for source files
    root: 'moe',

    // Build configuration
    build: {
        // Output directory (relative to project root, not 'root')
        outDir: '../dist',

        // Empty output directory before build
        emptyOutDir: true,

        // Generate source maps for debugging
        sourcemap: true,

        // Rollup options for multiple entry points
        rollupOptions: {
            input: {
                // Pet system modules
                'pet-main': resolve(__dirname, 'moe/user/js/pet/main.js'),

                // Other JS modules (non-ES6, will be bundled individually)
                'pet-arena': resolve(__dirname, 'moe/user/js/pet_arena.js'),
                'collection-phase2': resolve(__dirname, 'moe/user/js/collection_phase2.js'),
                'trapeza': resolve(__dirname, 'moe/user/js/trapeza.js'),

                // CSS bundles
                'pet-styles': resolve(__dirname, 'moe/user/css/pet_v2.css'),
            },
            output: {
                // Organize output files
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },

        // Minification settings
        minify: 'esbuild',

        // Target modern browsers
        target: 'es2020',
    },

    // Development server configuration
    server: {
        // Proxy PHP files to local server (e.g., XAMPP)
        proxy: {
            '/moe': {
                target: 'http://localhost:80',
                changeOrigin: true,
            },
        },

        // Open browser on start
        open: '/moe/user/pet.php',

        // Port
        port: 3000,
    },

    // Resolve aliases for cleaner imports
    resolve: {
        alias: {
            '@pet': resolve(__dirname, 'moe/user/js/pet'),
            '@css': resolve(__dirname, 'moe/user/css'),
        },
    },

    // CSS configuration
    css: {
        // Generate source maps
        devSourcemap: true,
    },
});
