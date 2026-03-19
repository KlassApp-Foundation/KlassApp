module.exports = {
      purge: false,
       content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
         './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        fontFamily: {
            'primary': ['Nunito Sans', 'Roboto','sans-serif'],
            'exo': ['Exo\\ 2', 'sans-serif'],
            'plex': ['IBM\\ Plex\\ Sans', 'sans-serif']
        },
        extend: {}
  },
  variants: {},
  plugins: []
}
