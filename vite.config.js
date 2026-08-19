import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Inter', {
                    weights: [300, 400, 500, 600, 700],
                    subsets: ['latin'],
                    // Do not preload: preloading forces every unicode-range subset file
                    // to download at high priority (defeating unicode-range subsetting) and
                    // competes with the LCP hero image. With font-display: swap the browser
                    // lazily fetches only the Latin subset per weight as text needs it.
                    preload: false,
                }),
                bunny('Noto Sans Bengali', {
                    weights: [400, 500, 600, 700],
                    subsets: ['bengali', 'latin'],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
