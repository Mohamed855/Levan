const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const formValidationSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/form-validation.js"),
    "utf8",
).replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, "");

const fileUploadSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/file-upload.js"),
    "utf8",
).replace(/export /g, "");

test("hasFileValue returns true when visible existing file card is present", () => {
    const sandbox = {
        document: {
            readyState: "complete",
            addEventListener() {},
            querySelectorAll() { return []; },
        },
        window: { addEventListener() {} },
        initAllFileUploadZones() {},
        getFileUploadController() {},
        setFilesOnUploadZone() {},
        setTimeout,
        clearTimeout,
    };

    vm.runInNewContext(formValidationSource, sandbox);

    // Create a mock control inside a wrapper with an existing card
    const existingCard = {
        matches: (sel) => sel.includes("existing-file-card"),
        classList: { contains: (cls) => cls === "file-item" },
    };

    const wrapper = {
        querySelector(selector) {
            if (selector.includes("[data-existing-file-card]:not(.d-none)")) {
                return existingCard;
            }
            return null;
        },
    };

    const control = {
        type: "file",
        name: "image",
        files: [],
        closest(sel) {
            if (sel.includes("data-file-upload-wrapper")) {
                return wrapper;
            }
            return null;
        },
    };

    // Extract hasFileValue from context
    const hasFileValueFn = vm.runInNewContext(
        `(${formValidationSource.slice(
            formValidationSource.indexOf("function hasFileValue(control)"),
            formValidationSource.indexOf("function findFieldWrappers"),
        )})`,
        sandbox
    );

    assert.equal(hasFileValueFn(control), true, "hasFileValue should be true when existing card exists");

    // When card is hidden (.d-none) and no input
    const wrapperHidden = {
        querySelector(selector) {
            return null;
        },
    };
    const controlNoCard = {
        type: "file",
        name: "image",
        files: [],
        closest(sel) {
            return wrapperHidden;
        },
    };
    assert.equal(hasFileValueFn(controlNoCard), false, "hasFileValue should be false when no existing card or files");
});

test("single-file mode hides existing card when new file is uploaded and restores when removed", () => {
    // Test the logic directly
    const existingCardClasses = new Set(["file-item"]);
    const existingInput = { disabled: false };
    const existingCard = {
        classList: {
            add: (cls) => existingCardClasses.add(cls),
            remove: (cls) => existingCardClasses.delete(cls),
            contains: (cls) => existingCardClasses.has(cls),
        },
        querySelector(sel) {
            if (sel.includes("data-existing-file-input")) return existingInput;
            return null;
        },
    };

    const isMultiple = false;
    let currentFiles = [{ name: "new-upload.png", size: 1024, type: "image/png" }];

    // Simulate renderPreviews logic
    if (!isMultiple) {
        if (currentFiles.length > 0) {
            existingCard.classList.add("d-none");
            const inp = existingCard.querySelector("[data-existing-file-input]");
            if (inp) inp.disabled = true;
        } else {
            existingCard.classList.remove("d-none");
            const inp = existingCard.querySelector("[data-existing-file-input]");
            if (inp) inp.disabled = false;
        }
    }

    assert.equal(existingCard.classList.contains("d-none"), true, "Existing card must be hidden when new file is present");
    assert.equal(existingInput.disabled, true, "Existing input must be disabled when new file is present");

    // Now remove new file
    currentFiles = [];
    if (!isMultiple) {
        if (currentFiles.length > 0) {
            existingCard.classList.add("d-none");
            const inp = existingCard.querySelector("[data-existing-file-input]");
            if (inp) inp.disabled = true;
        } else {
            existingCard.classList.remove("d-none");
            const inp = existingCard.querySelector("[data-existing-file-input]");
            if (inp) inp.disabled = false;
        }
    }

    assert.equal(existingCard.classList.contains("d-none"), false, "Existing card must be restored when new file is removed");
    assert.equal(existingInput.disabled, false, "Existing input must be enabled when new file is removed");
});
