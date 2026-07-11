/**
 * Browser smoke test: first click on /financial-reports report cards should navigate.
 *
 * Usage:
 *   php artisan serve --port=8099
 *   node scripts/test-financial-reports-first-click.mjs
 */
import { chromium } from 'playwright';

const baseUrl = process.env.APP_URL ?? 'http://127.0.0.1:8099';
const email = process.env.TEST_USER_EMAIL;
const password = process.env.TEST_USER_PASSWORD;

if (!email || !password) {
    console.error('Set TEST_USER_EMAIL and TEST_USER_PASSWORD to run the browser click test.');
    process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

try {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(dashboard|two-factor)/);

    if (page.url().includes('two-factor')) {
        console.log('Skipping browser click test: account requires 2FA challenge.');
        process.exit(0);
    }

    await page.goto(`${baseUrl}/financial-reports`, { waitUntil: 'networkidle' });

    const overlayCount = await page.locator('#bank-account-panel, #entity-workspace-panel').count();
    if (overlayCount > 0) {
        throw new Error('Workspace overlay panels are still present on /financial-reports.');
    }

    const firstCard = page.locator('[data-report-url]').first();
    const targetUrl = await firstCard.getAttribute('data-report-url');
    if (!targetUrl) {
        throw new Error('No report card with data-report-url found.');
    }

    await Promise.all([
        page.waitForURL((url) => url.pathname + url.search !== '/financial-reports', { timeout: 10000 }),
        firstCard.click(),
    ]);

    const current = new URL(page.url());
    const expected = new URL(targetUrl, baseUrl);

    if (!current.pathname.startsWith(expected.pathname)) {
        throw new Error(`First click navigated to ${current.href}, expected path starting with ${expected.pathname}`);
    }

    console.log('PASS: first click on financial-reports card navigated to', page.url());
} finally {
    await browser.close();
}
