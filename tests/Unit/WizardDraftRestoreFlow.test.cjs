const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const formValidationSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/form-validation.js"),
    "utf8",
);
const cityDistrictSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/city-district-selector.js"),
    "utf8",
).replace("export function initCityDistrictSelector", "function initCityDistrictSelector");

for (const [scenario, invalidField, expectedIndex] of [
    ["all previous steps valid", null, 3],
    ["required password missing", "password", 1],
    ["property now in another active auction", "property_ids", 2],
]) {
    test(`draft uses guarded navigation once: ${scenario}`, async () => {
        const restore = formValidationSource.slice(formValidationSource.indexOf("async function restoreDraft(form)"), formValidationSource.indexOf("\nfunction initDrafts(form)"));
        const navigation = formValidationSource.slice(formValidationSource.indexOf("async function resolveDraftNavigation(form, defaultStep)"), formValidationSource.indexOf("\nasync function resolveDraftTargetStep"));
        const steps = ["name", "password", "property_ids", "notes"].map(field => ({ fields: [field] }));
        const moves = [];
        const directMoves = [];
        const changes = [];
        const validations = [];
        const state = { stepper: { to: index => directMoves.push(index) } };
        const form = {
            dataset: { draftUrl: "/drafts" },
            setAttribute() {}, removeAttribute() {}, insertAdjacentElement() {},
            closest: () => ({ querySelectorAll: () => steps }),
            querySelectorAll: () => steps,
            dispatchEvent(event) { if (event.type === "stepChanged") changes.push(event.detail.stepIndex); },
        };
        state.moveTo = index => {
            moves.push(index);
            form.dispatchEvent({ type: "stepChanged", detail: { stepIndex: index } });
        };
        const context = {
            form, hasVisibleServerErrors: () => false, draftResourceKey: () => "fixture",
            fetch: async () => ({ ok: true, json: async () => ({ draft_id: "saved", step: 4, data: {}, files: {} }) }),
            window: { confirmActionAsync: async () => true, toastr: { success() {}, error() {} } },
            document: { createElement: () => ({ addEventListener() {} }) },
            CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
            getFormState: () => state,
            addHiddenField() {}, restoreDraftValues() {}, restoreDraftFiles: async () => {}, updateStepNextButton() {},
            validationFieldsFromStep: step => step.fields,
            validateFields: async (_form, fields, options) => {
                assert.equal(options.force, true);
                validations.push(fields[0]);
                return !fields.includes(invalidField);
            },
            setTimeout,
        };
        await vm.runInNewContext(`${navigation}\n${restore}\nrestoreDraft(form)`, context);
        assert.deepEqual(moves, [expectedIndex]);
        assert.deepEqual(directMoves, []);
        assert.deepEqual(changes, [expectedIndex]);
        assert.deepEqual(validations, ["name", "password", "property_ids"].slice(0, invalidField ? expectedIndex + 1 : 3));
        assert.equal(form.inert, false);
    });
}

test("draft values never restore password controls", () => {
    const restore = formValidationSource.slice(formValidationSource.indexOf("function restoreDraftValues(form, values, prefix"), formValidationSource.indexOf("\nasync function discardDraft(form)"));
    const controls = {
        name: { type: "text", value: "", dispatchEvent() {} },
        password: { type: "password", value: "", dispatchEvent() {} },
        password_confirmation: { type: "password", value: "", dispatchEvent() {} },
    };
    const context = { form: { elements: { namedItem: name => controls[name] } }, window: {}, RadioNodeList: class {}, Event: class {} };
    vm.runInNewContext(`${restore}\nrestoreDraftValues(form, {name:'Saved name', password:'secret', password_confirmation:'secret'});`, context);
    assert.equal(controls.name.value, "Saved name");
    assert.equal(controls.password.value, "");
    assert.equal(controls.password_confirmation.value, "");
});

test("draft restoration waits for registered dependent fields before resolving its destination", async () => {
    const restore = formValidationSource.slice(
        formValidationSource.indexOf("async function restoreDraft(form)"),
        formValidationSource.indexOf("\nfunction initDrafts(form)"),
    );
    let dependencyResolved = false;
    let releaseDependency;
    let navigationSawResolvedDependency = false;
    const form = {
        dataset: { draftUrl: "/wizard-drafts" },
        setAttribute() {},
        removeAttribute() {},
        insertAdjacentElement() {},
        closest() {
            return null;
        },
        dispatchEvent(event) {
            if (event.type !== "wizardDraftRestored") return;
            event.detail.waitUntil(
                new Promise((resolve) => {
                    releaseDependency = () => {
                        dependencyResolved = true;
                        resolve();
                    };
                }),
            );
        },
    };
    const context = {
        form,
        hasVisibleServerErrors: () => false,
        draftResourceKey: () => "tenant.property-create",
        fetch: async () => ({
            ok: true,
            json: async () => ({ draft_id: "draft-1", data: {}, files: {}, step: 2 }),
        }),
        window: { confirmActionAsync: async () => true, toastr: { success() {}, error() {} } },
        document: { createElement: () => ({ addEventListener() {} }) },
        CustomEvent: class {
            constructor(type, options) {
                this.type = type;
                this.detail = options.detail;
            }
        },
        addHiddenField() {},
        restoreDraftValues() {},
        restoreDraftFiles: async () => {},
        resolveDraftNavigation: async () => {
            navigationSawResolvedDependency = dependencyResolved;
            return { targetStep: 1, targetControl: null };
        },
        getFormState: () => ({ stepper: null }),
        setTimeout,
    };

    const restoring = vm.runInNewContext(`${restore}\nrestoreDraft(form)`, context);
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(typeof releaseDependency, "function");
    assert.equal(navigationSawResolvedDependency, false);

    releaseDependency();
    await restoring;

    assert.equal(navigationSawResolvedDependency, true);
});

test("draft navigation returns to the first invalid step before the saved step", async () => {
    const navigation = formValidationSource.slice(
        formValidationSource.indexOf("async function resolveDraftNavigation(form, defaultStep)"),
        formValidationSource.indexOf("\nasync function resolveDraftTargetStep"),
    );
    const steps = [
        { fields: ["password"] },
        { fields: ["name"] },
        { fields: ["notes"] },
    ];
    const context = {
        form: {
            closest: () => ({ querySelectorAll: () => steps }),
            querySelectorAll: () => [],
            querySelector: () => null,
        },
        validationFieldsFromStep: (step) => step.fields,
        validateFields: async (_form, fields) => !fields.includes("password"),
        Array,
        Math,
    };

    vm.runInNewContext(`${navigation}\nthis.resolve = resolveDraftNavigation;`, context);

    const result = await context.resolve(context.form, 3);

    assert.equal(result.targetStep, 1);
});

test("city and district are restored together after district options finish loading", async () => {
    const city = { value: "Riyadh", options: [], handlers: new Map(), name: "city" };
    const district = { value: "", options: [], handlers: new Map(), name: "district" };
    const formListeners = new Map();
    const form = {
        addEventListener(type, handler) {
            formListeners.set(type, handler);
        },
    };
    city.closest = () => form;
    const pendingRequests = [];

    function wrap(element) {
        return {
            length: element ? 1 : 0,
            on(type, handler) {
                element.handlers.set(type, handler);
                return this;
            },
            val(value) {
                if (value === undefined) return element.value;
                element.value = String(value);
                return this;
            },
            prop(name, value) {
                if (value === undefined) return element[name];
                element[name] = value;
                return this;
            },
            empty() {
                element.options = [];
                element.value = "";
                return this;
            },
            append(option) {
                element.options.push(option);
                if (option.selected) element.value = String(option.value);
                return this;
            },
            trigger() {
                return this;
            },
            attr(name) {
                return element[name];
            },
            get() {
                return element;
            },
        };
    }

    const context = {
        window: { jQuery: (selector) => wrap(selector === "#city" || selector === city ? city : district), routes: { districts: "/districts" } },
        document: { querySelector: (selector) => (selector === "#city" ? city : district) },
        Option: function Option(text, value, _defaultSelected, selected) {
            this.text = text;
            this.value = value;
            this.selected = selected;
        },
        fetch: () => new Promise((resolve) => pendingRequests.push(resolve)),
        encodeURIComponent,
        console,
    };

    vm.runInNewContext(`${cityDistrictSource}\nthis.init = initCityDistrictSelector;`, context);
    context.init({ citySelector: "#city", districtSelector: "#district" });
    city.handlers.get("change").call(city);

    const tasks = [];
    const restoredHandler = formListeners.get("wizardDraftRestored");
    assert.equal(typeof restoredHandler, "function");
    restoredHandler({
        detail: {
            data: { district: "2" },
            waitUntil(task) {
                tasks.push(task);
            },
        },
    });
    assert.equal(tasks.length, 1);

    const response = {
        ok: true,
        json: async () => ({ districts: [{ id: 2, text: "Al Olaya" }] }),
    };
    pendingRequests[0](response);
    pendingRequests[1](response);
    await Promise.all(tasks);

    assert.equal(district.value, "2");
    assert.equal(district.options[1].text, "Al Olaya");
});
