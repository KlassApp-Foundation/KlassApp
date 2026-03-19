// Disable system notifications from webpack-notifier/node-notifier
// This prevents spawn errors on some macOS setups where a notifier binary fails.
process.env.DISABLE_NOTIFIER = "true";

const mix = require("laravel-mix");

// Disable desktop notifications (also available via mix.disableNotifications())
mix.disableNotifications();

require("laravel-mix-tailwind");
require("laravel-mix-purgecss");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js("resources/assets/js/app.js", "public/js")
    .sass("resources/assets/sass/app.scss", "public/css")
    .tailwind("./tailwind.config.js")
    .sourceMaps()
    .purgeCss();
// compile landing stylesheet into public/css/landing.css
mix.styles(["resources/css/landing.css"], "public/css/landing.css");

if (mix.inProduction()) {
    mix.version();
}
