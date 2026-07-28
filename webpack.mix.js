process.env.DISABLE_NOTIFIER = "true";

const path = require("path");
const mix = require("laravel-mix");

mix.disableNotifications();

mix.js("resources/assets/js/app.js", "public/js")
    .vue()
    .sass("resources/assets/sass/app.scss", "public/css")
    .options({
        postCss: [
            require("@tailwindcss/postcss"),
        ],
    })
    .sourceMaps();

mix.css("resources/css/tailwind.css", "public/css", [
    require("@tailwindcss/postcss"),
]);

mix.styles(["resources/css/landing.css"], "public/css/landing.css");

mix.webpackConfig({
    resolve: {
        alias: {
            vue$: "vue/dist/vue.esm.js",
        },
        modules: [
            "node_modules",
            path.resolve("public"),
            path.resolve("resources/assets"),
        ],
    },
});
