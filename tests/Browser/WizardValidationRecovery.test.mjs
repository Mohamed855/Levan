import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

test('a failed wizard restores native file inputs and dynamic selections before opening the error step', async () => {
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = JSON.stringify({ notes: ['Correct the notes'] });
        await page.setContent(`<div class="bs-stepper" data-form-stepper data-validation-errors='${errors}'
            data-failed-draft-id="failed-id" data-failed-draft-resource="tenant.auction-create">
            <div class="bs-stepper-header">${['basic', 'properties', 'files'].map(id =>
                `<div class="step" data-target="#${id}"><button class="step-trigger" type="button"></button></div>`,
            ).join('')}</div>
            <form id="auction-create-form" action="http://example.test/auctions" method="post" data-form-validate
                data-draft-url="/drafts" data-validation-url="/validate" data-draft-resource-key="tenant.auction-create">
                <div class="bs-stepper-content">
                    <div class="content" id="basic" data-form-step data-validation-fields='["name"]'>
                        <input name="name" value="Posted name"><div data-form-error></div>
                    </div>
                    <div class="content" id="properties" data-form-step data-validation-fields='["property_ids"]'>
                        <div data-form-field data-field-name="property_ids"><div data-form-error></div></div>
                    </div>
                    <div class="content" id="files" data-form-step data-validation-fields='["notes","cover_image"]'>
                        <div data-form-field data-field-name="notes"><input name="notes" value="Posted notes"><div data-form-error></div></div>
                        <div data-form-field data-field-name="cover_image"><input type="file" name="cover_image"><div data-form-error></div></div>
                    </div>
                </div>
            </form></div>`);
        await page.addScriptTag({ path: 'node_modules/bs-stepper/dist/js/bs-stepper.min.js' });
        await page.evaluate(() => {
            window.initAllFileUploadZones = () => {};
            window.getFileUploadController = () => null;
            window.fetch = async url => {
                if (url === '/photo') return new Response(new Blob(['image bytes'], { type: 'image/png' }));
                if (url.startsWith('/drafts?')) return new Response(JSON.stringify({
                    draft_id: 'failed-id', data: { name: 'Posted name', property_ids: ['42'] },
                    files: { cover_image: { key: 'cover_image', name: 'photo.png', mime: 'image/png', url: '/photo' } },
                }));
                return new Response(JSON.stringify({ success: true }));
            };
            const form = document.querySelector('form');
            form.addEventListener('wizardDraftRestored', event => {
                event.detail.waitUntil(new Promise(resolve => setTimeout(() => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'property_ids[]';
                    input.value = event.detail.data.property_ids[0];
                    document.getElementById('properties').append(input);
                    resolve();
                }, 10)));
            });
        });
        const script = readFileSync('resources/assets/js/shared/form-validation.js', 'utf8')
            .replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, '')
            .split('if (document.readyState === "loading")')[0];
        await page.addScriptTag({ content: `${script}\ninitForm(document.querySelector('form'));` });
        await page.waitForFunction(() => {
            const form = document.querySelector('form');
            return !form.inert && form.elements.cover_image.files.length === 1;
        });
        const actual = await page.evaluate(() => ({
            step: document.querySelector('[data-form-stepper]').bsStepper._currentIndex,
            file: document.querySelector('form').elements.cover_image.files[0].name,
            property: document.querySelector('[name="property_ids[]"]').value,
            name: document.querySelector('[name="name"]').value,
            error: document.querySelector('[data-field-name="notes"] [data-form-error]').textContent,
        }));
        assert.deepEqual(actual, { step: 2, file: 'photo.png', property: '42', name: 'Posted name', error: 'Correct the notes' });
    } finally {
        await browser.close();
    }
});
