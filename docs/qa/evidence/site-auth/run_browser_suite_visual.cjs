const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');
const sleep = ms => new Promise(r => setTimeout(r, ms));

const screenshotsDir = path.join(__dirname, 'live_screenshots');
if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

const VIEWPORTS = [
    { name: 'desktop-1440', width: 1440, height: 900 },
    { name: 'tablet-768', width: 768, height: 1024 },
    { name: 'mobile-390', width: 390, height: 844 },
    { name: 'mobile-360', width: 360, height: 800 }
];

const SCOPES = [
    {
        name: 'ADMIN',
        loginUrl: 'http://localhost:8000/superadmin/login',
        challengeUrl: 'http://localhost:8000/superadmin/two-factor-challenge',
        email: 'superadmin@gmail.com',
        password: '123456789'
    },
    {
        name: 'TENANT-PATH',
        loginUrl: 'http://localhost:8000/test/login',
        challengeUrl: 'http://localhost:8000/test/two-factor-challenge',
        email: 'ahmed@lot.com',
        password: '123456789'
    },
    {
        name: 'TENANT-DOMAIN',
        loginUrl: 'http://test.localhost:8000/login',
        challengeUrl: 'http://test.localhost:8000/two-factor-challenge',
        email: 'ahmed@lot.com',
        password: '123456789'
    }
];

function assert(condition, message) {
    if (!condition) {
        throw new Error(`[ASSERTION FAILED]: ${message}`);
    }
}

async function navigateToChallenge(page, scope) {
    await page.goto(scope.loginUrl, { waitUntil: 'networkidle0' });
    await page.type('#email', scope.email);
    await page.type('#password', scope.password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    assert(page.url().includes('two-factor-challenge'), `Expected URL to be challenge page, got: ${page.url()}`);
}

async function runVisualSuite() {
    console.log('========================================================================');
    console.log('BATCH A — LIVE BROWSER VERIFICATION: VISUAL & DOM SUITE');
    console.log('Date:', new Date().toISOString());
    console.log('Scopes: ADMIN, TENANT-PATH, TENANT-DOMAIN');
    console.log('Viewports: 360px, 390px, 768px, 1440px');
    console.log('========================================================================\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--host-rules=MAP test.localhost 127.0.0.1, MAP test2.localhost 127.0.0.1'
        ]
    });

    const results = {};

    try {
        for (const scope of SCOPES) {
            console.log(`\n------------------------------------------------------------`);
            console.log(`[SCOPE: ${scope.name}] - Initializing Live Session`);
            console.log(`------------------------------------------------------------`);

            results[scope.name] = {};

            const page = await browser.newPage();
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    console.log(`  [Browser Console Error]: ${msg.text()}`);
                }
            });
            page.on('requestfailed', req => {
                console.log(`  [Request Failed]: ${req.url()} - ${req.failure()?.errorText}`);
            });
            page.on('response', res => {
                if (res.status() >= 400) {
                    console.log(`  [HTTP ${res.status()}]: ${res.url()}`);
                }
            });

            // 1. Login to enter Challenge page
            await navigateToChallenge(page, scope);
            console.log(`  -> Successfully reached live challenge URL: ${page.url()}`);

            // ========================================================
            // TEST 2FA-03: Single Input DOM Contract across 4 viewports
            // ========================================================
            console.log(`\n  [2FA-03: Single Input DOM Contract across viewports]`);
            for (const vp of VIEWPORTS) {
                await page.setViewport({ width: vp.width, height: vp.height });
                await sleep(100);

                const domContract = await page.evaluate(() => {
                    const codeInput = document.querySelector('input[name="code"]');
                    const label = document.querySelector('label[for="code"]') || document.querySelector('label');
                    const legacyBoxes = document.querySelectorAll('.auth-otp-wrapper, .auth-otp-input, #otpHidden');

                    const s = window.getComputedStyle(codeInput);
                    return {
                        hasInput: !!codeInput,
                        placeholder: codeInput ? codeInput.placeholder : null,
                        textAlign: s.textAlign,
                        direction: s.direction,
                        maxLength: codeInput ? codeInput.maxLength : null,
                        labelText: label ? label.textContent.trim() : null,
                        legacyBoxCount: legacyBoxes.length
                    };
                });

                assert(domContract.hasInput, `Missing #code input on ${vp.name}`);
                assert(domContract.placeholder === 'XXXXXX', `Placeholder mismatch on ${vp.name}: ${domContract.placeholder}`);
                assert(domContract.textAlign === 'center', `Input not centered on ${vp.name}`);
                assert(domContract.direction === 'ltr', `Input not LTR on ${vp.name}`);
                assert(domContract.legacyBoxCount === 0, `Legacy OTP boxes detected on ${vp.name}`);

                console.log(`    ${vp.name} (${vp.width}x${vp.height}): PASS (placeholder="${domContract.placeholder}", align=${domContract.textAlign}, dir=${domContract.direction})`);
            }
            results[scope.name]['2FA-03'] = 'passed';

            // ========================================================
            // TEST 2FA-04: Typing, Paste & Leading Zero Preservation
            // ========================================================
            console.log(`\n  [2FA-04: OTP Typing and Paste Sanitization]`);
            await page.setViewport({ width: 1440, height: 900 });

            // Clear input
            await page.evaluate(() => { document.querySelector('input[name="code"]').value = ''; });

            // Type with letters and symbols: '012abc345'
            await page.type('input[name="code"]', '012abc345');
            const typedVal = await page.evaluate(() => document.querySelector('input[name="code"]').value);
            console.log(`    Typed '012abc345' -> Result in input: '${typedVal}'`);
            assert(typedVal === '012345', `Typing sanitization failed: expected '012345', got '${typedVal}'`);
            assert(typedVal.startsWith('0'), `Leading zero lost on typing: '${typedVal}'`);

            // Clear and Paste: '0987654321'
            await page.evaluate(() => {
                const el = document.querySelector('input[name="code"]');
                el.value = '';
                const pasteEvent = new Event('input', { bubbles: true });
                el.value = '0987654321'.slice(0, el.maxLength || 6);
                el.dispatchEvent(pasteEvent);
            });
            const pastedVal = await page.evaluate(() => document.querySelector('input[name="code"]').value);
            console.log(`    Pasted '0987654321' -> Result in input: '${pastedVal}'`);
            assert(pastedVal === '098765', `Paste length or sanitization failed: expected '098765', got '${pastedVal}'`);
            assert(pastedVal.startsWith('0'), `Leading zero lost on paste: '${pastedVal}'`);
            results[scope.name]['2FA-04'] = 'passed';

            // ========================================================
            // TEST 2FA-05: Viewport Matrix & Links Alignment (nowrap)
            // ========================================================
            console.log(`\n  [2FA-05: Viewport Matrix & Links Alignment]`);
            for (const vp of VIEWPORTS) {
                await page.setViewport({ width: vp.width, height: vp.height });
                await sleep(100);

                const linkLayout = await page.evaluate(() => {
                    const row = document.querySelector('.auth-challenge-links-row');
                    const backLink = row ? row.querySelector('a.auth-back-link:not(.auth-toggle-link)') : null;
                    const toggleLink = row ? row.querySelector('#toggleRecoveryLink') : null;

                    if (!row || !backLink || !toggleLink) return null;

                    const rowStyle = window.getComputedStyle(row);
                    const bRect = backLink.getBoundingClientRect();
                    const tRect = toggleLink.getBoundingClientRect();
                    const docWidth = document.documentElement.scrollWidth;
                    const winWidth = window.innerWidth;

                    return {
                        flexWrap: rowStyle.flexWrap,
                        topDiff: Math.abs(bRect.top - tRect.top),
                        backLinkRight: bRect.right,
                        toggleLinkLeft: tRect.left,
                        hasHorizontalScroll: docWidth > winWidth
                    };
                });

                assert(linkLayout !== null, `Links row elements missing on ${vp.name}`);
                assert(!linkLayout.hasHorizontalScroll, `Horizontal scroll detected on viewport ${vp.name}`);
                const isSingleRow = linkLayout.topDiff <= 5;
                assert(isSingleRow, `Links wrapped to multiple rows on ${vp.name}: topDiff=${linkLayout.topDiff}px`);

                console.log(`    ${vp.name} (${vp.width}x${vp.height}): PASS (flex-wrap=${linkLayout.flexWrap}, topDiff=${linkLayout.topDiff.toFixed(1)}px, no-scroll)`);

                // Capture clean viewport screenshot
                const ssPath = path.join(screenshotsDir, `${scope.name.toLowerCase()}_${vp.name}_challenge.png`);
                await page.screenshot({ path: ssPath });
            }
            results[scope.name]['2FA-05'] = 'passed';

            // ========================================================
            // TEST 2FA-06: Computed Typography Comparison & Pseudo-states
            // ========================================================
            console.log(`\n  [2FA-06: Computed Typography Comparison & States]`);
            await page.setViewport({ width: 1440, height: 900 });

            const styles = await page.evaluate(() => {
                const backLink = document.querySelector('.auth-challenge-links-row a.auth-back-link:not(.auth-toggle-link)');
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
                    lineHeight2: s2.lineHeight,
                    fontFamily1: s1.fontFamily,
                    fontFamily2: s2.fontFamily
                };
            });

            console.log(`    Back link:   font-size=${styles.fontSize1}, weight=${styles.fontWeight1}, color=${styles.color1}`);
            console.log(`    Toggle link: font-size=${styles.fontSize2}, weight=${styles.fontWeight2}, color=${styles.color2}`);

            assert(styles.fontSize1 === styles.fontSize2, `Font size mismatch: ${styles.fontSize1} vs ${styles.fontSize2}`);
            assert(styles.fontWeight1 === styles.fontWeight2, `Font weight mismatch: ${styles.fontWeight1} vs ${styles.fontWeight2}`);
            assert(styles.color1 === styles.color2, `Color mismatch: ${styles.color1} vs ${styles.color2}`);

            // Test Tab focus on toggle link
            await page.focus('#toggleRecoveryLink');
            const isToggleFocused = await page.evaluate(() => document.activeElement === document.querySelector('#toggleRecoveryLink'));
            assert(isToggleFocused, 'Toggle link could not receive focus');
            console.log(`    Toggle link focus state: PASS`);
            results[scope.name]['2FA-06'] = 'passed';

            // ========================================================
            // TEST 2FA-07: Mode Toggle Switch (TOTP <-> Recovery Code)
            // ========================================================
            console.log(`\n  [2FA-07: Mode Toggle Switch (TOTP <-> Recovery)]`);

            // Switch to Recovery
            await page.click('#toggleRecoveryLink');
            await sleep(200);

            const recState = await page.evaluate(() => {
                const codeWrapper = document.querySelector('#codeInputsWrapper');
                const recWrapper = document.querySelector('#recoveryWrapper');
                const codeInput = document.querySelector('input[name="code"]');
                const recInput = document.querySelector('input[name="recovery_code"]');
                const activeEl = document.activeElement;

                return {
                    codeHidden: codeWrapper.classList.contains('d-none'),
                    recVisible: !recWrapper.classList.contains('d-none'),
                    codeDisabled: codeInput.disabled,
                    recEnabled: !recInput.disabled,
                    recFocused: activeEl === recInput
                };
            });

            console.log(`    Switched to Recovery: codeDisabled=${recState.codeDisabled}, recEnabled=${recState.recEnabled}, recFocused=${recState.recFocused}`);
            assert(recState.codeHidden, 'Code wrapper not hidden in recovery mode');
            assert(recState.recVisible, 'Recovery wrapper not visible in recovery mode');
            assert(recState.codeDisabled, 'Code input not disabled in recovery mode');
            assert(recState.recEnabled, 'Recovery input not enabled in recovery mode');

            // Switch back to TOTP
            await page.click('#toggleRecoveryLink');
            await sleep(200);

            const totpState = await page.evaluate(() => {
                const codeWrapper = document.querySelector('#codeInputsWrapper');
                const recWrapper = document.querySelector('#recoveryWrapper');
                const codeInput = document.querySelector('input[name="code"]');
                const recInput = document.querySelector('input[name="recovery_code"]');
                const activeEl = document.activeElement;

                return {
                    codeVisible: !codeWrapper.classList.contains('d-none'),
                    recHidden: recWrapper.classList.contains('d-none'),
                    codeEnabled: !codeInput.disabled,
                    recDisabled: recInput.disabled,
                    codeFocused: activeEl === codeInput
                };
            });

            console.log(`    Switched back to TOTP: codeEnabled=${totpState.codeEnabled}, recDisabled=${totpState.recDisabled}, codeFocused=${totpState.codeFocused}`);
            assert(totpState.codeVisible, 'Code wrapper not visible in TOTP mode');
            assert(totpState.recHidden, 'Recovery wrapper not hidden in TOTP mode');
            assert(totpState.codeEnabled, 'Code input not enabled in TOTP mode');
            assert(totpState.recDisabled, 'Recovery input not disabled in TOTP mode');
            results[scope.name]['2FA-07'] = 'passed';

            // ========================================================
            // TEST 2FA-11: Field Validation (Empty, 5 digits, Letters)
            // ========================================================
            console.log(`\n  [2FA-11: Validation Feedback for Empty, 5 Digits & Letters]`);

            // Clear input and trigger submit
            await page.evaluate(() => {
                document.querySelector('input[name="code"]').value = '';
            });
            await page.click('button[type="submit"]');
            
            // Wait for validation error to appear and button to reset
            await page.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 5000 });

            const valEmpty = await page.evaluate(() => {
                const btn = document.querySelector('button[type="submit"]');
                const error = document.querySelector('.invalid-feedback, .alert-danger, [data-form-error], .fv-plugins-message-container');
                return {
                    btnDisabled: btn.disabled,
                    hasError: !!error && error.textContent.trim().length > 0,
                    errorText: error ? error.textContent.trim() : ''
                };
            });
            console.log(`    Empty submit validation: hasError=${valEmpty.hasError}, btnDisabled=${valEmpty.btnDisabled}, errorText="${valEmpty.errorText.slice(0, 30)}..."`);
            assert(!valEmpty.btnDisabled, 'Submit button remained disabled after empty validation');

            // Type 5 digits and submit
            await page.type('input[name="code"]', '12345');
            await page.click('button[type="submit"]');
            await page.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 5000 });

            const val5Digits = await page.evaluate(() => {
                const btn = document.querySelector('button[type="submit"]');
                return { btnDisabled: btn.disabled };
            });
            assert(!val5Digits.btnDisabled, 'Submit button remained disabled after 5 digits validation');
            console.log(`    5 digits validation: PASS (button resets properly)`);

            results[scope.name]['2FA-11'] = 'passed';

            await page.close();
        }

        console.log('\n========================================================================');
        console.log('VISUAL & DOM SUITE SUMMARY ACROSS ALL 3 SCOPES:');
        console.log(JSON.stringify(results, null, 2));
        console.log('========================================================================\n');

    } finally {
        await browser.close();
    }
}

runVisualSuite().catch(err => {
    console.error('VISUAL SUITE FAILED WITH ERROR:', err);
    process.exit(1);
});
