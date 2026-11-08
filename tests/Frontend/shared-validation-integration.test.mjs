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
