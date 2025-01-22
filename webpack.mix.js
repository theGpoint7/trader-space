const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

mix.js('resources/js/app.js', 'public/js')
   .react() // If you are using React
   .postCss('resources/css/app.css', 'public/css', [
       tailwindcss('./tailwind.config.js'),
   ])
   .version(); // Enable versioning for cache busting in production

// Optional: Add source maps for easier debugging
if (!mix.inProduction()) {
    mix.sourceMaps();
}