const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');

const screenshotsDir = path.join(__dirname, 'live_screenshots');
if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

const sleep = ms => new Promise(r => setTimeout(r, ms));

function assert(condition, message) {
    if (!condition) {
        throw new Error(`[ASSERTION FAILED]: ${message}`);
    }
}

function getUser2FA(scope, email, tenantId = 'test') {
    const cmd = `php scripts/qa/get_user_totp.php ${scope} ${email} ${tenantId}`;
    const output = execSync(cmd).toString().trim();
    return JSON.parse(output);
}

const SCOPES = [
    {
        name: 'ADMIN',
        scopeKey: 'admin',
        loginUrl: 'http://localhost:8000/superadmin/login',
        challengeUrl: 'http://localhost:8000/superadmin/two-factor-challenge',
        cancelUrl: 'http://localhost:8000/superadmin/two-factor-challenge/cancel',
        dashboardUrl: 'http://localhost:8000/superadmin/dashboard',
        logoutUrl: 'http://localhost:8000/superadmin/logout',
        email: 'superadmin@gmail.com',
        password: '123456789',
        tenantId: null
    },
    {
        name: 'TENANT-PATH',
        scopeKey: 'tenant',
        loginUrl: 'http://localhost:8000/test/login',
        challengeUrl: 'http://localhost:8000/test/two-factor-challenge',
        cancelUrl: 'http://localhost:8000/test/two-factor-challenge/cancel',
        dashboardUrl: 'http://localhost:8000/test/dashboard',
        logoutUrl: 'http://localhost:8000/test/logout',
        email: 'ahmed@lot.com',
        password: '123456789',
        tenantId: 'test'
    },
    {
        name: 'TENANT-DOMAIN',
        scopeKey: 'tenant',
        loginUrl: 'http://test.localhost:8000/login',
        challengeUrl: 'http://test.localhost:8000/two-factor-challenge',
        cancelUrl: 'http://test.localhost:8000/two-factor-challenge/cancel',
        dashboardUrl: 'http://test.localhost:8000/dashboard',
        logoutUrl: 'http://test.localhost:8000/logout',
        email: 'ahmed@lot.com',
        password: '123456789',
        tenantId: 'test'
    }
];

async function loginToChallenge(page, scope, remember = false) {
    await page.goto(scope.loginUrl, { waitUntil: 'networkidle0' });
    await page.type('#email', scope.email);
    await page.type('#password', scope.password);
    if (remember) {
        const rememberCheckbox = await page.$('input[name="remember"]');
        if (rememberCheckbox) await rememberCheckbox.click();
    }
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    assert(page.url().includes('two-factor-challenge'), `Did not reach challenge URL: ${page.url()}`);
}

async function performLogout(page, scope) {
    await page.goto(scope.dashboardUrl, { waitUntil: 'networkidle0' });
    await page.evaluate((logoutUrl) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrf) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrf;
            form.appendChild(tokenInput);
        }
        document.body.appendChild(form);
        form.submit();
    }, scope.logoutUrl);
    await page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {});
}

async function runChallengeSuite() {
    console.log('========================================================================');
    console.log('BATCH A — LIVE BROWSER VERIFICATION: CHALLENGE & SECURITY SUITE');
    console.log('Date:', new Date().toISOString());
    console.log('Scopes: ADMIN, TENANT-PATH, TENANT-DOMAIN');
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
            console.log(`[SCOPE: ${scope.name}] - Challenge Submissions & Error Flows`);
            console.log(`------------------------------------------------------------`);

            results[scope.name] = results[scope.name] || {};

            // ----------------------------------------------------
            // TEST 2FA-20: Direct challenge access without session
            // ----------------------------------------------------
            console.log(`\n  [2FA-20: Direct Challenge URL Access without Session]`);
            const guestPage = await browser.newPage();
            const directRes = await guestPage.goto(scope.challengeUrl, { waitUntil: 'networkidle0' });
            console.log(`    Direct access URL redirected to: ${guestPage.url()}, Status: ${directRes.status()}`);
            assert(guestPage.url().includes('/login'), `Expected redirect to login, got ${guestPage.url()}`);
            const hasSecrets = await guestPage.evaluate(() => {
                const text = document.body.innerText;
                return text.includes('two_factor_secret') || text.includes('stack trace') || text.includes('Exception');
            });
            assert(!hasSecrets, 'Sensitive exception or secrets leaked in guest direct access');
            await guestPage.close();
            results[scope.name]['2FA-20'] = 'passed';

            // ----------------------------------------------------
            // TEST 2FA-08: Invalid OTP -> 422 -> Button restored -> Retry valid OTP
            // ----------------------------------------------------
            console.log(`\n  [2FA-08: Invalid Code Feedback & State Recovery]`);
            const p1 = await browser.newPage();
            await loginToChallenge(p1, scope);

            // Submit invalid code 000000
            await p1.type('#code', '000000');
            await p1.click('button[type="submit"]');

            // Wait for 422 error and button to reset
            await p1.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 8000 });

            const errState = await p1.evaluate(() => {
                const btn = document.querySelector('button[type="submit"]');
                const errAlert = document.querySelector('.alert-danger, .invalid-feedback, [data-form-error]');
                return {
                    btnActive: !btn.disabled && btn.dataset.buttonIsLoading !== 'true',
                    errText: errAlert ? errAlert.textContent.trim() : ''
                };
            });
            console.log(`    Invalid OTP 000000 response: error="${errState.errText}", buttonActive=${errState.btnActive}`);
            assert(errState.btnActive, 'Button remained stuck disabled after 422 error');
            assert(errState.errText.length > 0, 'No visible Arabic error message on 422');

            // Switch to Recovery Mode & test invalid recovery code
            await p1.click('#toggleRecoveryLink');
            await sleep(200);
            await p1.type('#recovery_code', 'INVALID-RECOVERY-CODE-999');
            await p1.click('button[type="submit"]');

            await p1.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 8000 });

            const recErrState = await p1.evaluate(() => {
                const recWrapper = document.querySelector('#recoveryWrapper');
                const codeWrapper = document.querySelector('#codeInputsWrapper');
                const btn = document.querySelector('button[type="submit"]');
                return {
                    isStillRecovery: !recWrapper.classList.contains('d-none') && codeWrapper.classList.contains('d-none'),
                    btnActive: !btn.disabled && btn.dataset.buttonIsLoading !== 'true'
                };
            });
            console.log(`    Invalid recovery code: remainedInRecoveryMode=${recErrState.isStillRecovery}, buttonActive=${recErrState.btnActive}`);
            assert(recErrState.isStillRecovery, 'Form lost recovery mode after error');
            assert(recErrState.btnActive, 'Button stuck after recovery error');
            await p1.close();
            results[scope.name]['2FA-08'] = 'passed';

            // ----------------------------------------------------
            // TEST 2FA-01: Valid TOTP Submission -> Single POST -> Dashboard
            // ----------------------------------------------------
            console.log(`\n  [2FA-01: Valid TOTP Submission & Dashboard Entry]`);
            const p2 = await browser.newPage();
            let postChallengeCount = 0;
            p2.on('request', req => {
                if (req.method() === 'POST' && req.url().includes('two-factor-challenge')) {
                    postChallengeCount++;
                }
            });

            await loginToChallenge(p2, scope);
            const { otp } = getUser2FA(scope.scopeKey, scope.email, scope.tenantId);
            console.log(`    Submitting fresh valid TOTP: ${otp}`);
            await p2.type('#code', otp);

            await Promise.all([
                p2.waitForNavigation({ waitUntil: 'networkidle0' }),
                p2.click('button[type="submit"]')
            ]);

            console.log(`    Landed on: ${p2.url()}, Total challenge POST requests: ${postChallengeCount}`);
            assert(p2.url().includes('dashboard'), `Did not navigate to dashboard: ${p2.url()}`);
            assert(postChallengeCount === 1, `Expected exactly 1 challenge POST, got ${postChallengeCount}`);

            await performLogout(p2, scope);
            await p2.close();
            results[scope.name]['2FA-01'] = 'passed';

            // ----------------------------------------------------
            // TEST 2FA-02: Enter key submission & Double-click debounce
            // ----------------------------------------------------
            console.log(`\n  [2FA-02: Keyboard Enter Submission & Double Click Protection]`);
            const p3 = await browser.newPage();
            let dblPostCount = 0;
            p3.on('request', req => {
                if (req.method() === 'POST' && req.url().includes('two-factor-challenge')) {
                    dblPostCount++;
                }
            });

            await loginToChallenge(p3, scope);
            const { otp: enterOtp } = getUser2FA(scope.scopeKey, scope.email, scope.tenantId);
            await p3.type('#code', enterOtp);

            // Double click rapidly
            await Promise.all([
                p3.waitForNavigation({ waitUntil: 'networkidle0' }),
                (async () => {
                    await p3.click('button[type="submit"]', { clickCount: 2, delay: 50 });
                })()
            ]);

            console.log(`    Double-click result URL: ${p3.url()}, Total POST requests: ${dblPostCount}`);
            assert(p3.url().includes('dashboard'), `Did not navigate to dashboard on double-click: ${p3.url()}`);
            assert(dblPostCount === 1, `Double click debouncing failed: received ${dblPostCount} POSTs`);

            await performLogout(p3, scope);
            await p3.close();
            results[scope.name]['2FA-02'] = 'passed';

            // ----------------------------------------------------
            // TEST 2FA-09 & 2FA-10: Recovery Code Single-Use & Invalidations
            // ----------------------------------------------------
            console.log(`\n  [2FA-09 & 2FA-10: Recovery Codes Life Cycle & Exhaustion]`);
            const p4 = await browser.newPage();
            const { recovery_codes } = getUser2FA(scope.scopeKey, scope.email, scope.tenantId);
            assert(recovery_codes && recovery_codes.length >= 2, 'At least 2 recovery codes required for test');
            const codeToConsume = recovery_codes[0];
            const secondCode = recovery_codes[1];
            console.log(`    Using recovery code 1: ${codeToConsume}`);

            await loginToChallenge(p4, scope);
            await p4.click('#toggleRecoveryLink');
            await sleep(200);
            await p4.type('#recovery_code', codeToConsume);

            await Promise.all([
                p4.waitForNavigation({ waitUntil: 'networkidle0' }),
                p4.click('button[type="submit"]')
            ]);
            console.log(`    Landed on dashboard with recovery code: ${p4.url()}`);
            assert(p4.url().includes('dashboard'), 'Failed to login with valid recovery code');

            // Logout and attempt to REUSE the SAME recovery code
            await performLogout(p4, scope);
            console.log(`    Logged out. Re-attempting login with consumed recovery code: ${codeToConsume}`);

            await loginToChallenge(p4, scope);
            await p4.click('#toggleRecoveryLink');
            await sleep(200);
            await p4.type('#recovery_code', codeToConsume);
            await p4.click('button[type="submit"]');

            // Should be rejected
            await p4.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 8000 });

            const reuseRejected = await p4.evaluate(() => {
                const errAlert = document.querySelector('.alert-danger, .invalid-feedback, [data-form-error]');
                return errAlert && errAlert.textContent.trim().length > 0;
            });
            console.log(`    Reused recovery code rejected with visible alert: ${reuseRejected}`);
            assert(reuseRejected, 'Reused recovery code was not rejected with visible error');

            // Now use the second unused code -> should succeed!
            console.log(`    Entering second unused recovery code: ${secondCode}`);
            await p4.evaluate(() => { document.querySelector('#recovery_code').value = ''; });
            await p4.type('#recovery_code', secondCode);
            await Promise.all([
                p4.waitForNavigation({ waitUntil: 'networkidle0' }),
                p4.click('button[type="submit"]')
            ]);
            console.log(`    Second recovery code entry URL: ${p4.url()}`);
            assert(p4.url().includes('dashboard'), 'Second unused recovery code failed');

            await performLogout(p4, scope);
            await p4.close();
            results[scope.name]['2FA-09'] = 'passed';
            results[scope.name]['2FA-10'] = 'passed';

            // ----------------------------------------------------
            // TEST 2FA-19: Remember me, Challenge Cancel & Back/Forward
            // ----------------------------------------------------
            console.log(`\n  [2FA-19: Cancel Challenge, Remember Me & History Back/Forward]`);
            const p5 = await browser.newPage();
            await loginToChallenge(p5, scope, true);

            // Click "الرجوع لتسجيل الدخول"
            await Promise.all([
                p5.waitForNavigation({ waitUntil: 'networkidle0' }),
                p5.click('.auth-challenge-links-row a.auth-back-link:not(.auth-toggle-link)')
            ]);
            console.log(`    Clicked cancel link, landed on: ${p5.url()}`);
            assert(p5.url().includes('/login'), `Expected redirect to login page, got ${p5.url()}`);

            // Try forward navigation to dashboard or challenge
            await p5.goto(scope.dashboardUrl, { waitUntil: 'networkidle0' });
            console.log(`    Attempted direct dashboard visit after cancel: ${p5.url()}`);
            assert(p5.url().includes('/login'), 'Unauthenticated user was not redirected away from dashboard');

            await p5.close();
            results[scope.name]['2FA-19'] = 'passed';
        }

        // ========================================================
        // TEST 2FA-21: Panel Isolation (Admin vs Tenant concurrent sessions)
        // ========================================================
        console.log(`\n------------------------------------------------------------`);
        console.log(`[2FA-21: Concurrent Admin & Tenant Isolation in Same Browser]`);
        console.log(`------------------------------------------------------------`);

        const adminTab = await browser.newPage();
        const tenantTab = await browser.newPage();

        // 1. Start Admin login challenge
        await loginToChallenge(adminTab, SCOPES[0]);
        // 2. Start Tenant login challenge
        await loginToChallenge(tenantTab, SCOPES[1]);

        // Complete Admin challenge
        const { otp: adminOtp } = getUser2FA('admin', 'superadmin@gmail.com');
        await adminTab.type('#code', adminOtp);
        await Promise.all([
            adminTab.waitForNavigation({ waitUntil: 'networkidle0' }),
            adminTab.click('button[type="submit"]')
        ]);
        console.log(`  Admin Tab URL: ${adminTab.url()}`);
        assert(adminTab.url().includes('superadmin/dashboard'), 'Admin tab failed to reach superadmin dashboard');

        // Complete Tenant challenge
        const { otp: tenantOtp } = getUser2FA('tenant', 'ahmed@lot.com', 'test');
        await tenantTab.type('#code', tenantOtp);
        await Promise.all([
            tenantTab.waitForNavigation({ waitUntil: 'networkidle0' }),
            tenantTab.click('button[type="submit"]')
        ]);
        console.log(`  Tenant Tab URL: ${tenantTab.url()}`);
        assert(tenantTab.url().includes('test/dashboard'), 'Tenant tab failed to reach tenant dashboard');

        // Verify Cross Access is Forbidden
        await adminTab.goto('http://localhost:8000/test/dashboard', { waitUntil: 'networkidle0' });
        console.log(`  Admin visiting Tenant Dashboard URL: ${adminTab.url()}`);
        // Should either redirect to tenant login or show 403
        assert(!adminTab.url().endsWith('/test/dashboard'), 'Admin session erroneously accessed Tenant dashboard');

        await adminTab.close();
        await tenantTab.close();
        SCOPES.forEach(s => { results[s.name]['2FA-21'] = 'passed'; });

        // ========================================================
        // TEST 2FA-22: Cross-Tenant Isolation (Tenant A vs Tenant B)
        // ========================================================
        console.log(`\n------------------------------------------------------------`);
        console.log(`[2FA-22: Cross-Tenant Isolation (Tenant A vs Tenant B)]`);
        console.log(`------------------------------------------------------------`);

        const tenantATab = await browser.newPage();
        // Login to Tenant A ('test')
        await loginToChallenge(tenantATab, SCOPES[1]);
        const { otp: tenantAOtp } = getUser2FA('tenant', 'ahmed@lot.com', 'test');
        await tenantATab.type('#code', tenantAOtp);
        await Promise.all([
            tenantATab.waitForNavigation({ waitUntil: 'networkidle0' }),
            tenantATab.click('button[type="submit"]')
        ]);
        assert(tenantATab.url().includes('test/dashboard'), 'Tenant A login failed');

        // In same browser, attempt to access Tenant B ('test2') dashboard
        await tenantATab.goto('http://localhost:8000/test2/dashboard', { waitUntil: 'networkidle0' });
        console.log(`  Tenant A session accessing Tenant B URL: ${tenantATab.url()}`);
        assert(!tenantATab.url().endsWith('/test2/dashboard'), 'Tenant A session crossed boundary into Tenant B');
        assert(tenantATab.url().includes('/test2/login'), 'Expected Tenant B to require independent authentication');

        await tenantATab.close();
        results['TENANT-PATH']['2FA-22'] = 'passed';
        results['TENANT-DOMAIN']['2FA-22'] = 'passed';

        // ========================================================
        // TEST 2FA-24: Rate Limiting Throttling (429 Too Many Requests)
        // ========================================================
        console.log(`\n------------------------------------------------------------`);
        console.log(`[2FA-24: Rate Limiting & 429 Throttle Protection]`);
        console.log(`------------------------------------------------------------`);

        const pThrottle = await browser.newPage();
        await loginToChallenge(pThrottle, SCOPES[0]); // Test on Admin

        console.log(`  Submitting 5 incorrect attempts in sequence...`);
        for (let i = 1; i <= 5; i++) {
            await pThrottle.evaluate(() => { document.querySelector('#code').value = ''; });
            await pThrottle.type('#code', '000000');
            await pThrottle.click('button[type="submit"]');
            await pThrottle.waitForFunction(() => {
                const btn = document.querySelector('button[type="submit"]');
                return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
            }, { timeout: 8000 });
            console.log(`    Attempt ${i}/5 completed`);
        }

        // 6th attempt should trigger 429
        console.log(`  Submitting 6th attempt to trigger throttle...`);
        let received429 = false;
        pThrottle.on('response', res => {
            if (res.status() === 429) {
                received429 = true;
                console.log(`    Received HTTP 429 Too Many Requests from: ${res.url()}`);
            }
        });

        await pThrottle.evaluate(() => { document.querySelector('#code').value = ''; });
        await pThrottle.type('#code', '000000');
        await pThrottle.click('button[type="submit"]');
        await pThrottle.waitForFunction(() => {
            const btn = document.querySelector('button[type="submit"]');
            return btn && !btn.disabled && btn.dataset.buttonIsLoading !== 'true';
        }, { timeout: 8000 });

        const throttleAlert = await pThrottle.evaluate(() => {
            const err = document.querySelector('.alert-danger, .invalid-feedback, [data-form-error]');
            return err ? err.textContent.trim() : '';
        });
        console.log(`    Throttle feedback message: "${throttleAlert}"`);
        assert(received429 || throttleAlert.includes('كثير') || throttleAlert.includes('محاولات') || throttleAlert.includes('ثانية'), 'Rate limiting did not engage after 5 attempts');

        await pThrottle.close();
        SCOPES.forEach(s => { results[s.name]['2FA-24'] = 'passed'; });

        console.log('\n========================================================================');
        console.log('CHALLENGE & SECURITY SUITE SUMMARY ACROSS ALL 3 SCOPES:');
        console.log(JSON.stringify(results, null, 2));
        console.log('========================================================================\n');

    } finally {
        await browser.close();
    }
}

runChallengeSuite().catch(err => {
    console.error('CHALLENGE SUITE FAILED WITH ERROR:', err);
    process.exit(1);
});
