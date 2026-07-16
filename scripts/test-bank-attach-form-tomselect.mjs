/**
 * Smoke test: bank attach form Tom Select initializes on Link existing tab.
 *
 * Usage:
 *   $env:TEST_USER_EMAIL="admin@gmail.com"
 *   $env:TEST_USER_PASSWORD="your-password"
 *   node scripts/test-bank-attach-form-tomselect.mjs
 */
import { chromium } from 'playwright';

const baseUrl = process.env.APP_URL ?? 'http://127.0.0.1:8001';
const email = process.env.TEST_USER_EMAIL;
const password = process.env.TEST_USER_PASSWORD;

if (!email || !password) {
    console.error('Set TEST_USER_EMAIL and TEST_USER_PASSWORD.');
    process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

page.on('console', (msg) => {
    if (msg.type() === 'error') {
        console.error('BROWSER ERROR:', msg.text());
    }
});

page.on('pageerror', (err) => {
    console.error('PAGE ERROR:', err.message);
});

try {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(dashboard|two-factor)/, { timeout: 15000 });

    if (page.url().includes('two-factor')) {
        console.log('SKIP: 2FA required');
        process.exit(0);
    }

    await page.goto(`${baseUrl}/business-entities/13/assets/create`, { waitUntil: 'networkidle' });

    const configPresent = await page.locator('#add-bank-account-config').count();
    console.log('config present:', configPresent);

    await page.locator('[data-open-add-bank-account]').first().click();
    await page.waitForSelector('#bank-account-panel[data-panel-open="true"]', { timeout: 10000 });

    await page.locator('[data-bank-panel-tab="link"]').click();
    await page.waitForSelector('#assign-bank-account-form', { timeout: 15000 });

    const diagnostics = await page.evaluate(() => {
        const panel = document.getElementById('bank-account-panel');
        const linkPane = document.querySelector('[data-bank-panel-pane="link"]');
        const select = document.getElementById('link_bank_account_id');
        const purpose = document.getElementById('attach_account_purpose');

        return {
            panelOpen: panel?.dataset?.panelOpen,
            panelHidden: panel?.hidden,
            panelInert: panel?.inert,
            linkPaneHidden: linkPane?.classList?.contains('hidden'),
            selectExists: Boolean(select),
            selectDataTomselect: select?.hasAttribute('data-tomselect'),
            selectTomselect: Boolean(select?.tomselect),
            selectDeferred: select?.dataset?.tomselectDeferred,
            selectDisplay: select ? getComputedStyle(select).display : null,
            selectVisibility: select ? getComputedStyle(select).visibility : null,
            wrapperCount: document.querySelectorAll('#bank-attach-form-host .ts-wrapper').length,
            purposeExists: Boolean(purpose),
            purposeTomselect: Boolean(purpose?.tomselect),
            purposeDisabled: purpose?.disabled,
            purposeOptionCount: purpose?.options?.length ?? 0,
            purposeHiddenOptions: purpose
                ? Array.from(purpose.options).filter((o) => o.hidden).length
                : 0,
        };
    });

    console.log('Diagnostics:', JSON.stringify(diagnostics, null, 2));

    if (!diagnostics.selectTomselect) {
        throw new Error('Portfolio account Tom Select did not initialize');
    }

    if (!diagnostics.purposeTomselect) {
        throw new Error('Purpose Tom Select did not initialize');
    }

    const accountControl = page.locator('#link_bank_account_id').locator('xpath=ancestor::div[contains(@class,"ts-wrapper")]').first();
    await accountControl.click();
    await page.waitForSelector('body > .ts-dropdown', { timeout: 5000 });

    const dropdownVisible = await page.locator('body > .ts-dropdown').isVisible();
    if (!dropdownVisible) {
        throw new Error('Tom Select dropdown did not open on click');
    }

    console.log('PASS: attach form Tom Select fields initialize and dropdown opens');
} finally {
    await browser.close();
}
