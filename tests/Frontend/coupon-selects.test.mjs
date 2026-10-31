import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../resources/assets/js/admin/coupons/form-ui.js', import.meta.url), 'utf8');

function couponForm(sharedInitialized = false) {
    const documentListeners = {};
    const form = { addEventListener() {}, querySelector: () => null };
    const controls = ['discount_type', 'is_active', 'users'].map((id) => ({
        id, value: id === 'discount_type' ? 'fixed' : id === 'is_active' ? '0' : '7',
        initialized: sharedInitialized, native: {}, jquery: {}, dispatches: 0,
        addEventListener(type, callback) { (this.native[type] ||= []).push(callback); },
        fire(type) {
            [...(this.jquery[type] || []), ...(this.native[type] || [])].forEach((callback) => callback.call(this));
        },
        dispatchEvent(event) {
            this.dispatches++;
            // Bound the faulty recursive path so a regression fails without overflowing Node's stack.
            if (this.dispatches < 8) this.fire(event.type);
            return true;
        },
    }));
    const maxInput = { value: '50', disabled: false };
    const maxField = { style: {}, querySelector: () => maxInput };
    const valueInput = { setAttribute() {}, removeAttribute() {} };
    const elements = Object.fromEntries(controls.map((control) => [control.id, control]));
    Object.assign(elements, { 'coupon-wizard-form': form, maxDiscountField: maxField, discount_value: valueInput });

    const jquery = (element) => {
        const wrapper = {
            find: () => ({ each(callback) { controls.forEach((control) => callback.call(control)); } }),
            hasClass: () => element.initialized,
            wrap: () => wrapper,
            data: () => '',
            parent: () => wrapper,
            val: () => element.value,
            select2() { element.initialized = true; return wrapper; },
            on(events, selector, callback) {
                const handler = typeof selector === 'function' ? selector : callback;
                if (element.jquery) {
                    events.split(' ').forEach((event) => {
                        (element.jquery[event.split('.')[0]] ||= []).push(handler);
                    });
                }
                return wrapper;
            },
        };
        return wrapper;
    };
    const document = {
        addEventListener: (name, callback) => { documentListeners[name] = callback; },
        getElementById: (id) => elements[id] || null,
        querySelector: () => form,
    };
    const window = { jQuery: jquery, SharedFormValidation: { clearFieldError() {} } };
    vm.runInNewContext(source, { document, window, Event });
    documentListeners.DOMContentLoaded();

    return { controls, maxInput, maxField, window };
}

for (const sharedInitialized of [false, true]) {
    test(`coupon selects do not recursively redispatch change (shared initialized: ${sharedInitialized})`, () => {
        const { controls } = couponForm(sharedInitialized);
        controls.forEach((control) => {
            const selected = control.value;
            control.fire('change');
            assert.equal(control.dispatches, 0, `${control.id} must not redispatch its own change event`);
            assert.equal(control.value, selected);
        });
    });
}

test('switching discount type still toggles the maximum discount field', () => {
    const { controls, maxField, window } = couponForm(true);
    controls[0].value = 'percentage';
    controls[0].fire('change');
    assert.equal(maxField.style.display, 'block');
    controls[0].value = 'fixed';
    window.updateDiscountFields();
    assert.equal(maxField.style.display, 'none');
});
