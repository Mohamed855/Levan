import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer';

const browser = await puppeteer.launch({executablePath: process.env.BROWSERSHOT_CHROME_PATH || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true});
try {
    const page = await browser.newPage();
    await page.setContent(await readFile('tmp/pdfs/remediation/report-input-proof.html', 'utf8'));
    await page.addScriptTag({type: 'module', content: (await readFile('resources/assets/js/tenant/auctions/report-inputs.js', 'utf8')) + '\nwindow.mountReportInputs = mountReportInputs;'});
    await page.waitForFunction('typeof window.mountReportInputs === "function"');
    const result = await page.evaluate(() => {
        const form = document.getElementById('report-proof');
        const controller = window.mountReportInputs(form);
        const control = (name) => form.elements.namedItem(name);
        form.querySelector('#add-campaign-btn').click();
        control('marketing[campaigns][0][views]').value = '0';
        const addedZero = new FormData(form).get('marketing[campaigns][0][views]');
        controller.restore({marketing: {campaigns: {4: {platform: 'x', views: 0}}, links: [{role: 'video', label: 'فيديو', url: 'https://example.com/video'}]}, property_results: {12: {board_count: 0, board_dimensions: '3×6'}}, marketing_evidence_meta: [{section: 'video', channel: 'youtube'}]}, {marketing_evidence: [{name: 'video.png'}]});
        form.querySelector('#property-results-container').append(controller.propertyCard({id: 12, name: '<script>عقار</script>'}));
        controller.restorePropertyResults();
        const restoredCount = control('property_results[12][board_count]').value;
        control('property_results[12][board_count]').value = '3';
        controller.restorePropertyResults();
        form.querySelector('#add-campaign-btn').click();
        const input = control('marketing_evidence[]');
        const first = new File(['a'], 'a.png', {type:'image/png', lastModified:1});
        const second = new File(['b'], 'b.png', {type:'image/png', lastModified:2});
        const select = (files) => { const list = new DataTransfer(); files.forEach(file => list.items.add(file)); input.files = list.files; input.dispatchEvent(new Event('change', {bubbles:true})); };
        select([first, second]);
        control('marketing_evidence_meta[0][channel]').value = 'x';
        control('marketing_evidence_meta[1][channel]').value = 'youtube';
        select([second, first]);
        return {addedZero, restoredCount, retainedEdit: control('property_results[12][board_count]').value, dimensions: control('property_results[12][board_dimensions]').value, views: control('marketing[campaigns][4][views]').value, nextRow: Boolean(control('marketing[campaigns][5][views]')), role: control('marketing[links][0][role]').value, reorderedChannels: [control('marketing_evidence_meta[0][channel]').value, control('marketing_evidence_meta[1][channel]').value], injectedScript: Boolean(form.querySelector('script'))};
    });
    assert.deepEqual(result, {addedZero:'0', restoredCount:'0', retainedEdit:'3', dimensions:'3×6', views:'0', nextRow:true, role:'video', reorderedChannels:['youtube','x'], injectedScript:false});
    console.log('Browser report-input proof passed: add/edit/draft/zero/sparse rows/reordered files/escaped names.');
} finally {
    await browser.close();
}
