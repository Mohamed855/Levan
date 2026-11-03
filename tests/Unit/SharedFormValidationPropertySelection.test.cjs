const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const source = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/form-validation.js"),
    "utf8",
).replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, "");

function loadSharedValidation() {
    const document = {
        readyState: "loading",
        addEventListener() {},
        querySelectorAll() {
            return [];
        },
    };
    const window = {
        addEventListener() {},
    };

    vm.runInNewContext(source, { document, window, setTimeout, clearTimeout });

    return window.SharedFormValidation;
}

function createNextButton() {
    const classes = new Set();

    return {
        disabled: false,
        style: {},
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
            contains: (name) => classes.has(name),
        },
        setAttribute() {},
        removeAttribute() {},
    };
}

function createStep({ id, fields, nextButton }) {
    return {
        id,
        dataset: { validationFields: JSON.stringify(fields) },
        querySelectorAll(selector) {
            return selector.includes("data-wizard-next") ? [nextButton] : [];
        },
    };
}

test("only a property_ids step disables Next until a property is selected", () => {
    const validation = loadSharedValidation();
    const formWithoutSelectedProperties = {
        querySelectorAll() {
            return [];
        },
    };

    const propertyCreateNext = createNextButton();
    validation.updateStepNextButton(
        formWithoutSelectedProperties,
        createStep({
            id: "property-basic-validation",
            fields: ["name", "type"],
            nextButton: propertyCreateNext,
        }),
    );

    assert.equal(propertyCreateNext.disabled, false);

    const selectionNext = createNextButton();
    validation.updateStepNextButton(
        formWithoutSelectedProperties,
        createStep({
            id: "assignment-properties-validation",
            fields: ["property_ids"],
            nextButton: selectionNext,
        }),
    );

    assert.equal(selectionNext.disabled, true);

    const selectedPropertyNext = createNextButton();
    validation.updateStepNextButton(
        {
            querySelectorAll() {
                return [{ value: "42" }];
            },
        },
        createStep({
            id: "auction-properties-validation",
            fields: ["property_ids"],
            nextButton: selectedPropertyNext,
        }),
    );

    assert.equal(selectedPropertyNext.disabled, false);
});
