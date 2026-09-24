import "datatables.net-bs5";
import "datatables.net-buttons";
import "datatables.net-buttons-bs5";
import "datatables.net-buttons/js/buttons.html5";
import "datatables.net-buttons/js/buttons.print";
import "datatables.net-fixedcolumns-bs5";
import "datatables.net-fixedheader-bs5";
import "datatables.net-responsive";
import "datatables.net-responsive-bs5";
import "datatables.net-rowgroup-bs5";
import "datatables.net-select-bs5";
import Checkbox from "jquery-datatables-checkboxes";
import JSZip from "jszip";

$.fn.dataTable.ext.Checkbox = Checkbox(window, $);
window.JSZip = JSZip;

Promise.all([import("pdfmake"), import("pdfmake/build/vfs_fonts")]).then(
    ([{ default: pdfMake }]) => {
        if (window.pdfMake?.vfs) {
            pdfMake.vfs = window.pdfMake.vfs;
        }

        pdfMake.fonts = {
            Roboto: {
                normal: "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Regular.ttf",
                bold: "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Medium.ttf",
                italics:
                    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Italic.ttf",
                bolditalics:
                    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-MediumItalic.ttf",
            },
        };

        $.fn.dataTable.Buttons.pdfMake(pdfMake);
    },
);
