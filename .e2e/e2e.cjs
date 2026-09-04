const puppeteer = require('puppeteer');

const BASE = 'http://127.0.0.1:8012';
const results = [];
function check(name, cond, extra = '') {
    results.push({ name, ok: !!cond, extra });
    console.log((cond ? 'PASS ' : 'FAIL ') + name + (extra ? ' :: ' + extra : ''));
}

(async () => {
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    const page = await browser.newPage();
    const consoleErrors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', (err) => consoleErrors.push('PAGEERROR: ' + err.message));
    page.on('requestfailed', (req) => consoleErrors.push('REQFAIL: ' + req.url() + ' ' + (req.failure() && req.failure().errorText)));

    // Login
    await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    await page.type('input[name="email"]', 'e2e@example.com');
    await page.type('input[name="password"]', 'secret123');
    await page.click('button[type="submit"]');
    await page.waitForFunction(() => location.pathname !== '/login', { timeout: 10000 }).catch(() => {});

    // Step 1: pump grid at desktop
    await page.setViewport({ width: 1024, height: 900 });
    await page.goto(BASE + '/accounts?type=fuel_station', { waitUntil: 'networkidle0' });
    await page.waitForSelector('[data-pump-card]', { timeout: 10000 });
    const cardCount = await page.$$eval('[data-pump-card]', (els) => els.length);
    check('pump grid shows all 24 cards on load', cardCount === 24, 'count=' + cardCount);

    const headerText = await page.evaluate(() => document.body.innerText);
    check('header shows 24 accounts', /24 accounts/.test(headerText), headerText.slice(0, 60));

    // Click the E2E account
    const clicked = await page.evaluate(() => {
        const btn = [...document.querySelectorAll('[data-pump-card]')].find(b => b.dataset.pumpName === 'E2E Pump Verification');
        if (!btn) return false;
        btn.click();
        return true;
    });
    check('found E2E pump card to click', clicked);

    // Wait for fragment (balance + branch + table)
    await page.waitForFunction(() => {
        const c = document.querySelector('[x-ref="fragmentContainer"]') || document.querySelector('.ledger-fragment');
        const el = document.querySelector('[data-pump-id]') ? document.querySelector('div[x-show="selectedId"]') : null;
        // find the detail container by the back link text
        const back = [...document.querySelectorAll('button')].find(b => /Back to all pumps/.test(b.textContent));
        if (!back) return false;
        const container = back.closest('div[x-show]');
        const frag = container ? container.querySelector('div') : null;
        return frag && /Current Balance/.test(container.textContent) && !/Failed to load/.test(container.textContent);
    }, { timeout: 10000 }).catch(() => {});

    const detail = await page.evaluate(() => {
        const back = [...document.querySelectorAll('button')].find(b => /Back to all pumps/.test(b.textContent));
        const container = back.closest('div[x-show]');
        return {
            text: container.textContent,
            hasBranch: !!container.querySelector('#pump-branch-select'),
            rows: container.querySelectorAll('table tbody tr').length,
            hasNext: [...container.querySelectorAll('a')].some(a => /Next/.test(a.textContent)),
            hasBack: true,
        };
    });
    check('detail shows balance summary (Current Balance)', /Current Balance/.test(detail.text));
    check('detail shows branch dropdown (multiple branches)', detail.hasBranch);
    check('detail shows transaction rows', detail.rows > 0, 'rows=' + detail.rows);
    check('detail paginates (Next present, >12 txns)', detail.hasNext);

    // Dark mode (click the real header theme toggle button)
    const toggleMode = await page.evaluate(() => {
        const btn = [...document.querySelectorAll('button')].find(b => b.querySelector('svg') && /M12 3v1m0 16v1m9-9h-1M4 12H3/.test(b.innerHTML));
        if (btn) { btn.click(); return 'btn'; }
        if (window.Alpine && window.Alpine.store('theme')) { window.Alpine.store('theme').toggle(); return 'store'; }
        return 'none';
    });
    await new Promise(r => setTimeout(r, 300));
    const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
    check('dark theme toggles', isDark, 'mode=' + toggleMode);
    const cardCountDark = await page.$$eval('[data-pump-card]', (els) => els.length);
    check('cards still present in dark mode', cardCountDark === 24, 'count=' + cardCountDark);
    await page.evaluate(() => window.Alpine.store('theme').toggle());

    // Responsive widths
    for (const w of [768, 375]) {
        await page.setViewport({ width: w, height: 900 });
        await page.goto(BASE + '/accounts?type=fuel_station', { waitUntil: 'networkidle0' });
        await page.waitForSelector('[data-pump-card]', { timeout: 10000 });
        const c = await page.$$eval('[data-pump-card]', (els) => els.length);
        check('pump grid visible at ' + w + 'px width', c === 24, 'count=' + c);
    }

    check('NO console errors during session', consoleErrors.length === 0, consoleErrors.slice(0, 5).join(' | '));

    await browser.close();
    const failed = results.filter(r => !r.ok);
    console.log('\n==== ' + (failed.length ? 'FAILED (' + failed.length + ')' : 'ALL PASSED') + ' ====');
    process.exit(failed.length ? 1 : 0);
})().catch((e) => { console.error('E2E CRASH', e); process.exit(2); });
