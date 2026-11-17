import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import test from 'node:test';
import puppeteer from 'puppeteer';

async function render(errors, run, { failRequest = false, realTransitions = false, existingFiles = false } = {}) {
    const html = execFileSync('php', ['tests/Fixtures/AuctionReports/render-closing-edit.php', JSON.stringify(errors)], { encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
    assert.ok(html.startsWith('<!doctype html>'), html.slice(0, 1000));
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const pageErrors = [];
        page.on('pageerror', error => pageErrors.push(error.message));
        await page.setRequestInterception(true);
        page.on('request', request => request.url().startsWith('http') ? request.respond({ status: 200, body: '' }) : request.continue());
        await page.setContent(html);
        if (realTransitions) await page.addStyleTag({ path: 'node_modules/bootstrap/dist/css/bootstrap.min.css' });
        await page.addScriptTag({ path: 'node_modules/jquery/dist/jquery.min.js' });
        await page.addScriptTag({ path: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js' });
        await page.evaluate(({ fail, realTransitions, existingFiles }) => {
            window.toastr = { success() {}, warning() {}, error() {} };
            window.fixtureModalShown = false;
            $(document).on('shown.bs.modal', '#closingPropertyModal', () => { window.fixtureModalShown = true; });
            window.ajaxLoads = 0;
            $.ajax = options => {
                window.ajaxLoads++;
                const id = Number(options.url.split('/').pop());
                const attachments = existingFiles ? [1, 2].map(index => ({ id: id * 100 + index, file_name: existingFiles === 'unsafe' ? 'saved "quoted" <b>name</b>.png' : `saved-${index}.png`, file_url: `/saved-${index}.png`, file_type: 'image/png' })) : [];
                setTimeout(() => fail ? options.error() : options.success({ id, name: `العقار ${id}`, property_number: `PROP-${id}`, result_status: 'sold', buyer_name: 'Server buyer', attachments }), realTransitions ? 250 : (id === 42 ? 120 : 40));
            };
            document.querySelector('form').addEventListener('submit', event => event.preventDefault());
        }, { fail: failRequest, realTransitions, existingFiles });
        await page.addScriptTag({ type: 'module', path: 'resources/assets/js/shared/file-upload.js' });
        await page.addScriptTag({ path: 'resources/assets/js/tenant/auctions/cycle-forms.js' });
        await run(page);
        assert.deepEqual(pageErrors, []);
    } finally {
        await browser.close();
    }
}

async function waitForProperty(page, id) {
    await page.waitForFunction(id => document.querySelector('#modal_property_id').value === String(id) && !document.querySelector('#closing-modal-content').classList.contains('d-none'), { timeout: 2000 }, id);
}

test('opening the property modal never validates empty images during initialization', async () => {
    await render({}, async page => {
        await page.evaluate(() => $('.edit-closing-prop-btn[data-property-id="42"]').trigger('click'));
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), false);
        await waitForProperty(page, 42);
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), false);
        await page.click('#btn-save-closing-modal');
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), true);
        await page.click('#closingPropertyModal [data-bs-dismiss="modal"]');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        await page.evaluate(() => $('.edit-closing-prop-btn[data-property-id="42"]').trigger('click'));
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), false);
        await waitForProperty(page, 42);
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), false);
    });
});

test('closing dates validate live against inclusive auction dates and board removal follows closing', async () => {
    await render({}, async page => {
        await page.evaluate(() => {
            const input = document.querySelector('[name="closing_date"]');
            input.value = '2026-11-09';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        assert.equal(await page.$eval('[name="closing_date"]', node => node.classList.contains('is-invalid')), true);
        for (const value of ['2026-11-10', '2026-11-20']) {
            await page.evaluate(value => {
                const input = document.querySelector('[name="closing_date"]');
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }, value);
            assert.equal(await page.$eval('[name="closing_date"]', node => node.classList.contains('is-invalid')), false);
            assert.equal(await page.$eval('[name="board_removal_date"]', node => node.min), value);
        }
        await page.evaluate(() => {
            const input = document.querySelector('[name="board_removal_date"]');
            input.value = '2026-11-19';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        assert.equal(await page.$eval('[name="board_removal_date"]', node => node.classList.contains('is-invalid')), true);
        await page.evaluate(() => {
            const input = document.querySelector('[name="board_removal_date"]');
            input.value = '2026-11-20';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        assert.equal(await page.$eval('[name="board_removal_date"]', node => node.classList.contains('is-invalid')), false);
        await page.evaluate(() => {
            const input = document.querySelector('[name="closing_date"]');
            input.value = '2026-11-21';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        assert.equal(await page.$eval('[name="closing_date"]', node => node.classList.contains('is-invalid')), true);
    });
});

test('submit blocks date violations even without input events and allows equal dates', async () => {
    await render({}, async page => {
        for (const [closing, removal, invalidField] of [['2026-11-09', '2026-11-21', 'closing_date'], ['2026-11-21', '2026-11-21', 'closing_date'], ['2026-11-20', '2026-11-19', 'board_removal_date']]) {
            await page.evaluate(([closing, removal]) => {
                document.querySelector('[name="closing_date"]').value = closing;
                document.querySelector('[name="board_removal_date"]').value = removal;
                document.querySelector('[name="results_narrative"]').value = 'نتائج المزاد';
                document.querySelector('form').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }, [closing, removal]);
            assert.equal(await page.$eval(`[name="${invalidField}"]`, node => node.classList.contains('is-invalid')), true);
        }
        await page.evaluate(() => {
            document.querySelector('[name="board_removal_date"]').value = '2026-11-20';
            document.querySelector('form').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        });
        assert.equal(await page.$eval('[name="closing_date"]', node => node.classList.contains('is-invalid')), false);
        assert.equal(await page.$eval('[name="board_removal_date"]', node => node.classList.contains('is-invalid')), false);
    });
});

test('a delayed response for another property cannot replace the latest error modal', async () => {
    await render({}, async page => {
        await page.evaluate(() => {
            const form = document.querySelector('form');
            form.dispatchEvent(new CustomEvent('stepValidationFailed', { detail: { errors: { 'results.42.buyer_name': ['خطأ العقار الأول'] } } }));
            form.dispatchEvent(new CustomEvent('stepValidationFailed', { detail: { errors: { 'results.43.sale_price': ['خطأ العقار الثاني'] } } }));
        });
        await waitForProperty(page, 43);
        await page.waitForFunction(() => document.querySelector('#modal_sale_price').classList.contains('is-invalid'));
        await page.evaluate(() => new Promise(resolve => setTimeout(resolve, 150)));
        assert.equal(await page.$eval('#modal_property_id', node => node.value), '43');
        assert.equal(await page.$eval('#modal_buyer_name', node => node.classList.contains('is-invalid')), false);
        assert.equal(await page.$eval('#modal_sale_price', node => node.closest('[data-form-field]').querySelector('[data-form-error]').textContent), 'خطأ العقار الثاني');
    });
});

test('clicking a property edit button loads its modal once', async () => {
    await render({}, async page => {
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.evaluate(() => window.ajaxLoads), 1);
    });
});

test('redirect errors open the matching property with local values and all its field errors after loading', async () => {
    await render({ 'results.43.buyer_name': ['خطأ اسم المشتري'], 'results.43.sale_price': ['خطأ قيمة الترسية'], 'board_images_after.43.0': ['خطأ الصورة'], 'results.42.result_notes': ['خطأ العقار الآخر'] }, async page => {
        await waitForProperty(page, 43);
        assert.equal(await page.$eval('#modal_buyer_name', node => node.value), 'المشتري 43');
        for (const [id, message] of [['modal_buyer_name', 'خطأ اسم المشتري'], ['modal_sale_price', 'خطأ قيمة الترسية'], ['modal_board_images_after', 'خطأ الصورة']]) {
            assert.equal(await page.$eval(`#${id}`, node => node.classList.contains('is-invalid')), true);
            assert.equal(await page.$eval(`#${id}`, node => node.closest('[data-form-field]').querySelector('[data-form-error]').textContent), message);
        }
        assert.equal(await page.$eval('#modal_result_notes', node => node.classList.contains('is-invalid')), false);
    });
});

test('AJAX validation errors open the identified property and show bracket-name errors', async () => {
    await render({}, async page => {
        await page.evaluate(() => document.querySelector('form').dispatchEvent(new CustomEvent('stepValidationFailed', { detail: { errors: { 'results[42][participants_count]': ['عدد المشاركين غير صالح'], 'board_images_after[42][0]': ['الصورة غير صالحة'] } } })));
        await waitForProperty(page, 42);
        assert.equal(await page.$eval('#modal_participants_count', node => node.classList.contains('is-invalid')), true);
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), true);
    });
});

test('date errors do not open a property modal', async () => {
    await render({ closing_date: ['تاريخ غير صالح'] }, async page => {
        assert.equal(await page.$eval('#closingPropertyModal', node => node.classList.contains('show')), false);
        assert.equal(await page.evaluate(() => window.ajaxLoads), 0);
    });
});

test('property error still appears with local data when loading the property fails', async () => {
    await render({ 'results.42.result_notes': ['ملاحظات غير صالحة'] }, async page => {
        await waitForProperty(page, 42);
        assert.equal(await page.$eval('#modal_result_notes', node => node.value), 'ملاحظات 42');
        assert.equal(await page.$eval('#modal_result_notes', node => node.classList.contains('is-invalid')), true);
    }, { failRequest: true });
});

test('server errors survive the modal fade and focus enters the invalid field after shown', async () => {
    await render({ 'results.42.buyer_name': ['اسم المشتري تجاوز الحد المسموح'] }, async page => {
        await waitForProperty(page, 42);
        await page.waitForFunction(() => window.fixtureModalShown, { timeout: 2000 });
        assert.equal(await page.$eval('#modal_buyer_name', node => node.classList.contains('is-invalid')), true);
        assert.equal(await page.$eval('#modal_buyer_name', node => node.closest('[data-form-field]').querySelector('[data-form-error]').textContent), 'اسم المشتري تجاوز الحد المسموح');
        assert.equal(await page.evaluate(() => document.activeElement?.id), 'modal_buyer_name');
    }, { realTransitions: true });
});

test('closing saved images use the upload component and removal is staged only after saving the modal', async () => {
    await render({}, async page => {
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.$$eval('#modal_board_images_after-preview-list [data-existing-file-card] img', nodes => nodes.length), 2);
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), false);
        assert.equal(await page.$('#modal_existing_attachments_container'), null);
        await page.click('#modal_board_images_after-preview-list [data-existing-file-card] .btn-remove-file');
        assert.equal(await page.$$eval('form [name="remove_board_evidence[]"]', nodes => nodes.length), 0);
        await page.click('#btn-save-closing-modal');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        assert.deepEqual(await page.$$eval('form [name="remove_board_evidence[]"]', nodes => nodes.map(node => node.value)), ['4201']);
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.$$eval('#modal_board_images_after-preview-list [data-existing-file-card]', nodes => nodes.length), 1);
        assert.equal(await page.$eval('#modal_board_images_after-preview-list .btn-view-file', node => node.getAttribute('href')), '/saved-2.png');
    }, { existingFiles: true });
});

test('closing saved image removal is cancelled and cannot remove the last required image without replacement', async () => {
    await render({}, async page => {
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        await page.click('#modal_board_images_after-preview-list [data-existing-file-card] .btn-remove-file');
        await page.click('#closingPropertyModal [data-bs-dismiss="modal"]');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.$$eval('#modal_board_images_after-preview-list [data-existing-file-card]', nodes => nodes.length), 2);
        await page.click('#modal_board_images_after-preview-list [data-existing-file-card] .btn-remove-file');
        await page.click('#modal_board_images_after-preview-list [data-existing-file-card] .btn-remove-file');
        await page.click('#btn-save-closing-modal');
        assert.equal(await page.$eval('#closingPropertyModal', node => node.classList.contains('show')), true);
        assert.equal(await page.$eval('#modal_board_images_after', node => node.classList.contains('is-invalid')), true);
        await page.evaluate(() => window.SharedFileUpload.getController(document.querySelector('#modal_board_images_after')).setFiles([new File(['new'], 'new.png', { type: 'image/png' })]));
        await page.click('#btn-save-closing-modal');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        assert.deepEqual(await page.$$eval('form [name="remove_board_evidence[]"]', nodes => nodes.map(node => node.value)), ['4201', '4202']);
    }, { existingFiles: true });
});

test('saved file names are rendered as text in the shared upload cards', async () => {
    await render({}, async page => {
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.$eval('#modal_board_images_after-preview-list .file-item-name', node => node.textContent), 'saved "quoted" <b>name</b>.png');
        assert.equal(await page.$$eval('#modal_board_images_after-preview-list .file-item-name b', nodes => nodes.length), 0);
    }, { existingFiles: 'unsafe' });
});

test('removing a staged new image clears its submitted file while retaining saved images', async () => {
    await render({}, async page => {
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        await page.evaluate(() => window.SharedFileUpload.getController(document.querySelector('#modal_board_images_after')).setFiles([new File(['new'], 'staged.png', { type: 'image/png' })]));
        await page.click('#btn-save-closing-modal');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        await page.click('#modal_board_images_after-preview-list .file-item:not([data-existing-file-card]) .btn-remove-file');
        await page.click('#btn-save-closing-modal');
        await page.waitForSelector('#closingPropertyModal:not(.show)');
        assert.equal(await page.$$eval('form [name="board_images_after[42][]"]', nodes => nodes.reduce((count, node) => count + node.files.length, 0)), 0);
        await page.click('.edit-closing-prop-btn[data-property-id="42"]');
        await waitForProperty(page, 42);
        assert.equal(await page.$$eval('#modal_board_images_after-preview-list .file-item', nodes => nodes.length), 2);
    }, { existingFiles: true });
});
