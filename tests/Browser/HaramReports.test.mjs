import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

function html(scenario) {
    return execFileSync(process.env.PHP_BINARY || 'php', ['-r', `
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        Illuminate\\Support\\Facades\\DB::listen(static function (): void { throw new RuntimeException('No database access allowed.'); });
        $data = require 'tests/Fixtures/AuctionReports/haram.php';
        $data['cycle_version'] = 1;
        $data['closing_saved'] = true;
        $scenario = json_decode(stream_get_contents(STDIN), true);
        $data = array_replace_recursive($data, $scenario);
        echo app(App\\Services\\Tenants\\Auctions\\Reports\\AuctionReportRenderer::class)->html($data, 'outputs');
    `], { input: JSON.stringify(scenario), encoding: 'utf8', maxBuffer: 60 * 1024 * 1024 });
}

async function render(scenario, callback) {
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(html(scenario), { waitUntil: 'load' });
        await page.waitForFunction('window.haramReportReady === true');
        assert.equal(await page.evaluate(() => window.haramReportError), null);
        await callback(page);
    } finally {
        await browser.close();
    }
}

test('long RTL cover cards fit, lead with their labels, and keep phone icons separate', async () => {
    await render({
        assignment: { name: 'مشروع عقارات الرياض '.repeat(10) },
        auction: { project_manager_name: 'مدير مشروع عقاري '.repeat(6) },
        marketing: { contacts: { phone: '+966112345678', unified_phone: '920012345', social: 'https://example.com/company' } },
        evidence: { board_before: [{ id: 101, uri: 'data:image/svg+xml;base64,' + readFileSync('resources/auction-reports/fallback.svg').toString('base64'), fit: 'cover', focal_x: 75, focal_y: 10 }] },
    }, async page => {
        const result = await page.evaluate(() => {
            const frame = document.querySelector('[data-page-type="haram-cover"]').getBoundingClientRect();
            return {
                cards: [...document.querySelectorAll('[data-haram-card]')].map(card => {
                    const box = card.getBoundingClientRect();
                    const label = card.querySelector('.haram-card-label').getBoundingClientRect();
                    return { width: box.width, maximum: frame.width / 2, right: box.right, labelRight: label.right, bottom: box.bottom, limit: frame.top + frame.height * 775 / 842.25 };
                }),
                contacts: [...document.querySelectorAll('[data-haram-contact]')].map(row => {
                    const icon = row.querySelector('svg').getBoundingClientRect();
                    const text = row.querySelector('a,span').getBoundingClientRect();
                    return text.left - icon.right;
                }),
                imageFit: getComputedStyle(document.querySelector('[data-report-attachment="101"]')).objectFit,
                imagePosition: getComputedStyle(document.querySelector('[data-report-attachment="101"]')).objectPosition,
            };
        });
        assert.equal(result.cards.length, 4);
        for (const card of result.cards) {
            assert.ok(card.width <= card.maximum);
            assert.ok(card.right - card.labelRight >= 10);
            assert.ok(card.bottom < card.limit);
        }
        for (const gap of result.contacts) assert.ok(gap >= 15);
        assert.equal(result.imageFit, 'cover');
        assert.equal(result.imagePosition, '75% 10%');
    });
});

test('marketing overflow continues its own section before the following page family', async () => {
    await render({ marketing: { campaigns: [
        { id: 1, platform: 'x', impressions: 0, views: 1000006, posts: 12, reach: 200 },
        { id: 2, platform: 'tiktok', impressions: 1, views: 2, profile_views: 3, reach: 4, profile_change: 5, reach_change: 6, views_change: 7 },
        { id: 3, platform: 'snapchat', impressions: 8, views: 9 },
    ] } }, async page => {
        // A constrained body forces the same measured continuation path used for tall content.
        await page.evaluate(async () => {
            const sections = [...document.querySelectorAll('.haram-marketing-section')];
            for (const section of sections) section.style.minHeight = '300pt';
            const code = document.querySelector('[data-haram-layout]').textContent;
            const script = document.createElement('script');
            script.textContent = code;
            document.body.append(script);
            while (!window.haramReportReady) await new Promise(resolve => setTimeout(resolve, 10));
        });
        assert.equal(await page.evaluate(() => window.haramReportError), null);
        const result = await page.evaluate(() => ({
            families: [...document.querySelectorAll('.auction-print-page')].map(page => page.dataset.pageType),
            channels: [...document.querySelectorAll('[data-haram-channel]')].map(section => section.dataset.haramChannel),
            metric: document.querySelector('[data-report-field="x_views"]').textContent,
        }));
        assert.deepEqual(result.channels, ['x', 'tiktok', 'snapchat']);
        assert.equal(result.metric, '1,000,006');
        assert.ok(result.families.includes('haram-marketing-continuation'));
        const end = result.families.lastIndexOf('haram-marketing-continuation');
        assert.equal(result.families[end + 1], 'haram-closing');
    });
});

test('cover identity uses current names and category and fits long Arabic names', async () => {
    const company = 'شركة الاختبار للاستثمار والتطوير العقاري '.repeat(6);
    const issuer = 'جهة الإسناد وإدارة الأصول العقارية '.repeat(6);
    await render({ branding: { company_name: company, issuing_entity_name: issuer }, auction: { name: 'مزاد جديد مزاد جديد مزاد الرياض', category: 'مزاد منقولات' } }, async page => {
        const identity = await page.evaluate(() => {
            const node = document.querySelector('.haram-cover-identity');
            const bounds = node.getBoundingClientRect();
            const title = document.querySelector('[data-report-field="name"]')?.getBoundingClientRect();
            return { values: [...node.children].map(child => child.textContent), height: node.scrollHeight, limit: node.clientHeight, bottom: bounds.bottom, titleTop: title?.top };
        });
        assert.deepEqual(identity.values, [company, issuer, 'مزاد منقولات']);
        assert.ok(identity.height <= identity.limit + 1);
        if (identity.titleTop) assert.ok(identity.bottom < identity.titleTop, JSON.stringify(identity));
    });
});
