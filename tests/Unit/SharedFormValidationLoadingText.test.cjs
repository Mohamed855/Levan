const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const formValidationSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/shared/form-validation.js"),
    "utf8",
).replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, "");

const checkoutSource = readFileSync(
    path.resolve(__dirname, "../../resources/assets/js/web/checkout.js"),
    "utf8",
);

function createClassList(initial = []) {
    const classes = new Set(initial);
    return {
        add: (...names) => names.forEach((n) => classes.add(n)),
        remove: (...names) => names.forEach((n) => classes.delete(n)),
        contains: (name) => classes.has(name),
    };
}

function createFakeEnvironment({ form, submitButton }) {
    const docListeners = { capture: [], bubble: [] };

    class FakeHTMLFormElement {}
    Object.setPrototypeOf(form, FakeHTMLFormElement.prototype);

    const document = {
        readyState: "complete",
        title: "Checkout",
        addEventListener(type, fn, options) {
            if (type !== "submit" && type !== "DOMContentLoaded") return;
            if (type === "DOMContentLoaded") {
                docListeners.domReady = docListeners.domReady || [];
                docListeners.domReady.push(fn);
                return;
            }
            const capture = options === true || Boolean(options?.capture);
            (capture ? docListeners.capture : docListeners.bubble).push(fn);
        },
        querySelectorAll(selector) {
            if (selector === "form" || selector === "form[data-form-validate]") {
                return [];
            }
            return [];
        },
        querySelector(selector) {
            if (selector === "#payment-form") return form;
            if (selector === "#submit-payment-btn") return submitButton;
            return null;
        },
        getElementById(id) {
            if (id === "submit-payment-btn") return submitButton;
            return null;
        },
        createElement(tag) {
            return {
                tagName: tag.toUpperCase(),
                _text: "",
                set textContent(v) {
                    this._text = String(v ?? "");
                },
                get textContent() {
                    return this._text;
                },
                get innerHTML() {
                    return this._text
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;");
                },
            };
        },
    };

    const window = {
        addEventListener() {},
        location: { href: "https://example.test/checkout/1/details" },
        history: { replaceState() {} },
        document,
    };

    const sandbox = {
        document,
        window,
        location: window.location,
        history: window.history,
        addEventListener() {},
        HTMLFormElement: FakeHTMLFormElement,
        HTMLElement: function () {},
        Event: class FakeEvent {
            constructor(type, init = {}) {
                this.type = type;
                this.bubbles = Boolean(init.bubbles);
            }
        },
        CustomEvent: class FakeCustomEvent {},
        CSS: { escape: (s) => s },
        initAllFileUploadZones() {},
        getFileUploadController() { return null; },
        setFilesOnUploadZone() {},
        setTimeout,
        clearTimeout,
        console,
    };
    sandbox.window = sandbox;

    vm.runInNewContext(formValidationSource, sandbox);

    return { sandbox, docListeners, document };
}

function makeButton(initialText) {
    const attrs = {};
    let inner = `<span class="btn-text">${initialText}</span>`;
    let text = initialText;
    const textNode = {
        get textContent() {
            return text;
        },
        set textContent(val) {
            text = String(val ?? "");
            inner = `<span class="btn-text">${text}</span>`;
        },
    };

    return {
        type: "submit",
        disabled: false,
        dataset: {},
        style: {},
        classList: createClassList(),
        setAttribute(k, v) {
            attrs[k] = String(v);
        },
        getAttribute(k) {
            return attrs[k] ?? null;
        },
        removeAttribute(k) {
            delete attrs[k];
        },
        matches() {
            return false;
        },
        querySelector(sel) {
            if (sel === ".btn-text" && inner.includes("btn-text")) {
                return textNode;
            }
            return null;
        },
        get textContent() {
            return text;
        },
        set textContent(val) {
            text = String(val ?? "");
            inner = text;
        },
        get innerHTML() {
            return inner;
        },
        set innerHTML(val) {
            inner = String(val ?? "");
            text = inner.replace(/<[^>]+>/g, "");
        },
    };
}

test("getButtonLoadingText strips leading جاري/جارٍ/جارى/جار and trailing ellipsis to prevent duplication", () => {
    const form = {
        dataset: {},
        classList: createClassList(),
        closest() {
            return null;
        },
        querySelector() {
            return null;
        },
        querySelectorAll() {
            return [];
        },
        addEventListener() {},
        checkValidity() {
            return true;
        },
    };
    const { sandbox } = makeBasicEnv(form);
    const { getButtonLoadingText } = sandbox.SharedFormValidation;

    assert.equal(getButtonLoadingText(makeButton("حفظ")), "جاري الحفظ...");
    assert.equal(getButtonLoadingText(makeButton("تحديث")), "جاري التحديث...");
    assert.equal(getButtonLoadingText(makeButton("حفظ التعديلات")), "جاري حفظ التعديلات...");
    assert.equal(getButtonLoadingText(makeButton("حفظ المسودة")), "جاري حفظ المسودة...");
    assert.equal(getButtonLoadingText(makeButton("تحديث البيانات")), "جاري تحديث البيانات...");
    assert.equal(getButtonLoadingText(makeButton("إرسال الطلب")), "جاري إرسال الطلب...");
    assert.equal(getButtonLoadingText(makeButton("متابعة إلى الدفع")), "جاري متابعة إلى الدفع...");

    // Already prefixed loading strings must never produce "جاري جاري ..." or "......"
    assert.equal(getButtonLoadingText(makeButton("جاري الإرسال...")), "جاري الإرسال...");
    assert.equal(getButtonLoadingText(makeButton("جاري جاري الإرسال...")), "جاري الإرسال...");
    assert.equal(getButtonLoadingText(makeButton("جارٍ الإرسال...")), "جاري الإرسال...");
    assert.equal(getButtonLoadingText(makeButton("جارى الارسال")), "جاري الارسال...");
    assert.equal(getButtonLoadingText(makeButton("جار الاعتماد والحفظ")), "جاري الاعتماد والحفظ...");
    assert.equal(getButtonLoadingText(makeButton("جاري المعالجة...")), "جاري المعالجة...");
    assert.equal(getButtonLoadingText(makeButton("جاري الحفظ...")), "جاري الحفظ...");
    assert.equal(getButtonLoadingText(makeButton("جاري التحديث...")), "جاري التحديث...");
    assert.equal(getButtonLoadingText(makeButton("جاري...")), "جاري المعالجة...");
    assert.equal(getButtonLoadingText(makeButton("")), "جاري الحفظ...");
});

function makeBasicEnv(form) {
    return createFakeEnvironment({ form, submitButton: makeButton("حفظ") });
}

test("checkout.js payment submit coordinates with SharedFormValidation without duplicating جاري", () => {
    const submitBtn = makeButton("متابعة إلى الدفع");
    const formListeners = { capture: [], bubble: [], passed: [] };

    const form = {
        id: "payment-form",
        dataset: { checkoutStep: "payment" },
        classList: createClassList(),
        closest() {
            return null;
        },
        querySelector(sel) {
            if (sel === 'input[name="payment_method"]:checked') {
                return { value: "card" };
            }
            if (sel.includes('button[type="submit"]')) {
                return submitBtn;
            }
            return null;
        },
        querySelectorAll() {
            return [];
        },
        addEventListener(type, fn, capture) {
            if (type === "formValidationPassed") {
                formListeners.passed.push(fn);
                return;
            }
            if (type !== "submit") return;
            (capture ? formListeners.capture : formListeners.bubble).push(fn);
        },
        checkValidity() {
            return true;
        },
    };
    submitBtn.form = form;

    const { sandbox, docListeners } = createFakeEnvironment({
        form,
        submitButton: submitBtn,
    });

    vm.runInNewContext(checkoutSource, sandbox);
    for (const fn of docListeners.domReady || []) fn();

    const event = {
        target: form,
        submitter: submitBtn,
        defaultPrevented: false,
        preventDefault() {
            this.defaultPrevented = true;
        },
        stopImmediatePropagation() {},
    };

    for (const fn of formListeners.capture) fn(event);
    for (const fn of docListeners.bubble) fn(event);

    assert.equal(submitBtn.textContent.trim(), "جاري المعالجة...");
    assert.equal(submitBtn.dataset.buttonIsLoading, "true");
    assert.ok(!submitBtn.textContent.includes("جاري جاري"));
});

test("global submit fallback still avoids double جاري when button text was pre-mutated without disabling", () => {
    const submitBtn = makeButton("جاري الإرسال...");
    const form = {
        id: "custom-form",
        dataset: {},
        classList: createClassList(),
        closest() {
            return null;
        },
        querySelector() {
            return submitBtn;
        },
        querySelectorAll() {
            return [];
        },
        addEventListener() {},
        checkValidity() {
            return true;
        },
    };
    submitBtn.form = form;

    const { docListeners } = createFakeEnvironment({
        form,
        submitButton: submitBtn,
    });

    const event = {
        target: form,
        submitter: submitBtn,
        defaultPrevented: false,
        preventDefault() {},
    };

    for (const fn of docListeners.bubble) fn(event);

    assert.equal(submitBtn.textContent.trim(), "جاري الإرسال...");
});

test("global submit fallback does not double-prefix button text when another submit handler sets loading state", () => {
    const submitBtn = makeButton("متابعة إلى الدفع");
    const formListeners = { capture: [], bubble: [] };

    const form = {
        id: "payment-form",
        dataset: { checkoutStep: "payment" },
        classList: createClassList(),
        closest() {
            return null;
        },
        querySelector(sel) {
            if (sel === 'input[name="payment_method"]:checked') {
                return { value: "card" };
            }
            if (sel.includes('button[type="submit"]')) {
                return submitBtn;
            }
            return null;
        },
        querySelectorAll() {
            return [];
        },
        addEventListener(type, fn, capture) {
            if (type !== "submit") return;
            (capture ? formListeners.capture : formListeners.bubble).push(fn);
        },
        checkValidity() {
            return true;
        },
    };
    submitBtn.form = form;

    const { sandbox, docListeners } = createFakeEnvironment({
        form,
        submitButton: submitBtn,
    });

    // Simulate checkout.js capture listener changing button text to " جاري المعالجة..."
    formListeners.capture.push(() => {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري المعالجة...';
    });

    // Simulate a form submit event: capture on form -> bubble on document
    const event = {
        target: form,
        submitter: submitBtn,
        defaultPrevented: false,
        preventDefault() {
            this.defaultPrevented = true;
        },
        stopImmediatePropagation() {},
    };

    for (const fn of formListeners.capture) fn(event);
    for (const fn of docListeners.bubble) fn(event);

    assert.equal(submitBtn.textContent.trim(), "جاري المعالجة...");
    assert.ok(!submitBtn.textContent.includes("جاري جاري"));
});

test("global submit fallback skips Moyasar payment forms (.mysr-form)", () => {
    const submitBtn = makeButton("ادفع");
    const form = {
        id: "",
        dataset: {},
        classList: createClassList(["mysr-form"]),
        closest(sel) {
            return sel === ".mysr-form" ? this : null;
        },
        querySelector() {
            return submitBtn;
        },
        querySelectorAll() {
            return [];
        },
        addEventListener() {},
        checkValidity() {
            return true;
        },
    };
    submitBtn.form = form;

    const { docListeners } = createFakeEnvironment({
        form,
        submitButton: submitBtn,
    });

    const event = {
        target: form,
        submitter: submitBtn,
        defaultPrevented: false,
        preventDefault() {},
    };

    for (const fn of docListeners.bubble) fn(event);

    assert.equal(submitBtn.textContent.trim(), "ادفع");
});
