const puppeteer = require('puppeteer');
const { execSync } = require('child_process');

(async () => {
    execSync('php artisan cache:clear');
    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--host-rules=MAP test.localhost 127.0.0.1, MAP test2.localhost 127.0.0.1'
        ]
    });
    const ctx = await browser.createBrowserContext();
    const adminTab = await ctx.newPage();
    const tenantTab = await ctx.newPage();

    adminTab.on('console', msg => console.log('ADMIN CONSOLE:', msg.text()));
    adminTab.on('response', res => {
        if (res.status() >= 300) console.log('ADMIN HTTP:', res.status(), res.url());
    });

    tenantTab.on('console', msg => console.log('TENANT CONSOLE:', msg.text()));
    tenantTab.on('response', res => {
        if (res.status() >= 300) console.log('TENANT HTTP:', res.status(), res.url());
    });

    // 1. Admin login to challenge
    await adminTab.goto('http://localhost:8000/superadmin/login', { waitUntil: 'load' });
    await adminTab.type('#email', 'superadmin@gmail.com');
    await adminTab.type('#password', '123456789');
    await Promise.all([
        adminTab.waitForNavigation({ waitUntil: 'load' }),
        adminTab.click('button[type="submit"]')
    ]);
    console.log('Admin URL:', adminTab.url());

    // 2. Tenant login
    await tenantTab.goto('http://localhost:8000/test/login', { waitUntil: 'networkidle0' });
    console.log('Tenant initial URL:', tenantTab.url());
    await tenantTab.type('#email', 'ahmed@lot.com');
    await tenantTab.type('#password', '123456789');

    // Click submit and check what happens
    await tenantTab.click('button[type="submit"]');
    await new Promise(r => setTimeout(r, 3000));
    console.log('Tenant URL after click:', tenantTab.url());
    const err = await tenantTab.evaluate(() => {
        const el = document.querySelector('.alert-danger, .invalid-feedback, [data-form-error]');
        return el ? el.textContent.trim() : null;
    });
    console.log('Tenant error after click:', err);

    await ctx.close();
    await browser.close();
})();
