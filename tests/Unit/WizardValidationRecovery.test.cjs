const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname, '../../resources/assets/js/shared/form-validation.js'), 'utf8');

test('empty feedback placeholders do not send validation back to the first step', () => {
    const empty = { textContent: '', hidden: false, style: {} };
    const error = { textContent: 'Owner name is required', hidden: false, style: {} };
    const steps = [empty, empty, error].map(feedback => ({
        querySelector: () => null,
        querySelectorAll: selector => selector === '[name]' ? [] : [feedback],
    }));
    const form = { querySelectorAll: () => steps };
    const fn = source.slice(source.indexOf('function findInvalidStep(form)'), source.indexOf('\nfunction showFirstInvalidStep'));
    const result = vm.runInNewContext(`${fn}\nfindInvalidStep(form)`, { form, getFormState: () => ({}) });
    assert.equal(result.targetStepIndex, 2);
    assert.equal(result.firstInvalidElement, error);
});

test('server error recovery restores files automatically without replacing posted scalar values', async () => {
    const restored = [];
    const moves = [];
    const state = { hasServerErrors: true };
    const form = {
        dataset: { draftUrl: '/wizard-drafts' },
        closest: () => ({ dataset: { failedDraftId: 'failed-id', failedDraftResource: 'tenant.property-create' } }),
        setAttribute() {}, removeAttribute() {}, dispatchEvent() {},
    };
    const restore = source.slice(source.indexOf('async function restoreDraft(form)'), source.indexOf('\nfunction initDrafts(form)'));
    const context = {
        form, hasVisibleServerErrors: () => true, draftResourceKey: () => 'tenant.property-create',
        fetch: async () => ({ ok: true, json: async () => ({ draft_id: 'failed-id', step: 5, data: { name: 'stale' }, files: { main_image: { name: 'photo.png' } } }) }),
        window: { confirmActionAsync: () => assert.fail('Recovery must not ask to restore a draft'), toastr: { success() {}, error() {} } },
        addHiddenField() {}, restoreDraftValues: () => assert.fail('Old input must remain authoritative'),
        restoreDraftFiles: async (_form, files) => restored.push(files.main_image.name),
        resolveDraftNavigation: () => assert.fail('Server errors determine the recovery step'),
        showFirstInvalidStep: () => moves.push(2), getFormState: () => state,
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    };
    await vm.runInNewContext(`${restore}\nrestoreDraft(form)`, context);
    assert.deepEqual(restored, ['photo.png']);
    assert.deepEqual(moves, [2]);
    assert.equal(form.inert, false);
});

test('server recovery waits for dynamic property selections before returning to the error step', async () => {
    let selected = [];
    const moves = [];
    const form = {
        dataset: { draftUrl: '/wizard-drafts' },
        closest: () => ({ dataset: { failedDraftId: 'failed-id', failedDraftResource: 'tenant.auction-create' } }),
        setAttribute() {}, removeAttribute() {},
        dispatchEvent(event) {
            event.detail.waitUntil(new Promise(resolve => setImmediate(() => {
                selected = Array.from(event.detail.data.property_ids);
                resolve();
            })));
        },
    };
    const restore = source.slice(source.indexOf('async function restoreDraft(form)'), source.indexOf('\nfunction initDrafts(form)'));
    await vm.runInNewContext(`${restore}\nrestoreDraft(form)`, {
        form, hasVisibleServerErrors: () => true, draftResourceKey: () => 'tenant.auction-create',
        fetch: async () => ({ ok: true, json: async () => ({ draft_id: 'failed-id', data: { property_ids: ['42'] }, files: {} }) }),
        window: {}, addHiddenField() {}, restoreDraftFiles: async () => {},
        getFormState: () => ({}), showFirstInvalidStep: () => moves.push([...selected]),
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    });
    assert.deepEqual(moves, [['42']]);
});
