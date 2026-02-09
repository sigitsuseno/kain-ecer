import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            registerType: "autoUpdate",
            injectRegister: "script", // <--- PENTING: Vite akan suntikkan script pendaftaran otomatis
            devOptions: {
                enabled: true, // <--- WAJIB: Supaya PWA aktif saat kamu pakai 'npm run dev'
            },
            manifest: {
                name: "POS Kasir Eceran",
                short_name: "KasirEcer",
                display: "standalone",
                start_url: "/",
                icons: [
                    {
                        src: "/icons/icon-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/icons/icon-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
