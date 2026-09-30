import html from "@rollup/plugin-html";
import { readFileSync } from "node:fs";
import { glob } from "glob";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";
// import vue from '@vitejs/plugin-vue';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function getBladeViteEntries() {
    const entries = new Set([
        "resources/assets/vendor/scss/rtl/core.scss",
        "resources/assets/vendor/scss/rtl/theme-semi-dark.scss",
    ]);

    for (const viewPath of glob.sync("resources/views/**/*.blade.php")) {
        const view = readFileSync(viewPath, "utf8");
        const directives = view.matchAll(/@vite\s*\((?:\[(?:.|\n)*?\]|'[^']+'|\"[^\"]+\")\)/g);

        for (const directive of directives) {
            for (const asset of directive[0].matchAll(/['\"](resources\/[^'\"]+\.(?:css|scss|js))['\"]/g)) {
                if (!asset[1].endsWith("template-customizer.js")) entries.add(asset[1]);
            }
        }
    }

    return [...entries];
}

const bladeViteEntries = getBladeViteEntries();

// Processing Window Assignment for Libs like jKanban, pdfMake
function libsWindowAssignment() {
    return {
        name: "libsWindowAssignment",
        transform(src, id) {
            if (id.includes("jkanban.js")) {
                return src.replace("this.jKanban", "window.jKanban");
            } else if (id.includes("vfs_fonts")) {
                return src.replaceAll("this.pdfMake", "window.pdfMake");
            }
        },
    };
}

export default defineConfig({
    build: {
        // PDFMake is loaded only by the DataTables PDF export path.
        chunkSizeWarningLimit: 2048,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules/pdfmake")) {
                        return "pdfmake";
                    }

                    if (id.includes("node_modules/jszip")) {
                        return "jszip";
                    }

                    if (
                        id.includes("node_modules/datatables.net") ||
                        id.includes("node_modules/jquery-datatables-checkboxes")
                    ) {
                        return "datatables";
                    }
                },
            },
        },
    },
    plugins: [
        laravel({
            input: bladeViteEntries,
            refresh: true,
        }),
        html(),

        libsWindowAssignment(),
    ],
});
