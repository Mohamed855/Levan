import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas } from '@napi-rs/canvas';

async function render(scenario, callback, beforeLayout = null) {
    execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', scenario], { stdio: 'pipe' });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        const html = readFileSync(`output/pdf/asir-native-content/${scenario}.html`, 'utf8');
        await page.setContent(beforeLayout ? html.replace(/<script data-asir-layout>[\s\S]*?<\/script>/, '') : html, { waitUntil: 'load' });
        if (beforeLayout) {
            await beforeLayout(page);
            await page.addScriptTag({ content: readFileSync('resources/auction-reports/asir/layout.js', 'utf8') });
        }
        await page.waitForFunction('window.asirReportReady === true || window.asirReportError !== null');
        assert.equal(await page.evaluate(() => window.asirReportError), null);
        await callback(page);
    } finally { await browser.close(); }
}

test('board photo numbers retain two digits and continue across installation and removal pages', async () => {
    await render('current', async page => {
        for (const family of ['asir-board-installation', 'asir-board-removal']) {
            const numbers = await page.$$eval(`[data-page-type^="${family}"] .asir-board-number`, nodes => nodes.map(node => node.textContent.trim()));
            assert.deepEqual(numbers, ['٠١','٠٢','٠٣','٠٤','٠٥','٠٦','٠٧','٠٨','٠٩','١٠','١١','١٢','١٣']);
        }
    });
});

test('link buttons keep the individual reference dimensions with complete normal and long labels', async () => {
    const longLabel='رابط المزاد على '+'W'.repeat(255);
    for (const long of [false, true]) {
        await render('current', async page => {
            const buttons = await page.$$eval('.asir-link', nodes => nodes.map(node => {
                const box = node.getBoundingClientRect(), text = node.querySelector('span');
                const css = getComputedStyle(node);
                return {role:node.dataset.linkRole, width:box.width*.75,height:box.height*.75,font:parseFloat(css.fontSize)*.75,href:node.href,
                    label:node.textContent,inside:text && text.scrollWidth <= text.clientWidth+1 && text.getBoundingClientRect().height <= node.clientHeight-parseFloat(css.paddingTop)-parseFloat(css.paddingBottom)+1};
            }));
            for (const [role,width,height,font] of [['brochure',144.443,41.761,15.158],['platform',312.261,66.522,18.109],['video',215.766,66.522,24.145]]) {
                const button=buttons.find(button=>button.role===role);
                assert.ok(button);
                assert.ok(Math.abs(button.width-width)<.2 && Math.abs(button.height-height)<.2,`${role}: ${button.width}×${button.height}`);
                if(!long)assert.ok(Math.abs(button.font-font)<.01,`${role}: reference font ${font}pt, actual ${button.font}pt.`);
                assert.equal(button.inside,true,`${role}: keep the complete label inside the source button.`);
                assert.ok(button.href.startsWith('https://example.com/'));
                if(long && role==='platform')assert.equal(button.label,longLabel);
            }
        },long ? page=>page.evaluate(label=>{
            const node=document.querySelector('.asir-link[data-link-role="platform"]');
            (node.querySelector('span')??node).textContent=label;
        },longLabel) : null);
    }
});

test('marketing methodology fills one shared page and continues only when its combined text needs more space', async () => {
    for (const repetitions of [1, 120]) {
        const fields = ['planning_methodology', 'city_context', 'brochure_methodology', 'audience_methodology', 'messages_methodology'];
        const texts = fields.map((field, index) => `تفاصيل منهجية التسويق المسجلة ${index + 1}. `.repeat(repetitions));
        await render('current', async page => {
            const result = await page.evaluate(fields => {
                const sheets = [...document.querySelectorAll('[data-page-type="asir-planning"],[data-page-type="asir-design"],[data-page-type="asir-promotion"],[data-page-type="asir-planning-continuation"],[data-page-type="asir-design-continuation"],[data-page-type="asir-promotion-continuation"]')];
                return {
                    texts: fields.map(field => sheets.flatMap(sheet => [...sheet.querySelectorAll(`[data-report-field="${field}"]`)].map(node => node.textContent)).join('')),
                    headings: sheets.flatMap(sheet => [...sheet.querySelectorAll('.asir-methodology-heading')].map(node => node.textContent)),
                    sheets: sheets.map(sheet => {
                        const frame = sheet.getBoundingClientRect();
                        const sections = [...sheet.querySelectorAll('[data-asir-section]')];
                        return { bottom: (sections.at(-1).getBoundingClientRect().bottom-frame.top)*.75,
                            panel: [getComputedStyle(sheet,'::after').top,getComputedStyle(sheet,'::after').bottom,getComputedStyle(sheet,'::after').width] };
                    }),
                };
            }, fields);
            assert.deepEqual(result.texts, texts);
            assert.deepEqual([...new Set(result.headings)], ['التخطيط والاستعداد', 'الإعداد والتصميم', 'النشر والترويج']);
            if (repetitions === 1) assert.equal(result.sheets.length, 1, 'Short methodology sections must share the same page.');
            else {
                assert.ok(result.sheets.length > 1);
                assert.ok(result.sheets.slice(0,-1).every(sheet => sheet.bottom >= 780), 'Fill the available page height before continuing.');
            }
            assert.ok(result.sheets.every(sheet => sheet.bottom <= 826));
            assert.ok(result.sheets.every(sheet => JSON.stringify(sheet.panel) === JSON.stringify(result.sheets[0].panel)));
        }, page => page.evaluate(({fields,texts}) => {
            fields.forEach((field,index) => {document.querySelector(`[data-report-field="${field}"]`).textContent=texts[index];});
        }, {fields,texts}));
    }
});

test('board evidence pages do not print Google or video viewing statistics', async () => {
    await render('current', async page => {
        const boards = await page.$$eval('[data-page-type^="asir-board-"]', sheets => sheets.map(sheet => ({
            metrics: sheet.querySelectorAll('[data-asir-channel="google"],[data-report-field="google_views"]').length,
            text: sheet.textContent,
        })));
        assert.ok(boards.length > 0);
        assert.ok(boards.every(sheet => sheet.metrics === 0 && !sheet.text.includes('عدد مشاهدات الفيديو')));
    });
});

test('platform status backgrounds match the system theme for every auction status', async () => {
    await render('populated', async page => {
        const theme = readFileSync('resources/assets/vendor/scss/_custom-variables/_bootstrap-extended.scss', 'utf8');
        for (const name of ['warning', 'success', 'secondary']) {
            const hex = theme.match(new RegExp(`\\$${name}:\\s*(#[0-9a-f]+)`, 'i'))[1];
            const expected = hex.slice(1).match(/../g).map(value => parseInt(value,16));
            const color = await page.$eval('.asir-platform-status', (badge,name) => {
                badge.className = `asir-platform-status bg-${name}`;
                return getComputedStyle(badge).backgroundColor.match(/\d+/g).slice(0,3).map(Number);
            }, name);
            assert.deepEqual(color, expected, name);
        }
    });
});

test('portrait evidence keeps transparent letterboxing in the printed PDF', async () => {
    const portrait = 'data:image/svg+xml;base64,' + Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="100"><rect width="40" height="100" fill="#e00000"/></svg>').toString('base64');
    await render('populated', async page => {
        const points = await page.evaluate(() => ['.asir-board-image img','.asir-brochure-image img','.asir-thumbnail-image img','.asir-x-grid img','.asir-whatsapp-grid img','.asir-social-image img','.asir-broker-image img','.asir-video-evidence img'].map(selector => {
            const image = document.querySelector(selector);
            const sheet = image.closest('.auction-print-page');
            const bounds = sheet.getBoundingClientRect(), rect = image.getBoundingClientRect();
            return {selector, page:[...document.querySelectorAll('.auction-print-page')].indexOf(sheet)+1, x:(rect.left-bounds.left)*.75+3, y:(rect.top-bounds.top+rect.height/2)*.75};
        }));
        const pdf = await getDocument({data:new Uint8Array(await page.pdf({preferCSSPageSize:true, printBackground:true}))}).promise;
        try {
            for(const point of points){
                const sheet = await pdf.getPage(point.page), viewport = sheet.getViewport({scale:2});
                const canvas = createCanvas(Math.ceil(viewport.width),Math.ceil(viewport.height)), context=canvas.getContext('2d');
                await sheet.render({canvasContext:context,viewport}).promise;
                const rgb=[...context.getImageData(Math.floor(point.x*2),Math.floor(point.y*2),1,1).data].slice(0,3);
                assert.ok(!rgb.every(value=>value>245), `${point.selector}: white letterboxing ${rgb}`);
            }
        } finally {await pdf.destroy();}
    }, async page => page.evaluate(portrait => {
        document.querySelectorAll('[data-report-attachment]').forEach(image => {image.src=portrait;image.style.objectFit='contain';});
        const extra=document.querySelector('.asir-video-hero [data-report-attachment]').closest('figure').cloneNode(true);
        extra.className='asir-video-evidence';
        const image=extra.querySelector('img');image.style.width='100%';image.style.height='100pt';
        document.querySelector('.asir-video-statistics').append(extra);
    }, portrait));
});

test('property table dividers and text stay inside the frame while its header keeps its overhang', async () => {
    await render('populated', async page => {
        const box = await page.evaluate(() => {
            const frame = document.querySelector('.asir-index-table-frame');
            const table = frame.querySelector('table');
            const bounds = frame.getBoundingClientRect();
            const paper = frame.closest('.auction-print-page').getBoundingClientRect();
            const body = table.tBodies[0].getBoundingClientRect();
            const heading = table.tHead.getBoundingClientRect();
            return {
                textInside: [...table.querySelectorAll('tbody bdi')].every(text => text.getBoundingClientRect().left >= bounds.left - 1 && text.getBoundingClientRect().right <= bounds.right + 1),
                strip: [(bounds.left - paper.left) * .75 - 9, (body.top - paper.top) * .75 + 3, 5, body.height * .75 - 6],
                headerStrip: [(bounds.left - paper.left) * .75 - 9, (heading.top - paper.top) * .75 + 3, 5, heading.height * .75 - 6] };
        });
        assert.equal(box.textInside, true, 'The complete one- and two-digit property numbers must fit the frame.');
        const outsideInk = async (region = box.strip) => {
            const pdf = await getDocument({data:new Uint8Array(await page.pdf({preferCSSPageSize:true, printBackground:true}))}).promise;
            try {
                const sheet = await pdf.getPage(2);
                const viewport = sheet.getViewport({scale:2});
                const canvas = createCanvas(Math.ceil(viewport.width), Math.ceil(viewport.height));
                const context = canvas.getContext('2d');
                await sheet.render({canvasContext:context, viewport}).promise;
                const pixels = context.getImageData(...region.map(value => Math.floor(value * 2))).data;
                let count = 0;
                for (let index=0; index<pixels.length; index+=4) if(pixels[index]<90 && pixels[index+1]<130 && pixels[index+2]<170 && pixels[index+2]>pixels[index]+25) count++;
                return count;
            } finally {await pdf.destroy();}
        };
        assert.equal(await outsideInk(), 0, 'The PDF must have no blue divider ink outside its frame.');
        assert.ok(await outsideInk(box.headerStrip) > 100, 'The original blue heading must still extend outside the frame.');
        await page.$eval('.asir-property-table', table => {table.style.width='calc(100% + 24pt)'; table.style.marginInline='-12pt';});
        assert.ok(await outsideInk() > 10, 'The original uncontained dividers must be detected by the same PDF check.');
    });
});

test('introduction preserves the reference logo and left text column while filling its vertical region', async () => {
    for (const repetitions of [1, 25, 200]) {
        const text = 'تفاصيل العقارات والنتائج المسجلة للمزاد. '.repeat(repetitions);
        await render('populated', async page => {
            const pages = await page.evaluate(() => [...document.querySelectorAll('[data-page-type^="asir-introduction"]')].map(page => {
                const frame = page.getBoundingClientRect();
                const text = page.querySelector('[data-report-field="introduction"]');
                const bounds = text.getBoundingClientRect();
                const logo = page.querySelector('.asir-introduction-logo').getBoundingClientRect();
                return { text: text.textContent, left: (bounds.left - frame.left) * .75, right: (frame.right - bounds.right) * .75,
                    top: (bounds.top - frame.top) * .75, bottom: (bounds.bottom - frame.top) * .75,
                    logo:[(logo.left-frame.left)*.75,(logo.top-frame.top)*.75,logo.width*.75,logo.height*.75] };
            }));
            assert.equal(pages.map(page => page.text).join(''), text);
            assert.ok(pages.every(page => Math.abs(page.left-15)<1 && Math.abs(page.right-260.276)<1));
            for (const sheet of pages) [347.226,27.48,248.05,414.52].forEach((expected,index)=>assert.ok(Math.abs(sheet.logo[index]-expected)<1));
            if (repetitions <= 25) assert.equal(pages.length, 1);
            if (repetitions === 1) assert.ok(Math.abs(pages[0].top-337)<1);
            if (repetitions === 200) {
                assert.ok(pages.length > 1);
                assert.ok(pages.slice(0, -1).every(page => page.bottom >= 785));
                assert.ok(pages.slice(1).every(page => page.top <= 40));
            }
        }, page => page.evaluate(text => { document.querySelector('[data-report-field="introduction"]').textContent = text; }, text));
    }
});

test('current auction inputs retain complete reference groups without isolated links files or thirteenth board images', async () => {
    await render('current', async page => {
        const result=await page.evaluate(()=>({
            types:[...document.querySelectorAll('.auction-print-page')].map(node=>node.dataset.pageType),
            video:[...document.querySelectorAll('[data-page-type^="asir-video"]')].map(node=>({images:node.querySelectorAll('[data-report-attachment]').length,links:node.querySelectorAll('[data-link-role="video"]').length})),
            platform:[...document.querySelectorAll('[data-page-type^="asir-platform"]')].map(node=>({images:node.querySelectorAll('[data-report-attachment]').length,links:node.querySelectorAll('[data-link-role="platform"]').length})),
            brokers:[...document.querySelectorAll('[data-page-type^="asir-brokers"]')].map(node=>({names:[...node.querySelectorAll('.asir-broker-name p')].map(p=>p.textContent),images:node.querySelectorAll('[data-report-attachment]').length})),
            boards:[...document.querySelectorAll('[data-page-type^="asir-board-installation"]')].map(node=>node.querySelectorAll('[data-report-attachment]').length),
            social:document.querySelectorAll('[data-page-type="asir-social"]').length,
            cards:document.querySelectorAll('.asir-social-card').length,
            metadataFont:getComputedStyle(document.querySelector('.asir-index-metadata dt')).fontFamily,
        }));
        assert.deepEqual(result.video,[{images:1,links:1}]);
        assert.deepEqual(result.platform,[{images:1,links:1}]);
        assert.deepEqual(result.brokers,[{names:['مكتب الوسيط الأول — ملف تجريبي'],images:1},{names:['مكتب الوسيط الثاني — ملف تجريبي'],images:1}]);
        assert.deepEqual(result.boards,[6,7]);
        assert.equal(result.social,1);assert.equal(result.cards,3);
        assert.match(result.metadataFont,/IBMReport/);
    });
});

test('long current broker text keeps its complete name and document together on the continuation', async () => {
    const name='W'.repeat(255), contract='تفاصيل عقد الوسيط المسجلة. '.repeat(30);
    await render('current', async page => {
        const result=await page.evaluate(()=>({
            contract:[...document.querySelectorAll('[data-page-type^="asir-brokers"] [data-report-field="contract_text"]')].map(node=>node.textContent),
            documents:[...document.querySelectorAll('[data-page-type^="asir-brokers"] [data-report-attachment]')].map(image=>{
                const page=image.closest('.auction-print-page'),frame=page.getBoundingClientRect(),bounds=image.getBoundingClientRect();
                return {name:page.querySelector('.asir-broker-name p').textContent,height:bounds.height*.75,bottom:(bounds.bottom-frame.top)*.75,fit:image.style.objectFit};
            }),
        }));
        assert.ok(result.contract.join('').includes(contract));
        assert.equal(result.documents.length,2);
        assert.equal(result.documents[0].name,name);
        assert.ok(result.documents.every(image=>image.height>=299 && image.bottom<=826 && image.fit==='contain'));
    }, async page=>page.evaluate(({name,contract})=>{
        const first=document.querySelector('[data-page-type="asir-brokers"]');
        first.querySelector('.asir-broker-name p').textContent=name;
        first.querySelector('[data-report-field="contract_text"]').textContent=contract;
    },{name,contract}));
});

test('Asir tables retain the original open grid, column proportions and interrupted detail frame', async () => {
    await render('populated', async page => {
        const result = await page.evaluate(() => {
            const property = document.querySelector('.asir-property-table');
            const cells = [...property.querySelector('tbody tr').cells];
            const widths = cells.map(cell => cell.getBoundingClientRect().width / property.getBoundingClientRect().width);
            const detail = document.querySelector('.asir-sold-table-frame');
            return {
                widths,
                serialBorder: getComputedStyle(cells[0]).borderBottomWidth,
                lastBorder: getComputedStyle(property.querySelector('tbody tr:last-child td:last-child')).borderBottomWidth,
                detailTag: detail.tagName,
                legend: detail.querySelector('legend')?.textContent.trim(),
                deedSize: parseFloat(getComputedStyle(document.querySelector('.asir-sold-table td:first-child')).fontSize) * .75,
                unsoldHeaderFont: getComputedStyle(document.querySelector('.asir-unsold-table th:nth-child(2)')).fontFamily,
                unsoldSerialHeading: document.querySelector('.asir-unsold-table th:first-child').textContent.trim(),
            };
        });
        [33, 81, 107, 80, 85, 116].forEach((width, index) => assert.ok(Math.abs(result.widths[index] - width / 502) < .002, `column ${index}`));
        assert.equal(result.serialBorder, '0px');
        assert.equal(result.lastBorder, '0px');
        assert.equal(result.detailTag, 'FIELDSET');
        assert.equal(result.legend, 'تفاصيل');
        assert.ok(Math.abs(result.deedSize - 15.973) < .01);
        assert.match(result.unsoldHeaderFont, /STVReport/);
        assert.equal(result.unsoldSerialHeading, '');
    });
});

test('WhatsApp keeps the original statistics order and separated total row', async () => {
    await render('populated', async page => {
        const rows = await page.evaluate(() => [...document.querySelectorAll('.asir-whatsapp-table tr')].map(row => ({
            field: row.querySelector('[data-report-field]').dataset.reportField,
            label: row.querySelector('th').textContent.trim(),
            height: row.getBoundingClientRect().height * .75,
            gap: parseFloat(getComputedStyle(row.querySelector('th')).borderTopWidth) * .75,
        })));
        assert.deepEqual(rows.map(row => row.field), ['whatsapp_messages', 'whatsapp_views', 'whatsapp_delivered', 'whatsapp_failed', 'whatsapp_pending', 'whatsapp_total']);
        assert.equal(rows[0].label, 'تم الإرسال');
        assert.equal(rows.at(-1).label, 'الكل');
        assert.ok(rows.every(row => row.height >= 20));
        assert.ok(rows.at(-1).gap >= 12);
    });
});

test('every internal Asir page uses native content and every PDF page matches the measured DOM', async () => {
    await render('populated', async page => {
        const result = await page.evaluate(() => ({
            count: document.querySelectorAll('.auction-print-page').length,
            graphics: document.querySelectorAll('.asir-native-page svg,.asir-native-page canvas,.asir-native-page object').length,
            headings: [...document.querySelectorAll('.asir-native-page')].every(page => page.querySelector('header h1')),
            attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(image => image.dataset.reportAttachment),
            alignments: [...document.querySelectorAll('.asir-table td')].map(cell => ({ align: getComputedStyle(cell).textAlign, padding: parseFloat(getComputedStyle(cell).paddingRight) })),
        }));
        assert.equal(result.graphics, 0);
        assert.equal(result.headings, true);
        // X evidence intentionally appears in its campaign and the social summary.
        assert.equal(result.attachments.filter(id => id === '304').length, 1);
        assert.equal(result.attachments.filter(id => id === '404').length, 1);
        assert.ok(result.alignments.every(cell => cell.align === 'center' && cell.padding >= 4));
        const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
    });
});

test('maximum valid thumbnail names remain complete without breaking PDF generation or duplicating evidence', async () => {
    for (const name of ['W'.repeat(255), 'م'.repeat(255)]) {
        let attachments;
        await render('populated', async page => {
            const result = await page.evaluate(() => ({
                names: [...document.querySelectorAll('.asir-thumbnail-name [data-asir-paragraph],.asir-platform-name strong')].map(node => node.textContent),
                thumbnailFits: [...document.querySelectorAll('.asir-platform-name strong')].every(node => node.getBoundingClientRect().bottom <= node.closest('.asir-platform-hero').getBoundingClientRect().bottom + 1 && parseFloat(getComputedStyle(node).fontSize) >= 8 / .75 - .1),
                attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment),
                count: document.querySelectorAll('.auction-print-page').length,
            }));
            assert.deepEqual(result.names, [name, name]);
            assert.equal(result.thumbnailFits, true);
            assert.deepEqual(result.attachments, attachments);
            const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
            try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
        }, async page => {
            attachments = await page.evaluate(name => {
                document.querySelectorAll('.asir-platform-name strong').forEach(node => { node.textContent = name; });
                return [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment);
            }, name);
        });
    }
});

test('long document captions stay visible and fit without cropping text or duplicating evidence', async () => {
    const caption = 'W'.repeat(255);
    let attachments;
    await render('populated', async page => {
        const result = await page.evaluate(() => ({
            text: document.querySelector('.asir-broker-image figcaption').textContent,
            color: getComputedStyle(document.querySelector('.asir-broker-image figcaption')).color,
            fit: document.querySelector('.asir-broker-image [data-report-attachment]').style.objectFit,
            attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment),
            count: document.querySelectorAll('.auction-print-page').length,
        }));
        assert.equal(result.text, caption);
        assert.equal(result.color, 'rgb(255, 255, 255)', 'Captions remain readable against the page behind the transparent image frame.');
        assert.equal(result.fit, 'contain');
        assert.deepEqual(result.attachments, attachments);
        const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
    }, async page => {
        attachments = await page.evaluate(text => {
            const label = document.createElement('figcaption');
            label.textContent = text;
            document.querySelector('.asir-broker-image').append(label);
            return [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment);
        }, caption);
    });
});

test('native evidence frames retain the measured original Asir positions and dimensions', async () => {
    await render('populated', async page => {
        const frames = await page.evaluate(() => Object.fromEntries(['asir-board-installation-1', 'asir-board-installation-2', 'asir-brochure', 'asir-video', 'asir-x', 'asir-whatsapp', 'asir-brokers'].map(type => {
            const page = document.querySelector(`[data-page-type="${type}"]`);
            const image = page.querySelector('[data-report-attachment]');
            const bounds = page.getBoundingClientRect();
            const rect = image.getBoundingClientRect();
            return [type, [(rect.x - bounds.x) * .75, (rect.y - bounds.y) * .75, rect.width * .75, rect.height * .75]];
        })));
        const dates = await page.evaluate(() => {
            const page = document.querySelector('[data-page-type="asir-index"]');
            const frame = page.getBoundingClientRect();
            const card = page.querySelector('.asir-date-card').getBoundingClientRect();
            return [(card.x - frame.x) * .75, (card.y - frame.y) * .75, card.width * .75, card.height * .75];
        });
        [319.22, 234.94, 242.21, 88.04].forEach((expected, index) => assert.ok(Math.abs(dates[index] - expected) < 1.5, `Original date card ${expected}, native ${dates[index]}`));
        for (const [type, original] of Object.entries({
            'asir-board-installation-1': [334.681, 364.393, 156.362, 106.731],
            'asir-brochure': [440.795, 371.261, 57.369, 81.146],
            'asir-video': [115.583, 366.137, 273.484, 187.305],
            'asir-x': [391.054, 370.056, 110.353, 148.046],
            'asir-whatsapp': [160.539, 558.545, 291.219, 116],
            'asir-brokers': [135.5, 325.8, 356.1, 463.6],
        })) {
            original.forEach((expected, index) => assert.ok(Math.abs(frames[type][index] - expected) < 1.5, `${type}: original ${expected}, native ${frames[type][index]}`));
        }
    });
});

test('evidence appendices retain original frames, complete captions and campaign metadata', async () => {
    await render('appendix', async page => {
        const result = await page.evaluate(() => {
            const gallery = [...document.querySelectorAll('.asir-native-gallery')].find(node => node.querySelector('[data-report-attachment="901"]'));
            const frame = gallery.getBoundingClientRect();
            return {
                frames: [...gallery.querySelectorAll('[data-report-attachment]')].map(node => {
                    const rect = node.getBoundingClientRect();
                    return [(rect.x - frame.x) * .75, (rect.y - frame.y) * .75, rect.width * .75, rect.height * .75];
                }),
                caption: gallery.querySelector('figcaption').textContent,
                campaign: document.querySelector('.asir-gallery-main--campaign').textContent,
                url: document.querySelector('.asir-gallery-main--campaign a').href,
            };
        });
        [[95, 350, 190, 100], [310, 350, 190, 100], [95, 485, 190, 100]].forEach((original, slot) => original.forEach((expected, index) => assert.ok(Math.abs(result.frames[slot][index] - expected) < 1.5, `Appendix frame ${slot}: original ${expected}, native ${result.frames[slot][index]}`)));
        assert.equal(result.caption, 'تيك توك - مرفق 901 - 2');
        assert.ok(result.campaign.includes('حملة إضافية'));
        assert.ok(result.campaign.includes('42'));
        assert.equal(result.url, 'https://example.com/extra');
    });
});

test('social badges and unsold table corners preserve their original CSS geometry', async () => {
    await render('populated', async page => {
        const styles = await page.evaluate(() => {
            const badge = document.querySelector('.asir-social-label');
            const table = document.querySelector('.asir-unsold-table');
            return {
                badgeRadius: getComputedStyle(badge).borderTopLeftRadius,
                badgeColor: getComputedStyle(badge, '::before').backgroundColor,
                headerClip: getComputedStyle(table.querySelector('th:last-child')).clipPath,
                lastClip: getComputedStyle(table.querySelector('tbody tr:last-child td:first-child')).clipPath,
            };
        });
        assert.ok(Math.abs(parseFloat(styles.badgeRadius) * .75 - 27.76) < .1);
        assert.equal(styles.badgeColor, 'rgb(244, 238, 30)');
        assert.notEqual(styles.headerClip, 'none');
        assert.notEqual(styles.lastClip, 'none');
    });
});

test('the application PDF preserves logo transparency and a solid complete delivery chart', async () => {
    await render('populated', async page => {
        const points = await page.evaluate(() => {
            const frame = document.querySelector('[data-page-type="asir-whatsapp"]');
            const bounds = frame.getBoundingClientRect();
            const logo = frame.querySelector('.asir-whatsapp-link a').getBoundingClientRect();
            const chart = frame.querySelector('[data-report-chart="whatsapp"]').getBoundingClientRect();
            return {
                page: [...document.querySelectorAll('.auction-print-page')].indexOf(frame) + 1,
                clear: [(logo.x - bounds.x) * .75 + 8, (logo.y - bounds.y) * .75 + 8],
                chart: [-1, 0, 1].map(offset => [(chart.x - bounds.x + chart.width / 2) * .75 + offset, (chart.y - bounds.y) * .75 + 15]),
            };
        });
        execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', 'populated', '--pdf'], { stdio: 'pipe' });
        const pdf = await getDocument({ data: new Uint8Array(readFileSync('output/pdf/asir-native-content/populated-engine.pdf')) }).promise;
        try {
            const sheet = await pdf.getPage(points.page);
            const viewport = sheet.getViewport({ scale: 2 });
            const canvas = createCanvas(Math.ceil(viewport.width), Math.ceil(viewport.height));
            const context = canvas.getContext('2d');
            await sheet.render({ canvasContext: context, viewport }).promise;
            const rgb = point => [...context.getImageData(Math.floor(point[0] * 2), Math.floor(point[1] * 2), 1, 1).data].slice(0, 3);
            assert.deepEqual(rgb(points.clear), [255, 255, 255], 'The transparent corner of the WhatsApp icon must reveal the white card.');
            for (const point of points.chart) assert.deepEqual(rgb(point), [0, 85, 137], 'A complete delivery chart must be uniformly blue, without a zero-value seam.');
        } finally { await pdf.destroy(); }
    });
});

for (const scenario of ['many', 'long']) {
    test(`${scenario} content continues under the same heading with all rows, attachments, and text`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => ({
                pages: [...document.querySelectorAll('.auction-print-page')].map(page => ({ type: page.dataset.pageType, title: page.querySelector('h1')?.textContent })),
                rows: [...document.querySelectorAll('[data-asir-table="properties"] tbody tr')].map(row => row.cells[3].textContent.trim()),
                sold: [...document.querySelectorAll('[data-asir-property]')].map(section => section.dataset.asirProperty),
                unsold: [...document.querySelectorAll('[data-asir-table="unsold"] tbody tr')].map(row => ({ id: row.dataset.asirRow, text: row.cells[4].textContent })),
                introduction: [...document.querySelectorAll('[data-report-field="introduction"]')].map(node => node.textContent).join(''),
                planning: [...document.querySelectorAll('[data-report-field="planning_methodology"]')].map(node => node.textContent).join(''),
                images: [...document.querySelectorAll('[data-report-attachment]')].map(node => node.dataset.reportAttachment),
            }));
            assert.deepEqual(result.rows, Array.from({ length: 47 }, (_, index) => `PROP-${index + 1}`));
            assert.equal(result.sold.length, 19);
            assert.equal(new Set(result.unsold.map(row => row.id)).size, 28);
            for (let index = 20; index <= 47; index++) {
                const id = String(index).replace(/\d/g, value => '٠١٢٣٤٥٦٧٨٩'[value]);
                assert.equal(result.unsold.filter(row => row.id === id).map(row => row.text).join(''), `سبب عدم البيع المسجل للعقار ${index}. `.repeat(scenario === 'long' ? 100 : 1));
            }
            for (const base of [1000, 4000, 8000]) for (let index = 1; index <= 17; index++) assert.ok(result.images.includes(String(base + index)));
            if (scenario === 'long') {
                assert.equal(result.introduction, 'التقرير التفصيلي لنتائج المزاد والعقارات المعروضة. '.repeat(300));
                assert.equal(result.planning, 'دراسة الفئات المستهدفة ووسائل الوصول إليها وواقع السوق العقاري. '.repeat(200));
            }
            for (const family of ['asir-index', 'asir-introduction', 'asir-planning', 'asir-sold-assets', 'asir-unsold-assets']) {
                const indices = result.pages.flatMap((page, index) => page.type.replace(/-continuation$/, '') === family ? [index] : []);
                assert.equal(indices.at(-1) - indices[0] + 1, indices.length);
                assert.ok(indices.every(index => result.pages[index].title === result.pages[indices[0]].title));
            }
        });
    });
}

test('sparse reports keep recorded zero without adding absent evidence or result sections', async () => {
    await render('sparse', async page => {
        const result = await page.evaluate(() => ({
            types: [...document.querySelectorAll('.auction-print-page')].map(page => page.dataset.pageType),
            values: [...document.querySelectorAll('[data-report-field="x_views"]')].map(node => node.textContent),
            images: document.querySelectorAll('.asir-native-page [data-report-attachment]').length,
        }));
        assert.deepEqual(result.types, ['asir-cover', 'asir-index', 'asir-x', 'asir-social', 'asir-closing']);
        assert.deepEqual(result.values, ['0', '0']);
        assert.equal(result.images, 0);
    });
});
