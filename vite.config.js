import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

/**
 * Phase 3.2 CSS entries — Mix remains the production pipeline until Blade cutover (3.3).
 * Vue 3.5.40 @vue/compat MODE 2: alias vue → @vue/compat + compiler compatConfig
 * (official migration-build Vite example) + runtime configureCompat in app.js.
 *
 * CSS entries (mirrors Mix):
 * - resources/css/tailwind.css → @tailwindcss/vite (has @import "tailwindcss")
 * - resources/assets/sass/app.scss → Vite built-in Sass (Phase 2b: plain CSS in
 *   adminstyle/style — no @apply reintroduction)
 * - resources/css/landing.css → plain CSS entry (Mix mix.styles concat); no
 *   @import "tailwindcss", so @tailwindcss/vite does not utility-scan it
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/js/app.js',
                'resources/assets/sass/app.scss',
                'resources/css/tailwind.css',
                'resources/css/landing.css',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    whitespace: 'preserve',
                    compatConfig: {
                        MODE: 2,
                    },
                },
            },
        }),
        // Only expands utilities for CSS that @import "tailwindcss" (tailwind.css).
        // Plain landing.css / Sass app.scss pass through without content scanning.
        tailwindcss(),
    ],
    resolve: {
        alias: {
            vue: '@vue/compat',
            '@': path.resolve(__dirname, 'resources/assets/js'),
        },
        // Webpack/Mix resolved extensionless ./Foo → Foo.vue; Vite needs this explicitly.
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    },
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
});
