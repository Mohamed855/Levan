const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../resources/assets/js/shared/form-validation.js'), 'utf8');
const discard = source.slice(source.indexOf('async function discardDraft(form)'), source.indexOf('\nfunction hasVisibleServerErrors'));

test('discard refuses HTTP failure instead of allowing a new form', async () => {
    const context = {
        form: { dataset: { draftUrl: '/wizard-drafts' } },
        draftResourceKey: () => 'tenant.property-create',
        document: { querySelector: () => ({ content: 'csrf' }) },
        fetch: async () => ({ ok: false, status: 500 }),
    };
    await assert.rejects(vm.runInNewContext(discard + '\ndiscardDraft(form)', context));
});

test('a failed discard leaves the form locked and offers retry', async () => {
    const restore = source.slice(source.indexOf('async function restoreDraft(form)'), source.indexOf('\nfunction initDrafts(form)'));
    const attributes = new Map();
    const form = {
        dataset: { draftUrl: '/wizard-drafts' },
        setAttribute: (key, value) => attributes.set(key, value),
        removeAttribute: (key) => attributes.delete(key),
    };
    const context = {
        form, hasVisibleServerErrors: () => false, draftResourceKey: () => 'fixture',
        fetch: async () => ({ ok: true, json: async () => ({ draft_id: 'saved-draft' }) }),
        window: { confirmActionAsync: async () => false, toastr: { error: () => {} } },
        discardDraft: async () => { throw new Error('storage failure'); },
        document: { createElement: () => ({ dataset: {}, setAttribute() {}, addEventListener() {}, classList: { add() {} } }) },
    };
    // The retry control is outside the inert form so keyboard users can recover.
    form.insertAdjacentElement = (_, control) => { context.retry = control; };
    await vm.runInNewContext(restore + '\nrestoreDraft(form)', context);
    assert.equal(form.inert, true);
    assert.ok(context.retry);
});
