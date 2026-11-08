import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const archives = [
    ['admin/archive/admin_users/index.js', '#admin-user-archive-table'],
    ['admin/archive/bills/index.js', '#bill-archive-table'],
    ['admin/archive/coupons/index.js', '#coupon-archive-table'],
    ['admin/archive/plans/index.js', '#paln-archive-table'],
    ['admin/archive/subscriptions/index.js', '#subscriptions-archive-table'],
    ['admin/archive/tenants/index.js', '#tenant-archive-table'],
    ['tenant/archive/users/index.js', '#archived-users-table'],
];

async function archive(relativePath, ids = ['4', '7']) {
    const source = await readFile(new URL(`../../resources/assets/js/${relativePath}`, import.meta.url), 'utf8');
    const calls = [];
    const errors = [];
    const docListeners = [];
    const window = {
        routes: { forceDelete: '/archive/:id/force-delete', massForceDelete: '/archive/mass-force-delete' },
        confirmRecordDelete: (options) => calls.push(options),
    };
    const $ = (selector) => ({
        each(callback) { ids.forEach((id) => callback.call({ id })); },
        val: () => selector.id,
        prop() {},
    });
    const context = {
        window, $, document: { addEventListener(event, callback) { docListeners.push({ event, callback }); } },
        toastr: { error: (message) => errors.push(message), warning: (message) => errors.push(message) },
    };
    vm.runInNewContext(source, context);
    return { window, calls, errors, docListeners };
}

for (const [path, table] of archives) {
    test(`${path}: permanent deletion delegates URL and table to the global modal`, async () => {
        const { window, calls } = await archive(path);
        window.confirmForceDelete(4);
        assert.equal(calls.length, 1);
        assert.equal(calls[0].url, '/archive/4/force-delete');
        assert.equal(calls[0].table, table);
        assert.match(calls[0].text, /نهائ/);
    });

    test(`${path}: bulk permanent deletion preserves DELETE with an array of IDs`, async () => {
        const { window, calls } = await archive(path);
        window.handleMassForceDelete();
        assert.equal(calls.length, 1);
        assert.equal(calls[0].url, '/archive/mass-force-delete');
        assert.equal(calls[0].method, 'DELETE');
        assert.deepEqual(Array.from(calls[0].data.ids), ['4', '7']);
        assert.equal(calls[0].table, table);
    });

    test(`${path}: invalid IDs and an empty selection do not open a modal`, async () => {
        const { window, calls, errors } = await archive(path, []);
        window.confirmForceDelete('');
        window.handleMassForceDelete();
        assert.equal(calls.length, 0);
        assert.equal(errors.length, 2);
    });
}

test('archived tenants preserve string IDs and encode them in the delete URL', async () => {
    const { window, calls } = await archive('admin/archive/tenants/index.js');
    window.confirmForceDelete('tenant/42');
    assert.equal(calls[0].url, '/archive/tenant%2F42/force-delete');
});

test('tenant property, assignment and auction archive buttons delegate their URL and table', async () => {
    const source = await readFile(new URL('../../resources/assets/js/tenant/archive/tenant-archive.js', import.meta.url), 'utf8');
    const listeners = new Map();
    const calls = [];
    const table = {};
    const button = {
        url: '/archive/properties/42/force-delete',
        closest: () => table,
    };
    const $ = (target) => {
        if (typeof target === 'function') return target();
        return {
            on: (event, selector, callback) => listeners.set(selector, callback),
            data: () => target.url,
        };
    };
    vm.runInNewContext(source, {
        $, document: {}, window: { confirmRecordDelete: (options) => calls.push(options) },
    });
    let prevented = false;
    listeners.get('.js-force-delete-btn').call(button, { preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, button.url);
    assert.equal(calls[0].table, table);
    assert.match(calls[0].text, /نهائ/);
});

test('bills and tenant users archives delegate click on .js-force-btn to confirmForceDelete', async () => {
    for (const [path, table] of [
        ['admin/archive/bills/index.js', '#bill-archive-table'],
        ['tenant/archive/users/index.js', '#archived-users-table'],
    ]) {
        const { calls, docListeners } = await archive(path);
        const clickListeners = docListeners.filter((l) => l.event === 'click');
        assert.ok(clickListeners.length >= 2, `Expected at least two click listeners on document for ${path}`);
        const fakeButton = {
            dataset: { id: '42' },
            closest: (sel) => (sel === '.js-force-btn' ? fakeButton : null),
        };
        for (const { callback } of clickListeners) {
            callback({ target: fakeButton });
        }
        assert.equal(calls.length, 1, `Expected 1 call for ${path}`);
        assert.equal(calls[0].url, '/archive/42/force-delete');
        assert.equal(calls[0].table, table);
    }
});

test('tenant notifications all.js delegates click on .js-delete-notification-btn to confirmRecordDelete', async () => {
    const source = await readFile(new URL('../../resources/assets/js/tenant/notifications/all.js', import.meta.url), 'utf8');
    const calls = [];
    const docListeners = [];
    const window = {
        routes: { deleteNotification: '/notifications/__ID__' },
        confirmRecordDelete: (options) => calls.push(options),
    };
    const $ = () => ({
        select2() {},
        on() {},
    });
    const document = {
        addEventListener(event, callback) { docListeners.push({ event, callback }); },
    };
    vm.runInNewContext(source, { window, $, document, console });
    const clickListener = docListeners.find((l) => l.event === 'click');
    assert.ok(clickListener, 'Expected click listener in notifications all.js');
    let prevented = false;
    const fakeButton = {
        dataset: { id: 'notif-77' },
        closest: (sel) => (sel === '.js-delete-notification-btn' ? fakeButton : null),
    };
    clickListener.callback({ target: fakeButton, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/notifications/notif-77');
    assert.equal(calls[0].table, '#notifications-table');
});
