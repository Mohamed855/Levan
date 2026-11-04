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

// Simple HTTP server to serve built assets and rendered HTML
const server = http.createServer((req, res) => {
    let filePath = '';
    const cleanUrl = req.url.split('?')[0];

    if (cleanUrl === '/admin-challenge') {
        let content = fs.readFileSync(adminHtmlPath, 'utf8');
        content = content.replaceAll('http://localhost/', `http://localhost:${PORT}/`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    if (cleanUrl === '/tenant-challenge') {
        let content = fs.readFileSync(tenantHtmlPath, 'utf8');
        content = content.replaceAll('http://localhost/', `http://localhost:${PORT}/`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    // Serve static files from public/
    filePath = path.join(publicDir, cleanUrl.replace(/^\//, ''));
    if (!fs.existsSync(filePath) || !fs.statSync(filePath).isFile()) {
        console.log(`404 Not Found: req.url=${req.url} -> filePath=${filePath}`);
    }
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

    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not Found');
});

const PORT = 8199;

const screenSizes = [
    { name: 'desktop-1440', width: 1440, height: 900 },
    { name: 'laptop-1024', width: 1024, height: 768 },
    { name: 'tablet-768', width: 768, height: 1024 },
    { name: 'mobile-390', width: 390, height: 844 },
    { name: 'mobile-360', width: 360, height: 800 },
    { name: 'narrow-320', width: 320, height: 740 }
];

async function runProbes() {
    server.listen(PORT);
    console.log(`Probe server running at http://localhost:${PORT}`);

    const logOutput = [];
    const log = (msg) => {
        console.log(msg);
        logOutput.push(msg);
    };

    log('================================================================');
    log('Puppeteer Browser Verification Suite for Batch A (2FA-01..08)');
    log(`Date: ${new Date().toISOString()}`);
    log('================================================================\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        for (const panel of ['admin', 'tenant']) {
            const pageUrl = `http://localhost:${PORT}/${panel}-challenge`;
            log(`\n------------------------------------------------------------`);
            log(`Testing Panel: ${panel.toUpperCase()} (${pageUrl})`);
            log(`------------------------------------------------------------`);

            const page = await browser.newPage();
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    console.log(`[Browser Console Error]: ${msg.text()}`);
                }
            });
            page.on('pageerror', err => {
                console.log(`[Browser Page Error]: ${err.message}`);
            });

            // 1. Viewport & Layout Matrix Check
            for (const size of screenSizes) {
                await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
                await page.goto(pageUrl, { waitUntil: 'networkidle0' });

                // Check links row layout in RTL
                const layoutCheck = await page.evaluate(() => {
                    const backLink = document.querySelector('.auth-challenge-links-row a.auth-back-link:first-child');
                    const toggleLink = document.querySelector('#toggleRecoveryLink');
                    if (!backLink || !toggleLink) return { error: 'Links not found' };

                    const rect1 = backLink.getBoundingClientRect();
                    const rect2 = toggleLink.getBoundingClientRect();

                    // In RTL, the first child (back to login) should have larger X than the second child (toggle recovery)
                    const isRtlOrder = rect1.right >= rect2.left;
                    // Check if they are on the same line (top coordinates are close)
                    const onSameLine = Math.abs(rect1.top - rect2.top) < 25;

                    return {
                        backLink: { x: rect1.x, y: rect1.top, width: rect1.width, height: rect1.height, text: backLink.textContent.trim() },
                        toggleLink: { x: rect2.x, y: rect2.top, width: rect2.width, height: rect2.height, text: toggleLink.textContent.trim() },
                        isRtlOrder,
                        onSameLine
                    };
                });

                log(`[${panel.toUpperCase()} | ${size.name} (${size.width}x${size.height})]`);
                log(`  Back Link: x=${layoutCheck.backLink?.x.toFixed(1)}, y=${layoutCheck.backLink?.y.toFixed(1)}, width=${layoutCheck.backLink?.width.toFixed(1)}`);
                log(`  Toggle Link: x=${layoutCheck.toggleLink?.x.toFixed(1)}, y=${layoutCheck.toggleLink?.y.toFixed(1)}, width=${layoutCheck.toggleLink?.width.toFixed(1)}`);
                log(`  RTL Alignment: ${layoutCheck.isRtlOrder ? 'PASS (Back link is right of toggle link)' : 'FAIL'}`);
                log(`  Side-by-side (same row): ${layoutCheck.onSameLine ? 'PASS (No separate row wrapping)' : 'FAIL'}`);

                // Save screenshots for desktop and mobile sizes
                if (size.name === 'desktop-1440' || size.name === 'mobile-360' || size.name === 'narrow-320') {
                    const ssPath = path.join(screenshotsDir, `${panel}_${size.name}_totp.png`);
                    await page.screenshot({ path: ssPath, fullPage: true });
                    log(`  Screenshot saved: ${ssPath}`);
                }
            }

            // 2. Computed Typography Comparison (2FA-06)
            log(`\n[2FA-06: Typography & Style Parity on ${panel.toUpperCase()}]`);
            const styleParity = await page.evaluate(() => {
                const backLink = document.querySelector('.auth-challenge-links-row a.auth-back-link:first-child');
                const toggleLink = document.querySelector('#toggleRecoveryLink');

                const s1 = window.getComputedStyle(backLink);
                const s2 = window.getComputedStyle(toggleLink);

                return {
                    fontSizeMatch: s1.fontSize === s2.fontSize,
                    fontWeightMatch: s1.fontWeight === s2.fontWeight,
                    colorMatch: s1.color === s2.color,
                    lineHeightMatch: s1.lineHeight === s2.lineHeight,
                    backLinkStyle: {
                        fontFamily: s1.fontFamily,
                        fontSize: s1.fontSize,
                        fontWeight: s1.fontWeight,
                        color: s1.color,
                        lineHeight: s1.lineHeight,
                        textDecoration: s1.textDecorationLine
                    },
                    toggleLinkStyle: {
                        fontFamily: s2.fontFamily,
                        fontSize: s2.fontSize,
                        fontWeight: s2.fontWeight,
                        color: s2.color,
                        lineHeight: s2.lineHeight,
                        textDecoration: s2.textDecorationLine
                    }
                };
            });

            log(`  Back Link Styles: size=${styleParity.backLinkStyle.fontSize}, weight=${styleParity.backLinkStyle.fontWeight}, color=${styleParity.backLinkStyle.color}, lineHeight=${styleParity.backLinkStyle.lineHeight}`);
            log(`  Toggle Link Styles: size=${styleParity.toggleLinkStyle.fontSize}, weight=${styleParity.toggleLinkStyle.fontWeight}, color=${styleParity.toggleLinkStyle.color}, lineHeight=${styleParity.toggleLinkStyle.lineHeight}`);
            log(`  Font-size match: ${styleParity.fontSizeMatch ? 'PASS' : 'FAIL'}`);
            log(`  Font-weight match: ${styleParity.fontWeightMatch ? 'PASS' : 'FAIL'}`);
            log(`  Color match: ${styleParity.colorMatch ? 'PASS' : 'FAIL'}`);

            // 3. 2FA-03: Single OTP input DOM & placeholder inspection
            log(`\n[2FA-03: Single Input DOM & Placeholder on ${panel.toUpperCase()}]`);
            const domContract = await page.evaluate(() => {
                const codeInput = document.querySelector('input[name="code"]');
                const oldOtpBoxes = document.querySelectorAll('.auth-otp-wrapper, .auth-otp-input, #otpHidden');

                return {
                    hasSingleCodeInput: !!codeInput,
                    inputId: codeInput?.id,
                    inputName: codeInput?.getAttribute('name'),
                    placeholder: codeInput?.getAttribute('placeholder'),
                    dir: window.getComputedStyle(codeInput).direction,
                    textAlign: window.getComputedStyle(codeInput).textAlign,
                    oldBoxesCount: oldOtpBoxes.length
                };
            });
            log(`  Single code input exists: ${domContract.hasSingleCodeInput ? 'PASS' : 'FAIL'}`);
            log(`  Name attribute: ${domContract.inputName}`);
            log(`  Placeholder: "${domContract.placeholder}"`);
            log(`  Text alignment: ${domContract.textAlign}`);
            log(`  Direction: ${domContract.dir}`);
            log(`  Old 6-box elements in DOM: ${domContract.oldBoxesCount} (expected 0)`);

            // 4. 2FA-04: Typing, Pasting with leading zero, regex filter
            log(`\n[2FA-04: OTP Typing, Paste, Sanitization on ${panel.toUpperCase()}]`);
            await page.focus('#code');
            await page.type('#code', '012abc345');
            let typedVal = await page.$eval('#code', el => el.value);
            log(`  Typed '012abc345' -> Result: '${typedVal}' (Digits filtered & 6-char max: ${typedVal === '012345' ? 'PASS' : 'FAIL'})`);

            // Clear and paste code starting with zero
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
            let pastedVal = await page.$eval('#code', el => el.value);
            log(`  Pasted '0987654321' -> Result: '${pastedVal}' (Preserved leading zero & sliced to 6: ${pastedVal === '098765' ? 'PASS' : 'FAIL'})`);

            // 5. 2FA-07: Mode Toggle Switch (TOTP <-> Recovery Code)
            log(`\n[2FA-07: Mode Toggle Switch on ${panel.toUpperCase()}]`);
            // Click to switch to Recovery Code
            await page.click('#toggleRecoveryLink');
            await new Promise(r => setTimeout(r, 200));

            const recoveryState = await page.evaluate(() => {
                const codeGroup = document.querySelector('#codeGroup');
                const recoveryGroup = document.querySelector('#recoveryGroup');
                const codeInput = document.querySelector('#code');
                const recoveryInput = document.querySelector('#recovery_code');
                const toggleText = document.querySelector('#toggleRecoveryText')?.textContent.trim();
                const focusedElement = document.activeElement?.id;

                return {
                    codeGroupHidden: codeGroup?.classList.contains('d-none'),
                    recoveryGroupVisible: !recoveryGroup?.classList.contains('d-none'),
                    codeInputDisabled: codeInput?.disabled,
                    recoveryInputEnabled: !recoveryInput?.disabled,
                    toggleText,
                    focusedElement
                };
            });

            log(`  Switched to Recovery Mode:`);
            log(`    Code group hidden: ${recoveryState.codeGroupHidden}`);
            log(`    Recovery group visible: ${recoveryState.recoveryGroupVisible}`);
            log(`    Code input disabled: ${recoveryState.codeInputDisabled}`);
            log(`    Recovery input enabled: ${recoveryState.recoveryInputEnabled}`);
            log(`    Toggle link text: "${recoveryState.toggleText}"`);
            log(`    Active focused element: #${recoveryState.focusedElement}`);

            // Take screenshot in recovery mode
            const ssRecovery = path.join(screenshotsDir, `${panel}_desktop_recovery.png`);
            await page.screenshot({ path: ssRecovery, fullPage: true });
            log(`    Screenshot saved: ${ssRecovery}`);

            // Click again to switch back to TOTP
            await page.click('#toggleRecoveryLink');
            await new Promise(r => setTimeout(r, 200));

            const totpReturnedState = await page.evaluate(() => {
                const codeInput = document.querySelector('#code');
                const recoveryInput = document.querySelector('#recovery_code');
                const toggleText = document.querySelector('#toggleRecoveryText')?.textContent.trim();
                const focusedElement = document.activeElement?.id;

                return {
                    codeInputEnabled: !codeInput?.disabled,
                    recoveryInputDisabled: recoveryInput?.disabled,
                    toggleText,
                    focusedElement
                };
            });

            log(`  Switched back to TOTP Mode:`);
            log(`    Code input re-enabled: ${totpReturnedState.codeInputEnabled}`);
            log(`    Recovery input disabled: ${totpReturnedState.recoveryInputDisabled}`);
            log(`    Toggle link text: "${totpReturnedState.toggleText}"`);
            log(`    Active focused element: #${totpReturnedState.focusedElement}`);

            // 6. 2FA-02: Enter key submission triggering form submit
            log(`\n[2FA-02: Enter Key Submission on ${panel.toUpperCase()}]`);
            const enterSubmitted = await page.evaluate(() => {
                return new Promise((resolve) => {
                    const timer = setTimeout(() => resolve(false), 3000);
                    const form = document.querySelector('form');
                    form.addEventListener('submit', (e) => {
                        clearTimeout(timer);
                        e.preventDefault();
                        resolve(true);
                    }, { once: true });

                    const codeInput = document.querySelector('#code');
                    codeInput.focus();
                    codeInput.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
                });
            });
            log(`  Enter key in #code triggers form submit: ${enterSubmitted ? 'PASS' : 'FAIL'}`);

            await page.close();
        }

        log('\n================================================================');
        log('ALL BROWSER PROBES COMPLETED SUCCESSFULLY');
        log('================================================================');

    } catch (err) {
        log(`\nERROR during browser verification: ${err.message}\n${err.stack}`);
    } finally {
        await browser.close();
        server.close();
        fs.writeFileSync(path.join(__dirname, 'browser-verification-results.txt'), logOutput.join('\n'), 'utf8');
        console.log(`\nVerification log saved to: ${path.join(__dirname, 'browser-verification-results.txt')}`);
    }
}

runProbes();
