import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = (await readFile(new URL('../../resources/assets/js/shared/confirm-delete.js', import.meta.url), 'utf8'))
    .replace(/^import .*;\r?$/gm, '');

function setup() {
    const requests = [];
    const notices = [];
    const listeners = {};
    const forms = new Map();
    const tables = new Map();
    let options;
    const document = {
        addEventListener: (event, callback) => { listeners[event] = callback; },
        getElementById: (id) => forms.get(id) || null,
        querySelector: (selector) => selector.startsWith('meta') ? { content: 'csrf-test' } : null,
        querySelectorAll: () => [],
    };
    const $ = (selector) => ({
        attr: () => 'csrf-test',
        DataTable: () => tables.get(selector),
    });
    $.ajax = (request) => { requests.push(request); };
    $.fn = { dataTable: { isDataTable: (selector) => tables.has(selector), tables: () => [] } };
    const window = {
        location: { origin: 'https://tenant.test', href: 'https://tenant.test/records', reload() { throw new Error('Unexpected page reload'); } },
        toastr: Object.fromEntries(['success', 'error', 'warning', 'info'].map((type) => [type, (message) => notices.push({ type, message })])),
        sessionStorage: { setItem() {} },
        jQuery: $, $,
    };
    vm.runInNewContext(source, { window, document, $, toastr: window.toastr, URL, Promise, FormData, setTimeout });
    window.confirmAction = (next) => { options = next; };
    return { window, requests, notices, forms, tables, listeners, options: () => options };
}

test('single record uses the shared confirmation and AJAX without submitting the form', async () => {
    const app = setup();
    let refreshed = 0;
    app.tables.set('#records', { ajax: { reload: () => refreshed++ } });
    app.forms.set('delete-form-7', { action: 'https://tenant.test/records/7', querySelector: () => ({ value: 'DELETE' }), submit() { assert.fail('Native form submission'); }, closest: () => null });
    app.window.confirmDelete(7, { table: '#records' });
    assert.equal(app.requests.length, 0);
    const pending = app.options().onConfirm();
    assert.equal(app.requests.length, 1);
    assert.equal(app.requests[0].method, 'DELETE');
    assert.equal(app.requests[0].headers.Accept, 'application/json');
    assert.equal(app.requests[0].headers['X-CSRF-TOKEN'], 'csrf-test');
    app.requests[0].success({ success: true, message: 'Deleted' });
    await pending;
    assert.equal(refreshed, 1);
    assert.deepEqual(app.notices, [{ type: 'success', message: 'Deleted' }]);
});

test('bulk deletion preserves both array and model endpoint payloads', async () => {
    const app = setup();
    app.window.confirmDeleteSelected([3, 4], '/bulk');
    const first = app.options().onConfirm();
    assert.equal(app.requests[0].method, 'POST');
    assert.deepEqual(Array.from(app.requests[0].data.ids), [3, 4]);
    app.requests[0].error({ status: 403, responseJSON: { message: 'Forbidden' } });
    assert.equal(await first, false);
    app.window.confirmDeleteSelectedmss([8, 9], '/mass-delete', 'Property');
    const second = app.options().onConfirm();
    assert.equal(app.requests[1].data.ids, '8,9');
    assert.equal(app.requests[1].data.model, 'Property');
    app.requests[1].error({ status: 422, responseJSON: { message: 'Not allowed' } });
    assert.equal(await second, false);
    assert.equal(app.notices.length, 2);
});

test('repeated confirmation cannot send a second request while deletion is pending', async () => {
    const app = setup();
    app.window.confirmRecordDelete({ url: '/records/2' });
    const first = app.options().onConfirm();
    const second = app.options().onConfirm();
    assert.equal(app.requests.length, 1);
    app.requests[0].error({ status: 500 });
    assert.equal(await first, false);
    await second;
    assert.equal(app.notices.length, 1);
});

test('business errors keep confirmation available and never refresh or report success', async () => {
    const app = setup();
    app.window.confirmRecordDelete({ url: '/records/2' });
    const pending = app.options().onConfirm();
    app.requests[0].success({ success: false, message: 'Record has dependencies' });
    assert.equal(await pending, false);
    assert.deepEqual(app.notices, [{ type: 'error', message: 'Record has dependencies' }]);
});

test('empty selections and cross-origin URLs cannot send destructive requests', async () => {
    const app = setup();
    app.window.confirmDeleteSelected([], '/bulk');
    assert.equal(app.requests.length, 0);
    assert.equal(app.notices[0].type, 'warning');
    app.window.confirmRecordDelete({ url: 'https://other-tenant.test/records/2' });
    if (app.options()) await app.options().onConfirm();
    assert.equal(app.requests.length, 0);
});

test('delegated buttons provide current URL and confirmation data on every click', () => {
    const app = setup();
    const configurations = [];
    app.window.confirmRecordDelete = (config) => configurations.push(config);
    for (const id of ['1', '2']) {
        const button = { dataset: { id, deleteUrl: `/records/${id}`, deleteTitle: `Record ${id}` }, closest: (selector) => selector === 'table' ? null : button };
        app.listeners.click({ target: { closest: () => button }, preventDefault() {} });
    }
    assert.deepEqual(configurations.map((config) => config.url), ['/records/1', '/records/2']);
    assert.deepEqual(configurations.map((config) => config.title), ['Record 1', 'Record 2']);
});

test('a failed refresh callback releases the lock so another deletion can be confirmed', async () => {
    const app = setup();
    app.window.confirmRecordDelete({ url: '/records/1', onSuccess: async () => { throw new Error('Refresh failed'); } });
    const pending = app.options().onConfirm();
    app.requests[0].success({ success: true });
    assert.equal(await pending, true);
    assert.equal(app.notices[0].message, 'تم الحذف، لكن تعذر تحديث الصفحة. يرجى تحديثها.');
    app.window.confirmRecordDelete({ url: '/records/2', refresh: false });
    const retry = app.options().onConfirm();
    assert.equal(app.requests.length, 2);
    app.requests[1].success({ success: true });
    assert.equal(await retry, true);
});

test('navigation carries the deletion toast without displaying it twice', async () => {
    const app = setup();
    const stored = [];
    let reloads = 0;
    app.window.sessionStorage.setItem = (...args) => stored.push(args);
    app.window.location.reload = () => reloads++;
    app.window.confirmRecordDelete({ url: '/records/1' });
    const pending = app.options().onConfirm();
    app.requests[0].success({ success: true, message: 'Deleted' });
    assert.equal(await pending, true);
    assert.equal(reloads, 1);
    assert.deepEqual(stored, [['delete-feedback:/records', 'Deleted']]);
    assert.deepEqual(app.notices, []);
});
