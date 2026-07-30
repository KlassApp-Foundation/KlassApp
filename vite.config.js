import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

/**
 * Phase 3.0 scaffold — Mix remains the production pipeline until Blade cutover (3.3).
 * Vue 3.5.40 @vue/compat MODE 2: alias vue → @vue/compat + compiler compatConfig
 * (official migration-build Vite example) + runtime configureCompat in app.js.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/js/app.js',
                'resources/assets/sass/app.scss',
                'resources/css/tailwind.css',
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
