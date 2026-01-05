import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
// import i18n from "laravel-vue-i18n/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                base: null,
                includeAbsolute: false,
            },
        }),
        // i18n(), // Desabilitado temporariamente para resolver erro
    ],
    resolve: {
        alias: {},
    },
    build: {
        chunkSizeWarningLimit: 2024,
        rollupOptions: {
            external: [
                '/resources/assets/images/mines-cover.png'
            ]
        }
    },
    optimizeDeps: {
        esbuildOptions: {
            // Node.js global to browser globalThis
            define: {
                global: "globalThis",
            },
        },
    },
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false
    }
});
