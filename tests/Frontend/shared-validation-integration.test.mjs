import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { after, before, test } from 'node:test';
import puppeteer from 'puppeteer';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const bootstrap = String.raw`require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); view()->share('errors',new Illuminate\Support\ViewErrorBag);`;
let browser;
before(async () => { browser = await puppeteer.launch({ headless: true, pipe: true, args: ['--no-sandbox', '--disable-gpu'] }); });
after(async () => { await browser?.close(); });

async function fixture(html, path = '/edit') {
    const page = await browser.newPage();
    await page.setRequestInterception(true);
    page.on('request', (request) => request.respond({ contentType: 'text/html; charset=utf-8', body: `<meta charset="utf-8"><meta name="csrf-token" content="test-token"><style>.d-none{display:none!important}</style>${html}` }));
    await page.goto(`http://localhost:8000${path}`);
    await page.evaluate(() => {
        window.validationRequests = [];
        window.toastr = { error() {}, success() {} };
        window.fetch = async (url, options = {}) => {
            if (!String(url).includes('validate-step')) return { ok: true, json: async () => ({ draft_id: null }) };
            window.validationRequests.push({ url: String(url), method: options.body.get('target_method'), fields: options.body.getAll('fields[]') });
            return { ok: false, json: async () => ({ success: false, errors: { channels: ['Choose a channel'] } }) };
        };
    });
    await page.addScriptTag({ content: (await read('resources/assets/js/shared/file-upload.js')).replace(/^export /gm, '') });
    await page.addScriptTag({ content: (await read('resources/assets/js/shared/form-validation.js')).replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, '') });
    await page.waitForFunction(() => document.querySelector('form')?.dataset.formValidationInitialized === 'true' && !document.querySelector('form').inert);
    return page;
}

test('shared required controls expose native constraints and immediate errors while optional controls stay valid', async () => {
    const html = execFileSync('php', ['-r', bootstrap + String.raw`
        echo Illuminate\Support\Facades\Blade::render('<form data-form-validate>
            <x-form.controls.input name="title" label="Title" required />
            <x-form.controls.select name="choice" label="Choice" :select2="false" :options="[1=>\'One\']" required />
            <x-form.controls.textarea name="notes" label="Notes" required />
            <x-form.controls.input name="optional" label="Optional" :required="false" />
        </form>');`], { encoding: 'utf8' });
    const page = await fixture(html);
    try {
        for (const name of ['title', 'choice', 'notes']) {
            await page.$eval(`[name="${name}"]`, (control) => { control.focus(); control.blur(); });
            assert.equal(await page.$eval(`[name="${name}"]`, (control) => control.validity.valueMissing), true);
            assert.equal(await page.$eval(`[name="${name}"]`, (control) => control.getAttribute('aria-invalid')), 'true');
        }
        assert.equal(await page.$eval('[name="optional"]', (control) => control.checkValidity()), true);
        await page.$eval('[name="title"]', (control) => { control.value = 'Title'; control.focus(); control.blur(); });
        await page.$eval('[name="choice"]', (control) => { control.value = '1'; control.focus(); control.blur(); });
        await page.$eval('[name="notes"]', (control) => { control.value = 'Notes'; control.focus(); control.blur(); });
        assert.equal(await page.$eval('form', (form) => form.checkValidity()), true);
    } finally { await page.close(); }
});

test('textarea component enforces 1000-char max with left-aligned RTL counter and leaves raw textareas untouched', async () => {
    const html = execFileSync('php', ['-r', bootstrap + String.raw`
        echo Illuminate\Support\Facades\Blade::render('<div dir="rtl"><form>
            <x-form.controls.textarea name="notes" label="Notes" />
            <x-form.controls.textarea name="short_desc" label="Short" maxlength="500" />
            <x-form.controls.textarea name="capped" label="Capped" maxlength="2500" />
            <textarea name="raw_notes"></textarea>
        </form></div>');`], { encoding: 'utf8' });
    const page = await browser.newPage();
    try {
        await page.setContent(`<meta charset="utf-8"><style>.d-flex{display:flex}.justify-content-end{justify-content:flex-end}.form-control{display:block;width:100%;box-sizing:border-box}</style>${html}`);
        await page.addScriptTag({ content: await read('resources/assets/js/shared/input-guards.js') });

        assert.equal(await page.$eval('[name="notes"]', (el) => el.getAttribute('maxlength')), '1000');
        assert.equal(await page.$eval('[name="short_desc"]', (el) => el.getAttribute('maxlength')), '500');
        assert.equal(await page.$eval('[name="capped"]', (el) => el.getAttribute('maxlength')), '1000');
        assert.equal(await page.$eval('[name="raw_notes"]', (el) => el.getAttribute('maxlength')), null);
        assert.equal(await page.$eval('[name="raw_notes"]', (el) => el.hasAttribute('data-textarea-counter')), false);
        assert.equal((await page.$$('[data-textarea-counter-display]')).length, 3);

        const initial = await page.evaluate(() => ({
            notes: document.querySelector('[data-field-name="notes"] [data-textarea-counter-display]').textContent.trim(),
            shortDesc: document.querySelector('[data-field-name="short_desc"] [data-textarea-counter-display]').textContent.trim(),
            capped: document.querySelector('[data-field-name="capped"] [data-textarea-counter-display]').textContent.trim(),
            counterDir: document.querySelector('[data-field-name="notes"] [data-textarea-counter-display]').getAttribute('dir'),
            isLeftAlignedInRtl: (() => {
                const ta = document.querySelector('[name="notes"]').getBoundingClientRect();
                const counter = document.querySelector('[data-field-name="notes"] [data-textarea-counter-display]').getBoundingClientRect();
                return counter.top >= ta.bottom && Math.abs(counter.left - ta.left) < 4;
            })(),
        }));
        assert.deepEqual(initial, {
            notes: '0/1000',
            shortDesc: '0/500',
            capped: '0/1000',
            counterDir: 'ltr',
            isLeftAlignedInRtl: true,
        });

        await page.$eval('[name="notes"]', (el) => {
            el.value = 'مرحبا';
        });
        assert.equal(
            await page.$eval('[data-field-name="notes"] [data-textarea-counter-display]', (el) => el.textContent.trim()),
            '5/1000',
        );

        await page.$eval('[name="notes"]', (el) => {
            el.value = 'أ'.repeat(1200);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
        const atLimit = await page.evaluate(() => ({
            length: document.querySelector('[name="notes"]').value.length,
            text: document.querySelector('[data-field-name="notes"] [data-textarea-counter-display]').textContent.trim(),
            hasDanger: document.querySelector('[data-field-name="notes"] [data-textarea-counter-display]').classList.contains('text-danger'),
        }));
        assert.deepEqual(atLimit, { length: 1000, text: '1000/1000', hasDanger: true });
    } finally { await page.close(); }
});

function renderMarketing(editing) {
    const php = bootstrap + String.raw`
        Illuminate\Support\Facades\Route::post('/wizard-drafts/validate-step',fn()=>null)->name('wizard-drafts.validate-step');
        Illuminate\Support\Facades\Route::post('/wizard-drafts',fn()=>null)->name('wizard-drafts.store');
        Illuminate\Support\Facades\Route::delete('/wizard-drafts/file',fn()=>null)->name('wizard-drafts.file.destroy');
        Illuminate\Support\Facades\Route::post('/marketing-campaigns',fn()=>null)->name('marketing-campaigns.store');
        Illuminate\Support\Facades\Route::put('/marketing-campaigns/{auction}',fn()=>null)->name('marketing-campaigns.update');
        Illuminate\Support\Facades\Route::get('/marketing-campaigns',fn()=>null)->name('marketing-campaigns.index');
        app('router')->getRoutes()->refreshNameLookups();
        $template=file_get_contents('resources/views/tenant/marketing-campaigns/form.blade.php');
        preg_match('/@php(.*?)@endphp/s',$template,$head);
        preg_match('/<form\b[\s\S]*?<\/form>/',$template,$form);
        $auction=(new App\Models\Tenants\Auctions\Auction)->forceFill(['id'=>7,'name'=>'Auction']);
        echo Illuminate\Support\Facades\Blade::render('@php'.$head[1].'@endphp'.$form[0],[
            'auction'=>$auction,'is_edit'=>__EDIT__,'auctionOptions'=>[7=>'Auction'],
            'propertyOptions'=>[],'existingCampaigns'=>collect(),'selectedChannels'=>[],
        ]);`;
    return execFileSync('php', ['-r', php.replace('__EDIT__', editing ? 'true' : 'false')], { encoding: 'utf8' });
}

for (const editing of [false, true]) {
    test(`marketing ${editing ? 'edit' : 'create'} sends live validation and displays server field errors`, async () => {
        const page = await fixture(renderMarketing(editing), `/marketing-campaigns/${editing ? '7/edit' : 'create'}`);
        try {
            const valid = await page.evaluate(() => window.SharedFormValidation.validateFields(document.querySelector('form'), ['channels']));
            assert.equal(valid, false);
            const requests = await page.evaluate(() => window.validationRequests);
            assert.equal(requests.length, 1);
            assert.equal(new URL(requests[0].url).pathname, '/wizard-drafts/validate-step');
            assert.equal(requests[0].method, editing ? 'PUT' : 'POST');
            assert.equal(await page.$eval('[name="channels[]"]', (control) => control.getAttribute('aria-invalid')), 'true');
            assert.equal(await page.$eval('[name="channels[]"]', (control) => control.closest('[data-form-field]').querySelector('[data-form-error]').textContent), 'Choose a channel');
        } finally { await page.close(); }
    });
}

test('price inputs format default, programmatic, draft-restored, dynamic, modal, and reset values while stripping commas on submit', async () => {
    const html = execFileSync('php', ['-r', bootstrap + String.raw`
        echo Illuminate\Support\Facades\Blade::render('<form id="price-form">
            <x-form.controls.input name="initial_price" label="Initial" :price="true" :value="1500000" />
            <x-form.controls.input name="decimal_price" label="Decimal" :price="true" value="25000.50" />
            <x-form.controls.input name="modal_price" label="Modal" :price="true" />
            <input name="raw_default_price" type="text" data-price-input="true" value="750000" />
        </form><div id="dynamic-host"></div>');`], { encoding: 'utf8' });
    const page = await browser.newPage();
    try {
        await page.setContent(`<meta charset="utf-8">${html}`);
        await page.addScriptTag({ content: await read('resources/assets/js/shared/input-guards.js') });

        // 1. Initial rendered values & raw default attribute values are formatted on load
        assert.deepEqual(
            await page.evaluate(() => ({
                initial: document.querySelector('[name="initial_price"]').value,
                decimal: document.querySelector('[name="decimal_price"]').value,
                rawDefault: document.querySelector('[name="raw_default_price"]').value,
            })),
            {
                initial: '1,500,000',
                decimal: '25,000.50',
                rawDefault: '750,000',
            },
        );

        // 2. Programmatic assignment (modal population / draft restore / clearing / zero / Arabic digits)
        const programmatic = await page.evaluate(() => {
            const modalInput = document.querySelector('[name="modal_price"]');
            modalInput.value = '1500000';
            const afterString = modalInput.value;

            modalInput.value = 2500000.75;
            const afterNumber = modalInput.value;

            modalInput.value = '١٢٥٠٠٠';
            const afterArabic = modalInput.value;

            modalInput.value = '0';
            const afterZero = modalInput.value;

            modalInput.value = '';
            const afterEmpty = modalInput.value;

            modalInput.value = '9876543.21';
            modalInput.dispatchEvent(new Event('change', { bubbles: true }));
            const afterDraftChange = modalInput.value;

            return { afterString, afterNumber, afterArabic, afterZero, afterEmpty, afterDraftChange };
        });
        assert.deepEqual(programmatic, {
            afterString: '1,500,000',
            afterNumber: '2,500,000.75',
            afterArabic: '125,000',
            afterZero: '0',
            afterEmpty: '',
            afterDraftChange: '9,876,543.21',
        });

        // 3. Dynamically inserted price inputs format default value and bind setter
        const dynamicResult = await page.evaluate(async () => {
            const host = document.getElementById('dynamic-host');
            host.innerHTML = '<input name="dynamic_price" type="text" data-price-input="true" value="450000" />';
            await new Promise((resolve) => setTimeout(resolve, 20));
            const dynamicInput = host.querySelector('[name="dynamic_price"]');
            const initialDynamic = dynamicInput.value;
            dynamicInput.value = '825000.5';
            return { initialDynamic, updatedDynamic: dynamicInput.value };
        });
        assert.deepEqual(dynamicResult, {
            initialDynamic: '450,000',
            updatedDynamic: '825,000.5',
        });

        // 4. Form reset keeps formatted default value even for raw HTML value="750000"
        const afterReset = await page.evaluate(async () => {
            const form = document.getElementById('price-form');
            const rawDefault = form.querySelector('[name="raw_default_price"]');
            rawDefault.value = '999999';
            form.reset();
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            return rawDefault.value;
        });
        assert.equal(afterReset, '750,000');

        // 5. Submit strips commas for FormData payload, then restores formatted display
        const submitResult = await page.evaluate(async () => {
            const form = document.getElementById('price-form');
            const modalInput = form.querySelector('[name="modal_price"]');
            modalInput.value = '1500000.50';

            let submittedValue = null;
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                // Wait a microtask? No: document listener bubbles after form listener, so inspect in capture/bubble on document
            });
            document.addEventListener('submit', () => {
                submittedValue = new FormData(form).get('modal_price');
            }, { once: true });

            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            await new Promise((resolve) => setTimeout(resolve, 80));
            return {
                submittedValue,
                restoredValue: modalInput.value,
            };
        });
        assert.deepEqual(submitResult, {
            submittedValue: '1500000.50',
            restoredValue: '1,500,000.50',
        });

        // 6. Manual typing and Backspace across comma preserve cursor and formatting
        await page.$eval('[name="modal_price"]', (el) => {
            el.value = '';
            el.focus();
        });
        await page.keyboard.type('12345.67');
        assert.equal(await page.$eval('[name="modal_price"]', (el) => el.value), '12,345.67');
        await page.$eval('[name="modal_price"]', (el) => el.setSelectionRange(3, 3));
        await page.keyboard.press('Backspace');
        assert.equal(await page.$eval('[name="modal_price"]', (el) => el.value), '1,345.67');
    } finally { await page.close(); }
});
