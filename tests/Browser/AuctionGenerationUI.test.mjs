import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

const views = 'resources/views/tenant/auctions/';
const script = filename => [...readFileSync(views + filename, 'utf8').matchAll(/<script>([\s\S]*?)<\/script>/g)].at(-1)[1];
const helpers = script('partials/_generation-helpers.blade.php').replace(/@json\([^\n]+\)/, JSON.stringify({ ready: { class: 'ready', label: 'Ready' }, not_prepared: { class: 'pending', label: 'Pending' }, failed: { class: 'failed', label: 'Failed' } }));

function reports(automatic) {
    let code = script('show.blade.php').split('            // متابعة حالة الإعلانات الترويجية للمزاد')[0] + '\n});';
    code = code.replace(/@if \(\$isProcessing\)([\s\S]*?)@endif/, automatic ? '$1' : '');
    return code.replace(/@json\(tenant_route_name\('auctions\.reports\.(status|retry)', \$auction->id\)\)/g, (_, action) => JSON.stringify(`https://fixture.test/reports/${action}`))
        .replace(/@json\(\$(gen\?->id|initialProgress|hasPublishedOutputs|hasPublishedReport)\)/g, (_, expression) => expression.startsWith('hasPublished') ? 'false' : (expression === 'initialProgress' ? '0' : '1'))
        .replace('{{ csrf_token() }}', 'test-token');
}

const reportMarkup = `<div id="auction-reports-card"><div id="reports-loading-box"></div><div id="reports-progress-bar"></div><span id="reports-progress-percent"></span><span id="reports-stage-label"></span><div id="reports-timeout-alert" class="d-none"></div><div id="reports-error-alert" class="d-none"><span id="reports-error-text"></span></div><div id="reports-header-badge"></div><button id="btn-retry-reports"><i id="btn-retry-icon"></i><span id="btn-retry-text"></span></button>${['outputs', 'report'].map(type => `<a id="btn-download-${type}" class="disabled" disabled aria-disabled="true" data-download-url="https://fixture.test/download/${type}">Download</a>`).join('')}</div>`;

function formMarkup(automatic) {
    return ['establishment', 'closing'].map(type => `<div data-regulatory-form="${type}" data-actionable="1" data-status="${automatic ? 'queued' : 'ready'}" data-generation-id="1" data-progress="0" data-has-file="0" data-status-url="https://fixture.test/${type}/status" data-retry-url="https://fixture.test/${type}/retry" data-download-url="https://fixture.test/download/${type}"><span data-form-badge></span><div data-form-error><span data-form-error-text></span></div><div data-form-timeout class="d-none"></div><div data-form-loading></div><div data-form-progress></div><span data-form-percent></span><span data-form-stage></span><button data-form-retry><i data-form-retry-icon></i><span data-form-retry-text></span></button><a data-form-download class="disabled" disabled aria-disabled="true">Download</a></div>`).join('');
}

async function check(kind, mode, late) {
    const automatic = mode === 'automatic';
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<style>.d-none{display:none}</style>' + (kind === 'reports' ? reportMarkup : formMarkup(automatic)));
        await page.evaluate(() => {
            let clock = 0;
            Date.now = () => clock;
            let nextTimer = 1;
            const timers = new Map();
            window.setInterval = callback => { const id = nextTimer++; timers.set(id, callback); return id; };
            window.clearInterval = id => timers.delete(id);
            window.advanceGenerationClock = async value => {
                clock = value;
                await Promise.all([...timers.values()].map(callback => callback()));
            };
            window.generationRequests = [];
            window.filesReady = false;
            window.generationResponseId = 2;
            window.fetch = async (url, options = {}) => {
                window.generationRequests.push({ url, method: options.method || 'GET' });
                const ready = window.filesReady;
                const state = {
                    success: true, status: ready ? 'ready' : 'processing', progress: ready ? 100 : 30,
                    generation_id: options.method === 'POST' ? 2 : window.generationResponseId,
                    has_outputs: ready, has_report: ready,
                    outputs_download_url: 'https://fixture.test/download/outputs', report_download_url: 'https://fixture.test/download/report',
                    has_file: ready, download_url: url.replace('/status', '/download'),
                };
                return { ok: true, json: async () => state };
            };
        });
        await page.addScriptTag({ content: helpers });
        const code = kind === 'reports' ? reports(automatic) : script('partials/regulatory-forms.blade.php');
        await page.addScriptTag({ content: code });
        await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
        if (!automatic) {
            if (kind === 'reports') await page.click('#btn-retry-reports');
            else for (const button of await page.$$('[data-form-retry]')) await button.click();
        }
        assert.deepEqual(errors, []);
        assert.ok(await page.evaluate(() => generationRequests.length > 0));
        if (late) await page.evaluate(() => window.advanceGenerationClock(70000));
        if (!automatic) {
            await page.evaluate(async late => {
                window.filesReady = true;
                window.generationResponseId = 1;
                await window.advanceGenerationClock(late ? 71000 : 1000);
            }, late);
            assert.ok(await page.evaluate(kind => [...document.querySelectorAll(kind === 'reports' ? '#btn-download-outputs,#btn-download-report' : '[data-form-download]')].every(button => button.hasAttribute('disabled')), kind), 'A previous generation must not activate the new generation downloads');
        }
        await page.evaluate(() => { window.generationResponseId = 2; });
        await page.evaluate(async late => { window.filesReady = true; await window.advanceGenerationClock(late ? 72000 : 2000); }, late);
        const state = await page.evaluate(kind => ({
            buttons: [...document.querySelectorAll(kind === 'reports' ? '#btn-download-outputs,#btn-download-report' : '[data-form-download]')].map(button => ({ disabled: button.hasAttribute('disabled'), classDisabled: button.classList.contains('disabled'), aria: button.getAttribute('aria-disabled'), url: button.href })),
            retries: [...document.querySelectorAll(kind === 'reports' ? '#btn-retry-reports' : '[data-form-retry]')].map(button => button.disabled),
            timeoutsHidden: [...document.querySelectorAll(kind === 'reports' ? '#reports-timeout-alert' : '[data-form-timeout]')].every(box => box.classList.contains('d-none')),
            posts: window.generationRequests.filter(request => request.method === 'POST').length,
        }), kind);
        assert.deepEqual(errors, []);
        for (const button of state.buttons) {
            assert.equal(button.disabled, false, `${kind} ${mode} late=${late}: download remained disabled`);
            assert.equal(button.classDisabled, false);
            assert.equal(button.aria, 'false');
            assert.ok(button.url.startsWith('https://fixture.test/'));
        }
        assert.ok(state.retries.every(disabled => !disabled));
        assert.equal(state.timeoutsHidden, true);
        assert.equal(state.posts, automatic ? 0 : (kind === 'reports' ? 1 : 2));
    } finally { await browser.close(); }
}

for (const kind of ['reports', 'forms']) for (const mode of ['automatic', 'manual']) for (const late of [false, true]) {
    test(`${kind} ${mode}: downloads activate when files finish${late ? ' after the timeout' : ''}`, async () => check(kind, mode, late));
}
