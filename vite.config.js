import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

/**
 * Phase 3.3 — Blade layouts use @vite() for app.js / app.scss / tailwind.css.
 * Vue 3.5.40 @vue/compat MODE 2: alias vue → @vue/compat + compiler compatConfig
 * (official migration-build Vite example) + runtime configureCompat in app.js.
 *
 * CSS entries (mirrors Mix):
 * - resources/css/tailwind.css → @tailwindcss/vite (has @import "tailwindcss")
 * - resources/assets/sass/app.scss → Vite built-in Sass (Phase 2b: plain CSS in
 *   adminstyle/style — no @apply reintroduction)
 * - resources/css/landing.css → plain CSS entry (Mix mix.styles concat); no
 *   @import "tailwindcss", so @tailwindcss/vite does not utility-scan it.
 *   Wire into Blade only where Mix landing.css was used (not app layouts).
 *
 * Phase 3.1: app.js + bootstrap.js are ESM (no require). vite:dev and vite:build
 * both supported; production path remains vite:build without public/hot.
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
    // date-fns@1 (via vuejs-datetimepicker) is CJS; Vite ESM needs prebundle + needsInterop
    // for default export. FullCalendar v5 plugins must resolve after core is prebundled.
    optimizeDeps: {
        include: [
            'date-fns/start_of_month',
            'date-fns/end_of_month',
            'date-fns/each_day',
            'date-fns/get_day',
            'date-fns/format',
            'date-fns/start_of_day',
            'date-fns/is_equal',
            '@fullcalendar/core',
            '@fullcalendar/vue',
            '@fullcalendar/common',
            '@fullcalendar/daygrid',
            '@fullcalendar/timegrid',
            '@fullcalendar/interaction',
        ],
        needsInterop: [
            'date-fns/start_of_month',
            'date-fns/end_of_month',
            'date-fns/each_day',
            'date-fns/get_day',
            'date-fns/format',
            'date-fns/start_of_day',
            'date-fns/is_equal',
        ],
    },
});
