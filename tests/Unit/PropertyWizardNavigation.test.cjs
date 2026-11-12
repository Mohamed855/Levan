const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname, '../../resources/assets/js/shared/form-validation.js'), 'utf8');
const initStepper = source.slice(source.indexOf('function initStepper(form)'), source.indexOf('\nfunction initSelect2Controls'));

function wizard(valid) {
    const handlers = new Map();
    const transitions = [];
    const validations = [];
    const ids = ['basic', 'location', 'ownership', 'files', 'review'];
    const steps = ids.map(id => ({ id: `property-${id}-validation`, classList: { contains: () => false } }));
    const element = {
        querySelectorAll: () => steps,
        addEventListener: (type, callback) => handlers.set(type, callback),
    };
    const state = {};
    const form = { id: 'property-create-form', closest: () => element, addEventListener() {}, dispatchEvent: event => transitions.push(event.detail.stepIndex) };
    const button = { dataset: {}, closest: () => steps[3], setAttribute() {}, removeAttribute() {}, querySelector: () => null };
    class Stepper {
        constructor() { this._currentIndex = 3; }
        to(number) {
            const index = number > 0 && number <= steps.length ? number - 1 : 0;
            let cancelled = false;
            handlers.get('show.bs-stepper')({ detail: { from: this._currentIndex, to: index }, preventDefault: () => { cancelled = true; } });
            if (!cancelled) this._currentIndex = index;
        }
    }
    vm.runInNewContext(`${initStepper}\ninitStepper(form)`, {
        form, window: { Stepper }, getFormState: () => state,
        validationFieldsFromStep: step => step === steps[3] ? ['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit'] : [],
        validateFields: async (_form, fields) => { validations.push(Array.from(fields)); return valid; },
        updateStepNextButton() {}, syncAllPropertyStepButtons() {}, initSelect2Controls() {}, scrollToFirstInvalid() {},
        setTimeout() {}, VALIDATION_ERROR_TOAST_MESSAGE: 'Invalid files',
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    });
    return {
        state, transitions, validations,
        next: () => handlers.get('click')({
            target: { closest: selector => selector.includes('data-wizard-next') ? button : null },
            preventDefault() {}, stopImmediatePropagation() {},
        }),
    };
}

test('valid property documents advance from step four to review without returning to step one', async () => {
    const flow = wizard(true);
    await flow.next();
    assert.equal(flow.state.stepper._currentIndex, 4);
    assert.deepEqual(flow.transitions, [4]);
    assert.deepEqual(flow.validations, [['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit']]);
});

test('invalid property documents keep the user on step four', async () => {
    const flow = wizard(false);
    await flow.next();
    assert.equal(flow.state.stepper._currentIndex, 3);
    assert.deepEqual(flow.transitions, []);
});
