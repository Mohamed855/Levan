const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const source = readFileSync(path.resolve(__dirname, "../../resources/assets/js/tenant/assignments/create.js"), "utf8");

class Element {
    constructor(properties = {}) {
        Object.assign(this, {
            dataset: {},
            innerHTML: "",
            textContent: "",
            listeners: new Map(),
            classList: { remove() {}, add() {} },
            style: {},
        }, properties);
    }

    addEventListener(type, callback) {
        this.listeners.set(type, [...(this.listeners.get(type) || []), callback]);
    }

    dispatchEvent(event) {
        for (const callback of this.listeners.get(event.type) || []) callback(event);
    }

    querySelector() { return null; }
    querySelectorAll() { return []; }
    removeAttribute() {}
    setAttribute() {}
}

function mount(mode) {
    const existingCard = new Element({ dataset: { propertyCard: "11" } });
    existingCard.querySelector = (selector) => ({
        ".fw-semibold": { textContent: "Existing property" },
        code: { textContent: "P-11" },
        img: { src: "/existing.png" },
        ".text-muted": { textContent: "P-11 · Villa · Riyadh" },
    })[selector] || null;
    const inputs = new Element();
    inputs.querySelectorAll = () => mode === "edit"
        ? [new Element({ value: "11", dataset: { propertyInput: "11" } })] : [];
    const cards = new Element();
    cards.querySelectorAll = () => mode === "edit" ? [existingCard] : [];
    const count = new Element({ textContent: mode === "edit" ? "1" : "0" });
    const basic = new Element();
    const review = new Element();
    const form = new Element({
        id: `assignment-${mode}-form`,
        dataset: { propertyImageFallback: "/fallback.svg" },
        elements: { namedItem: (name) => name === "name" ? { value: "My assignment" } : null },
    });
    const nodes = new Map([
        [form.id, form], ["selected-property-inputs", inputs],
        ["selected-properties-cards", cards], ["selected-properties-count", count],
        ["review-basic", basic], ["review-properties", review],
    ]);
    const checkbox = new Element({
        value: "22", checked: true,
        dataset: { name: "New property", number: "P-22", type: "Land", city: "Jeddah" },
    });
    checkbox.closest = (selector) => selector === ".property-selector" ? checkbox : null;
    const document = new Element();
    document.getElementById = (id) => nodes.get(id) || null;
    document.querySelector = (selector) => selector.split(",").some((id) => id.trim() === `#${form.id}`) ? form : null;
    document.querySelectorAll = (selector) => selector === ".property-selector" ? [checkbox] : [];
    document.createElement = () => {
        let text = "";
        return {
            set textContent(value) { text = value; },
            get innerHTML() { return text.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;"); },
        };
    };

    vm.runInNewContext(source, {
        document, window: { location: { origin: "https://tenant.example" } },
        Event: class { constructor(type) { this.type = type; } }, URL,
    });
    document.dispatchEvent({ type: "DOMContentLoaded" });

    return { form, document, inputs, cards, count, basic, review, checkbox };
}

test("edit initializes existing selections, synchronizes added/removed properties, and builds review", () => {
    const page = mount("edit");
    assert.match(page.basic.innerHTML, /My assignment/);
    assert.match(page.review.innerHTML, /Existing property/);

    page.checkbox.checked = true;
    page.document.dispatchEvent({ type: "change", target: page.checkbox });
    assert.equal(page.count.textContent, "2");
    assert.match(page.inputs.innerHTML, /value="11"/);
    assert.match(page.inputs.innerHTML, /value="22"/);
    assert.match(page.review.innerHTML, /New property/);

    const removeButton = { dataset: { removeProperty: "11" } };
    page.cards.dispatchEvent({ type: "click", target: { closest: () => removeButton } });
    assert.equal(page.count.textContent, "1");
    assert.doesNotMatch(page.inputs.innerHTML, /value="11"/);
    assert.match(page.inputs.innerHTML, /value="22"/);
});

test("create still synchronizes selected property IDs and review", () => {
    const page = mount("create");
    page.checkbox.checked = true;
    page.document.dispatchEvent({ type: "change", target: page.checkbox });
    assert.equal(page.count.textContent, "1");
    assert.match(page.inputs.innerHTML, /value="22"/);
    assert.match(page.review.innerHTML, /New property/);
});
