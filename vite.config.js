import html from "@rollup/plugin-html";
import { glob } from "glob";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";
// import vue from '@vitejs/plugin-vue';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
    return glob.sync(query);
}

// alias صغير
const getFiles = GetFilesArray;
/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray("resources/assets/js/*.js");

// Page JS Files
const pageCssFiles = GetFilesArray("resources/assets/css/*.css");

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray("resources/assets/vendor/js/*.js");

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray("resources/assets/vendor/libs/**/*.js");

/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray(
    "resources/assets/vendor/scss/**/!(_)*.scss",
);

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray(
    "resources/assets/vendor/libs/**/!(_)*.scss",
);
const LibsCssFiles = GetFilesArray("resources/assets/vendor/libs/**/*.css");

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray(
    "resources/assets/vendor/fonts/!(_)*.scss",
);

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
            input: [
                "resources/css/app.css",
                ...pageCssFiles,
                "resources/js/app.js",
                "resources/css/dataTable.css",
                "resources/css/toastr.css",
                "resources/js/toastr.js",
                "resources/js/task-modal-shared.js",
                "resources/css/printTemplate.css",
                "resources/assets/js/site/landing.js",
                ...pageJsFiles,
                ...vendorJsFiles,
                ...LibsJsFiles,
                ...CoreScssFiles,
                ...LibsScssFiles,
                ...LibsCssFiles,
                ...FontsScssFiles,
                ...getFiles("resources/assets/css/*.css"),
                ...getFiles("resources/assets/css/**/*.css"),
                ...getFiles("resources/assets/css/**/**/*.css"),
                ...getFiles("resources/assets/css/**/**/**/*.css"),
                ...getFiles("resources/assets/css/**/**/**/**/*.css"),

                ...getFiles("resources/assets/js/*.js"),
                ...getFiles("resources/assets/js/**/*.js"),
                ...getFiles("resources/assets/js/**/**/*.js"),
                ...getFiles("resources/assets/js/**/**/**/*.js"),
                ...getFiles("resources/assets/js/**/**/**/**/*.js"),
            ],
            refresh: true,
        }),
        html(),

        libsWindowAssignment(),
    ],
});
