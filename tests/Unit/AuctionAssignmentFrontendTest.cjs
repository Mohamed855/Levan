const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = readFileSync(path.resolve(__dirname, '../../resources/assets/js/tenant/auctions/create.js'), 'utf8');
const loader = source.slice(source.indexOf('    function syncSelectValue('), source.indexOf('    // Wizard draft restored handler'));

function mount({ edit = false, values = {} } = {}) {
    const nodes = new Map();
    const requests = [];
    let calculations = 0;
    function node(selector) {
        if (!nodes.has(selector)) nodes.set(selector, { value: '', text: '', html: '', options: [], attrs: {}, events: {} });
        return nodes.get(selector);
    }
    node('#city').options = [{ value: 'الجبيل', text: 'الجبيل' }, { value: 'الرياض', text: 'الرياض' }];
    node('#category').options = [{ value: 'lands', text: 'أراضي' }, { value: 'villas', text: 'فلل سكنية' }];
    node('#type').options = [{ value: 'electronic', text: 'إلكتروني' }];
    Object.entries(values).forEach(([key, value]) => { node(`#${key}`).value = value; });

    function wrap(items) {
        return {
            length: items.length,
            val(value) {
                if (value === undefined) return items[0]?.value;
                items.forEach((item) => { item.value = String(value); });
                return this;
            },
            text(value) {
                if (value === undefined) return items[0]?.text || '';
                items.forEach((item) => { item.text = value; });
                return this;
            },
            html(value) { items.forEach((item) => { item.html = value; }); return this; },
            trigger() { return this; },
            on(event, callback) { items.forEach((item) => { item.events[event] = callback; }); return this; },
            addClass() { return this; },
            removeClass() { return this; },
            prop() { return this; },
            attr(key, value) { items.forEach((item) => { item.attrs[key] = value; }); return this; },
            removeAttr(key) { items.forEach((item) => { delete item.attrs[key]; }); return this; },
            append(option) { items.forEach((item) => item.options.push(option)); return this; },
            find(selector) {
                let options = items.flatMap((item) => item.options || []);
                const match = selector.match(/^option\[value="(.*)"\]$/);
                if (match) options = options.filter((option) => option.value === match[1]);
                return wrap(options);
            },
            each(callback) {
                for (const item of items) if (callback.call(item) === false) break;
                return this;
            },
        };
    }
    function $(selector) {
        return wrap(typeof selector === 'string' ? selector.split(',').map((part) => node(part.trim())) : [selector]);
    }
    $.ajax = (request) => { requests.push(request); };
    const form = { id: edit ? 'auction-edit-form' : 'wizard-validation-form', dataset: { assignmentPropertiesUrl: '/admin/assignments/:id/properties' } };
    const context = vm.createContext({
        $, form,
        selectedProperties: new Map(), restoredPropertyIds: [], propertyImageFallback: '/fallback.svg',
        encodeURIComponent, escapeHtml: (value) => String(value ?? '').replaceAll('<', '&lt;').replaceAll('"', '&quot;'),
        calculateSelectedProperties: () => { calculations++; }, updateLivePreview() {}, syncTimeConstraints() {},
        Option: function (text, value) { this.text = text; this.value = value; },
    });
    vm.runInContext(`${loader}\nthis.load = loadAssignmentProperties;`, context);
    return { load: context.load, form, node, requests, calculations: () => calculations };
}

function response(overrides = {}) {
    return {
        success: true,
        assignment: { id: 1, city: 'الجبيل', category: 'villas', issuing_entity: 'Authority', start_date: '2026-11-01', end_date: '2026-11-30', ...overrides },
        properties: [{ id: 12, name: 'Villa', type: 'villa', city: 'الجبيل', is_selectable: true }],
    };
}

test('selecting an assignment fills step one and renders its properties', () => {
    const page = mount();
    page.load('1');
    assert.equal(page.requests[0].url, '/admin/assignments/1/properties');
    page.requests[0].success(response());
    for (const [field, value] of Object.entries({ city: 'الجبيل', category: 'villas', start_time: '00:00', end_time: '23:59', start_date: '2026-11-01', end_date: '2026-11-30', supervising_entity: 'Authority', type: 'electronic', expected_properties_count: '1' })) {
        assert.equal(page.node(`#${field}`).value, value, field);
    }
    assert.match(page.node('#properties-container').html, /Villa/);
    assert.match(page.node('#properties-container').html, /value="12"/);
    assert.equal(page.calculations(), 1);
});

test('initial reload preserves dates and times changed by the user', () => {
    const page = mount({ values: { city: 'الرياض', category: 'villas', start_date: '2026-11-05', end_date: '2026-11-20', start_time: '09:15', end_time: '18:30', supervising_entity: 'Custom authority', expected_properties_count: '4' } });
    page.load('1', [], true);
    page.requests[0].success(response());
    for (const [field, value] of Object.entries({ city: 'الرياض', start_date: '2026-11-05', end_date: '2026-11-20', start_time: '09:15', end_time: '18:30', supervising_entity: 'Custom authority', expected_properties_count: '4' })) {
        assert.equal(page.node(`#${field}`).value, value, field);
    }
    assert.match(page.node('#properties-container').html, /Villa/);
});

test('assignment city is selected exactly even when absent from current city options', () => {
    const page = mount();
    page.load('1');
    page.requests[0].success(response({ city: 'مدينة الرياض الجديدة' }));
    assert.equal(page.node('#city').value, 'مدينة الرياض الجديدة');
    assert.match(page.node('#properties-container').html, /Villa/);
});

test('changing assignment replaces defaults and does not retain obsolete date constraints', () => {
    const page = mount();
    page.load('1');
    page.requests[0].success(response());
    page.load('2');
    page.requests[1].success(response({ city: 'الرياض', category: 'lands', start_date: '2026-12-01', end_date: null }));
    assert.equal(page.node('#city').value, 'الرياض');
    assert.equal(page.node('#category').value, 'lands');
    assert.equal(page.node('#start_date').attrs.max, undefined);
    assert.equal(page.node('#end_date').attrs.max, undefined);
    assert.equal(page.node('#end_date').value, '');
});

test('editing an existing auction keeps the saved custom schedule', () => {
    const page = mount({ edit: true, values: { start_time: '10:20', end_time: '17:45' } });
    page.load('1', [], true);
    page.requests[0].success(response());
    assert.equal(page.node('#start_time').value, '10:20');
    assert.equal(page.node('#end_time').value, '17:45');
});

test('a late response from a previous assignment cannot replace the current selection', () => {
    const page = mount();
    page.load('1');
    page.load('2');
    page.requests[1].success(response({ city: 'الرياض', category: 'lands' }));
    page.requests[0].success(response());
    assert.equal(page.node('#city').value, 'الرياض');
    assert.equal(page.node('#category').value, 'lands');
    assert.equal(page.calculations(), 1);
});

test('clearing the assignment ignores any outstanding response and clears its properties', () => {
    const page = mount();
    page.load('1');
    page.load('');
    const emptyHtml = page.node('#properties-container').html;
    page.requests[0].success(response());
    assert.equal(page.node('#properties-container').html, emptyHtml);
    assert.doesNotMatch(emptyHtml, /value="12"/);
});

test('draft restoration waits until all saved fields are restored before loading the assignment', () => {
    const page = mount();
    page.form.inert = true;
    const assignment = page.node('#assignment_id');
    assignment.value = '1';
    assignment.events.change.call(assignment);
    assert.equal(page.requests.length, 0);
    page.form.inert = false;
    assignment.events.change.call(assignment);
    assert.equal(page.requests.length, 1);
});

test('an assignment without properties displays the empty state', () => {
    const page = mount();
    page.load('1');
    page.requests[0].success({ ...response(), properties: [] });
    assert.match(page.node('#properties-container').html, /لا توجد عقارات/);
});

test('failed assignment requests display an error instead of a silent empty table', () => {
    const page = mount();
    page.load('1');
    page.requests[0].error();
    assert.match(page.node('#properties-container').html, /حدث خطأ/);
});
