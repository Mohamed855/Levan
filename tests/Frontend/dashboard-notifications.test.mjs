import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = (await readFile(new URL('../../resources/assets/js/shared/dashboard-notifications.js', import.meta.url), 'utf8'))
    .replace(/^import .+ from .+;\r?$/gm, '');

async function dashboard() {
    const listeners = {};
    const element = () => {
        const classes = new Set();
        return {
        textContent: '99', dataset: {}, disabled: false, attributes: {},
        classList: {
            toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); },
            add(...names) { names.forEach((name) => classes.add(name)); },
            remove(...names) { names.forEach((name) => classes.delete(name)); },
            contains: (name) => classes.has(name),
        },
        setAttribute(name, value) { this.attributes[name] = value; },
        getAttribute(name) { return this.attributes[name]; },
        removeAttribute(name) { delete this.attributes[name]; },
        };
    };
    const counters = { all: element(), unread: element(), read: element() };
    const button = element();
    const root = {
        dataset: { feedUrl: '/feed', readAllUrl: '/read-all', readUrl: '/read/__ID__' },
        querySelector: () => null, addEventListener() {},
    };
    const page = { dataset: { notificationFilter: 'all' } };
    const state = {
        feed: { items: [], count: 2, counts: { all: 3, unread: 2, read: 1 } },
        fail: false, pending: null, successes: [], errors: [], reloads: 0,
    };
    const document = {
        hidden: false,
        addEventListener(name, callback) { listeners[name] = callback; },
        querySelector(selector) {
            if (selector === '.js-dashboard-notifications') return root;
            if (selector === '[data-notification-page]') return page;
            if (selector === 'meta[name="csrf-token"]') return { content: 'test-token' };
            return null;
        },
        querySelectorAll(selector) {
            if (selector === '[data-notification-read-all]') return [button];
            const match = selector.match(/^\[data-filter-(all|unread|read)-count\]$/);
            return match ? [counters[match[1]]] : [];
        },
    };
    const window = {
        addEventListener() {}, location: { origin: 'http://localhost', reload() { state.reloads++; } },
        toastr: { success: (message) => state.successes.push(message), error: (message) => state.errors.push(message) },
    };
    const fetch = async (url) => {
        if (url === '/feed') return { ok: true, status: 200, json: async () => state.feed };
        if (state.pending) await state.pending;
        if (state.fail) return { ok: false, status: 500 };
        state.feed = state.result || { items: [], count: 0, counts: { all: 3, unread: 0, read: 3 } };
        return { ok: true, status: 200 };
    };
    vm.runInNewContext(source, { document, window, fetch, URL, setInterval: () => 0, setTimeout, queueMicrotask });
    listeners.DOMContentLoaded();
    await setImmediate();

    const clickReadAll = () => listeners.click({
        target: { closest: (selector) => selector === '[data-notification-read-all]' ? button : null },
        preventDefault() {},
    });

    return { state, counters, button, clickReadAll, listeners, element, page };
}

test('the feed updates all three page counters', async () => {
    const { counters } = await dashboard();
    assert.equal(counters.all.textContent, '3');
    assert.equal(counters.unread.textContent, '2');
    assert.equal(counters.read.textContent, '1');
});

test('mark all waits for success and refreshes the counters', async () => {
    const { state, counters, button, clickReadAll } = await dashboard();
    let resolve;
    state.pending = new Promise((done) => { resolve = done; });
    const mutation = clickReadAll();
    assert.equal(button.disabled, true);
    assert.equal(state.successes.length, 0);
    resolve();
    await mutation;
    assert.equal(counters.all.textContent, '3');
    assert.equal(counters.unread.textContent, '0');
    assert.equal(counters.read.textContent, '3');
    assert.equal(button.disabled, true);
    assert.equal(state.successes.length, 1);
});

test('a failed mark all keeps counts and lets the user retry', async () => {
    const { state, counters, button, clickReadAll } = await dashboard();
    state.fail = true;
    await clickReadAll();
    assert.equal(counters.unread.textContent, '2');
    assert.equal(counters.read.textContent, '1');
    assert.equal(button.disabled, false);
    assert.equal(state.successes.length, 0);
    assert.equal(state.errors.length, 1);
});

test('opening a notification in a new tab updates its row and the unread filter', async () => {
    const { state, counters, listeners, element, page } = await dashboard();
    page.dataset.notificationFilter = 'unread';
    state.result = { items: [], count: 1, counts: { all: 3, unread: 1, read: 2 } };
    const row = element();
    row.dataset = { notificationId: 'notice-1', targetUrl: '/target' };
    row.classList.add('bg-label-primary');
    const link = {
        tagName: 'A', dataset: { dashboardNotificationRead: 'notice-1' },
        closest: (selector) => selector === '[data-notification-id]' ? row
            : selector === '[data-dashboard-notification-read]' ? link : null,
    };

    await listeners.click({ target: link, ctrlKey: true, preventDefault() {} });
    await setImmediate();
    assert.equal(row.classList.contains('marked-as-read'), true);
    assert.equal(row.classList.contains('bg-label-primary'), false);
    assert.equal(counters.unread.textContent, '1');
    assert.equal(state.reloads, 1);
});

test('reading a notification without a target waits before refreshing its counter', async () => {
    const { state, counters, listeners, element } = await dashboard();
    state.result = { items: [], count: 1, counts: { all: 3, unread: 1, read: 2 } };
    let resolve;
    state.pending = new Promise((done) => { resolve = done; });
    const row = element();
    row.dataset.notificationId = 'notice-1';
    row.classList.add('bg-label-primary');
    const link = {
        dataset: { dashboardNotificationRead: 'notice-1' }, getAttribute: () => '#',
        closest: (selector) => selector === '[data-notification-id]' ? row
            : selector === '[data-dashboard-notification-read]' ? link : null,
    };

    const mutation = listeners.click({ target: link, preventDefault() {} });
    assert.equal(counters.unread.textContent, '2');
    assert.equal(row.classList.contains('marked-as-read'), false);
    resolve();
    await mutation;
    assert.equal(counters.unread.textContent, '1');
    assert.equal(row.classList.contains('marked-as-read'), true);
});
