import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                /* @chisel-passkeys */
                'resources/js/passkeys.js',
                /* @end-chisel-passkeys */
            ],
            refresh: true,
            // Font Poppins (judul) dan Inter (isi) didaftarkan di resources/css/app.css
            // pada --font-display dan --font-sans dengan fallback system-ui.
            // Pengunduhan font otomatis via plugin "bunny" sengaja tidak dipakai
            // karena jaringan lokal sering timeout ke fonts.bunny.net dan membuat
            // build gagal. Kalau jaringan stabil, bisa diaktifkan kembali di sini:
            //
            // fonts: [
            //     bunny('Poppins', { weights: [400, 500, 600, 700] }),
            //     bunny('Inter', { weights: [400, 500, 600] }),
            // ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
