const puppeteer = require('puppeteer');
const http = require('http');
const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '../../../..');
const publicDir = path.join(projectRoot, 'public');
const adminHtmlPath = path.join(__dirname, 'admin_challenge.html');
const tenantHtmlPath = path.join(__dirname, 'tenant_challenge.html');
const screenshotsDir = path.join(__dirname, 'screenshots');

if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

const PORT = 8199;
const submitCounts = { admin: 0, tenant: 0 };
const validationCounts = { admin: 0, tenant: 0 };

function assert(condition, message) {
    if (!condition) {
        throw new Error(`ASSERTION FAILED: ${message}`);
    }
}

// HTTP server providing HTML, Vite assets, form validation endpoint, and challenge POST endpoints
const server = http.createServer((req, res) => {
    const cleanUrl = req.url.split('?')[0];

    // 1. HTML challenge views
    if ((cleanUrl.endsWith('two-factor-challenge') || cleanUrl === '/admin-challenge' || cleanUrl === '/tenant-challenge') && req.method === 'GET') {
        const isTenant = cleanUrl.includes('tenant');
        const htmlPath = isTenant ? tenantHtmlPath : adminHtmlPath;
        let content = fs.readFileSync(htmlPath, 'utf8');
        content = content.replaceAll('http://localhost/', `http://localhost:${PORT}/`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    if (cleanUrl === '/favicon.ico') {
        res.writeHead(204);
        return res.end();
    }

    // 2. Shared Form Validation endpoint
    if (cleanUrl.endsWith('form-validation') && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            validationCounts.total = (validationCounts.total || 0) + 1;
            let parsed = {};
            try {
                parsed = JSON.parse(body);
            } catch {
                const params = new URLSearchParams(body);
                parsed = Object.fromEntries(params.entries());
            }

            // Simulate server validation rejection for invalid code '000000'
            if (parsed.code === '000000') {
                res.writeHead(422, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({
                    success: false,
                    message: 'الكود غير صحيح.',
                    errors: { code: ['الكود غير صحيح.'] }
                }));
            }

            // Success validation response
            res.writeHead(200, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({ success: true, errors: {} }));
        });
        return;
    }

    // 3. Challenge POST submissions
    if ((cleanUrl.endsWith('two-factor-challenge') || cleanUrl === '/admin-challenge' || cleanUrl === '/tenant-challenge') && req.method === 'POST') {
        const panel = cleanUrl.includes('superadmin') || cleanUrl.includes('admin') ? 'admin' : 'tenant';
        submitCounts[panel]++;

        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            const params = new URLSearchParams(body);
            const code = params.get('code');
            const recoveryCode = params.get('recovery_code');

            if (code === '000000') {
                res.writeHead(302, { 'Location': `${cleanUrl}?error=invalid` });
                return res.end();
            }

            res.writeHead(302, {
                'Location': '/dashboard',
                'Set-Cookie': `auth_${panel}_session=authenticated; Path=/; HttpOnly`
            });
            return res.end();
        });
        return;
    }

    // 4. Authenticated Dashboard endpoint
    if (cleanUrl === '/dashboard') {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(`<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="utf-8"><title>لوحة التحكم</title></head>
<body>
    <h1 id="dashboardTitle">لوحة التحكم الرئيسية</h1>
    <p>تم تسجيل الدخول بنجاح واستكمال التحقق بخطوتين.</p>
</body>
</html>`);
    }

    // 5. Static assets from public/
    const filePath = path.join(publicDir, cleanUrl.replace(/^\//, ''));
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
        const ext = path.extname(filePath).toLowerCase();
        const contentTypes = {
            '.js': 'application/javascript; charset=utf-8',
            '.css': 'text/css; charset=utf-8',
            '.svg': 'image/svg+xml',
            '.png': 'image/png',
            '.jpg': 'image/jpeg',
            '.woff': 'font/woff',
            '.woff2': 'font/woff2',
            '.ttf': 'font/ttf'
        };
        const contentType = contentTypes[ext] || 'application/octet-stream';
        res.writeHead(200, { 'Content-Type': contentType });
        return res.end(fs.readFileSync(filePath));
    }

    console.log(`[HTTP 404 Not Found]: ${req.url}`);
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not Found');
});

const screenSizes = [
    { name: 'desktop-1440', width: 1440, height: 900 },
    { name: 'laptop-1024', width: 1024, height: 768 },
    { name: 'tablet-768', width: 768, height: 1024 },
    { name: 'mobile-390', width: 390, height: 844 },
    { name: 'mobile-360', width: 360, height: 800 },
    { name: 'narrow-320', width: 320, height: 740 }
];

async function runSuite() {
    server.listen(PORT);
    console.log(`Verification test server listening on http://localhost:${PORT}`);

    const logOutput = [];
    const log = (msg) => {
        console.log(msg);
        logOutput.push(msg);
    };

    log('========================================================================');
    log('Puppeteer Real HTTP Verification Suite for Batch A (Round 3)');
    log(`Date: ${new Date().toISOString()}`);
    log('========================================================================\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        for (const panel of ['admin', 'tenant']) {
            const pageUrl = `http://localhost:${PORT}/${panel}-challenge`;
            log(`\n============================================================`);
            log(`TESTING PANEL: ${panel.toUpperCase()}`);
            log(`============================================================`);

            const page = await browser.newPage();
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    log(`  [Browser Console Error]: ${msg.text()}`);
                }
            });

            // 1. Viewport Matrix & Visual Responsiveness (2FA-05)
            log(`\n[2FA-05: Viewport Matrix & Links Alignment]`);
            for (const size of screenSizes) {
                await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
                await page.goto(pageUrl, { waitUntil: 'networkidle0' });

                const layout = await page.evaluate(() => {
                    const linksRow = document.querySelector('.auth-challenge-links-row');
                    const backLink = linksRow?.querySelector('a.auth-back-link');
                    const toggleLink = linksRow?.querySelector('#toggleRecoveryLink');

                    const s = window.getComputedStyle(linksRow);
                    const bRect = backLink.getBoundingClientRect();
                    const tRect = toggleLink.getBoundingClientRect();

                    return {
                        flexWrap: s.flexWrap,
                        backLinkRight: bRect.right,
                        backLinkLeft: bRect.left,
                        backLinkTop: bRect.top,
                        toggleLinkRight: tRect.right,
                        toggleLinkLeft: tRect.left,
                        toggleLinkTop: tRect.top,
                        verticalDifference: Math.abs(bRect.top - tRect.top)
                    };
                });

                const isSameLine = layout.verticalDifference <= 5;
                log(`  ${size.name} (${size.width}x${size.height}): flex-wrap=${layout.flexWrap}, topDiff=${layout.verticalDifference.toFixed(1)}px -> Single row: ${isSameLine ? 'PASS' : 'FAIL'}`);
                assert(isSameLine, `Links wrapped to multiple rows on viewport ${size.name}`);

                const screenshotPath = path.join(screenshotsDir, `${panel}_${size.name}_totp.png`);
                await page.screenshot({ path: screenshotPath });
            }

            // 2. Computed Typography & Color Match (2FA-06)
            log(`\n[2FA-06: Computed Typography Comparison]`);
            await page.setViewport({ width: 1440, height: 900 });
            await page.goto(pageUrl, { waitUntil: 'networkidle0' });

            const styles = await page.evaluate(() => {
                const backLink = document.querySelector('.auth-challenge-links-row a.auth-back-link');
                const toggleLink = document.querySelector('#toggleRecoveryLink');
                const s1 = window.getComputedStyle(backLink);
                const s2 = window.getComputedStyle(toggleLink);

                return {
                    fontSize1: s1.fontSize,
                    fontSize2: s2.fontSize,
                    fontWeight1: s1.fontWeight,
                    fontWeight2: s2.fontWeight,
                    color1: s1.color,
                    color2: s2.color,
                    lineHeight1: s1.lineHeight,
                    lineHeight2: s2.lineHeight
                };
            });

            log(`  Back link:   size=${styles.fontSize1}, weight=${styles.fontWeight1}, color=${styles.color1}, lineHeight=${styles.lineHeight1}`);
            log(`  Toggle link: size=${styles.fontSize2}, weight=${styles.fontWeight2}, color=${styles.color2}, lineHeight=${styles.lineHeight2}`);
            assert(styles.fontSize1 === styles.fontSize2, `Font size mismatch: ${styles.fontSize1} vs ${styles.fontSize2}`);
            assert(styles.fontWeight1 === styles.fontWeight2, `Font weight mismatch: ${styles.fontWeight1} vs ${styles.fontWeight2}`);
            assert(styles.color1 === styles.color2, `Color mismatch: ${styles.color1} vs ${styles.color2}`);

            // 3. Single Input DOM Contract (2FA-03)
            log(`\n[2FA-03: Single Input DOM Contract]`);
            const dom = await page.evaluate(() => {
                const input = document.querySelector('input[name="code"]');
                const oldBoxes = document.querySelectorAll('.auth-otp-wrapper, .auth-otp-input, #otpHidden');
                const s = window.getComputedStyle(input);
                return {
                    hasSingleInput: !!input,
                    placeholder: input?.getAttribute('placeholder'),
                    direction: s.direction,
                    textAlign: s.textAlign,
                    oldBoxesCount: oldBoxes.length
                };
            });

            log(`  Single code input: ${dom.hasSingleInput ? 'PASS' : 'FAIL'}`);
            log(`  Placeholder: "${dom.placeholder}"`);
            log(`  Direction: ${dom.direction}, Text-Align: ${dom.textAlign}`);
            log(`  Old OTP boxes count: ${dom.oldBoxesCount}`);
            assert(dom.hasSingleInput, 'Single code input not found in DOM');
            assert(dom.placeholder === 'XXXXXX', `Placeholder expected 'XXXXXX', got '${dom.placeholder}'`);
            assert(dom.oldBoxesCount === 0, 'Legacy 6-box OTP inputs found in DOM');

            // 4. Typing, Paste, and Sanitization (2FA-04)
            log(`\n[2FA-04: OTP Typing and Paste Sanitization]`);
            await page.focus('#code');
            await page.type('#code', '012abc345');
            let val = await page.$eval('#code', el => el.value);
            log(`  Typed '012abc345' -> Result: '${val}'`);
            assert(val === '012345', `Typing sanitization failed: expected '012345', got '${val}'`);

            await page.evaluate(() => {
                const el = document.querySelector('#code');
                el.value = '';
                const pasteEvent = new ClipboardEvent('paste', {
                    bubbles: true,
                    cancelable: true,
                    clipboardData: new DataTransfer()
                });
                pasteEvent.clipboardData.setData('text/plain', '0987654321');
                el.dispatchEvent(pasteEvent);
            });
            val = await page.$eval('#code', el => el.value);
            log(`  Pasted '0987654321' -> Result: '${val}'`);
            assert(val === '098765', `Paste sanitization failed: expected '098765', got '${val}'`);

            // 5. Mode Toggle Switch (2FA-07)
            log(`\n[2FA-07: Mode Toggle Switch (TOTP <-> Recovery Code)]`);
            // Desktop 1440 Viewport
            await page.setViewport({ width: 1440, height: 900 });
            await page.click('#toggleRecoveryLink');
            await new Promise(r => setTimeout(r, 200));

            let modeState = await page.evaluate(() => {
                const codeWrapper = document.querySelector('#codeInputsWrapper');
                const recoveryWrapper = document.querySelector('#recoveryWrapper');
                const codeInput = document.querySelector('#code');
                const recoveryInput = document.querySelector('#recovery_code');

                return {
                    codeHidden: codeWrapper?.classList.contains('d-none'),
                    recoveryVisible: !recoveryWrapper?.classList.contains('d-none'),
                    codeDisabled: codeInput?.disabled,
                    recoveryEnabled: !recoveryInput?.disabled,
                    activeElementId: document.activeElement?.id
                };
            });

            log(`  Desktop 1440 Mode Switch to Recovery:`);
            log(`    Code wrapper hidden: ${modeState.codeHidden}`);
            log(`    Recovery wrapper visible: ${modeState.recoveryVisible}`);
            log(`    Code input disabled: ${modeState.codeDisabled}`);
            log(`    Recovery input enabled: ${modeState.recoveryEnabled}`);
            log(`    Active focused element: #${modeState.activeElementId}`);
            assert(modeState.codeHidden, '#codeInputsWrapper must have d-none in recovery mode');
            assert(modeState.recoveryVisible, '#recoveryWrapper must not have d-none in recovery mode');
            assert(modeState.codeDisabled, '#code must be disabled in recovery mode');
            assert(modeState.recoveryEnabled, '#recovery_code must be enabled in recovery mode');

            await page.screenshot({ path: path.join(screenshotsDir, `${panel}_desktop_1440_recovery.png`) });

            // Mobile 320 Viewport in Recovery Mode
            await page.setViewport({ width: 320, height: 740 });
            await page.screenshot({ path: path.join(screenshotsDir, `${panel}_narrow_320_recovery.png`) });

            // Switch back to TOTP mode
            await page.click('#toggleRecoveryLink');
            await new Promise(r => setTimeout(r, 200));

            let backToTotp = await page.evaluate(() => {
                const codeWrapper = document.querySelector('#codeInputsWrapper');
                const recoveryWrapper = document.querySelector('#recoveryWrapper');
                const codeInput = document.querySelector('#code');
                const recoveryInput = document.querySelector('#recovery_code');

                return {
                    codeVisible: !codeWrapper?.classList.contains('d-none'),
                    recoveryHidden: recoveryWrapper?.classList.contains('d-none'),
                    codeEnabled: !codeInput?.disabled,
                    recoveryDisabled: recoveryInput?.disabled,
                    activeElementId: document.activeElement?.id
                };
            });

            log(`  Switch back to TOTP Mode:`);
            log(`    Code wrapper visible: ${backToTotp.codeVisible}`);
            log(`    Recovery wrapper hidden: ${backToTotp.recoveryHidden}`);
            log(`    Code input enabled: ${backToTotp.codeEnabled}`);
            log(`    Recovery input disabled: ${backToTotp.recoveryDisabled}`);
            assert(backToTotp.codeVisible, '#codeInputsWrapper must be visible in TOTP mode');
            assert(backToTotp.recoveryHidden, '#recoveryWrapper must be hidden in TOTP mode');
            assert(backToTotp.codeEnabled, '#code must be enabled in TOTP mode');
            assert(backToTotp.recoveryDisabled, '#recovery_code must be disabled in TOTP mode');

            // 6. Real HTTP Submit & Double-Click Prevention (2FA-01, 2FA-02)
            log(`\n[2FA-01/02: Real HTTP Submission & Double Click Protection]`);
            await page.setViewport({ width: 1440, height: 900 });
            await page.goto(pageUrl, { waitUntil: 'networkidle0' });

            const startSubmitCount = submitCounts[panel];
            await page.focus('#code');
            await page.type('#code', '123456');

            // Rapid double-click on submit button
            log(`  Triggering rapid double-click on submit button...`);
            const submitBtn = await page.$('button[type="submit"]');
            await Promise.all([
                submitBtn.click(),
                submitBtn.click(),
                page.waitForNavigation({ waitUntil: 'networkidle0' })
            ]);

            const currentUrl = page.url();
            const heading = await page.$eval('#dashboardTitle', el => el.textContent.trim());
            const submitsReceived = submitCounts[panel] - startSubmitCount;

            log(`  Navigated to: ${currentUrl}`);
            log(`  Dashboard heading: "${heading}"`);
            log(`  Total challenge POST requests received: ${submitsReceived} (expected exactly 1)`);
            assert(currentUrl.endsWith('/dashboard'), `Expected redirect to /dashboard, got ${currentUrl}`);
            assert(submitsReceived === 1, `Double click allowed ${submitsReceived} requests instead of exactly 1`);

            // 7. Enter Key Real Submission (2FA-02)
            log(`\n[2FA-02: Enter Key Submission via Keyboard]`);
            await page.goto(pageUrl, { waitUntil: 'networkidle0' });
            await page.focus('#code');
            await page.type('#code', '123456');

            log(`  Pressing Enter in #code...`);
            await Promise.all([
                page.keyboard.press('Enter'),
                page.waitForNavigation({ waitUntil: 'networkidle0' })
            ]);

            const enterUrl = page.url();
            log(`  Navigated via Enter to: ${enterUrl}`);
            assert(enterUrl.endsWith('/dashboard'), `Enter key submission failed to reach /dashboard, got ${enterUrl}`);

            // 8. Error Display and Retry Flow (2FA-08)
            log(`\n[2FA-08: Error Feedback and Retry Flow]`);
            await page.goto(pageUrl, { waitUntil: 'networkidle0' });
            await page.focus('#code');
            await page.type('#code', '000000'); // Triggers 422 validation failure

            const submitBtnRetry = await page.$('button[type="submit"]');
            await submitBtnRetry.click();
            await new Promise(r => setTimeout(r, 400));

            const errorState = await page.evaluate(() => {
                const btn = document.querySelector('button[type="submit"]');
                const invalidEl = document.querySelector('.is-invalid, .invalid-feedback:not([hidden])');
                return {
                    buttonDisabled: btn?.disabled,
                    buttonIsLoading: btn?.dataset.buttonIsLoading,
                    hasInvalidFeedback: !!invalidEl
                };
            });

            log(`  After 422 Error:`);
            log(`    Invalid feedback displayed: ${errorState.hasInvalidFeedback}`);
            log(`    Button disabled: ${errorState.buttonDisabled}`);
            log(`    Button isLoading flag: ${errorState.buttonIsLoading}`);
            assert(errorState.buttonDisabled === false, 'Submit button remained disabled after 422 error');

            // Now enter valid code and submit again
            await page.evaluate(() => { document.querySelector('#code').value = ''; });
            await page.focus('#code');
            await page.type('#code', '123456');

            await Promise.all([
                page.click('button[type="submit"]'),
                page.waitForNavigation({ waitUntil: 'networkidle0' })
            ]);

            log(`  Retry succeeded! Final URL: ${page.url()}`);
            assert(page.url().endsWith('/dashboard'), 'Retry failed to navigate to /dashboard');

            await page.close();
        }

        log('\n========================================================================');
        log('ALL BROWSER PROBES PASSED WITH ZERO ASSERTION FAILURES (Exit 0)');
        log('========================================================================');
        process.exitCode = 0;

    } catch (err) {
        log(`\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!`);
        log(`BROWSER SUITE FAILED: ${err.message}`);
        log(err.stack);
        log(`!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!`);
        process.exitCode = 1;
    } finally {
        await browser.close();
        server.close();
        fs.writeFileSync(path.join(__dirname, 'browser-verification-results.txt'), logOutput.join('\n'), 'utf8');
        console.log(`\nVerification log saved to: ${path.join(__dirname, 'browser-verification-results.txt')}`);
    }
}

runSuite();
