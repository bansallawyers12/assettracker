/**
 * Smoke test: dashboard Add Transaction keeps entity + asset after opening form.
 *
 * Usage:
 *   $env:TEST_USER_EMAIL="admin@gmail.com"
 *   $env:TEST_USER_PASSWORD="password"
 *   $env:APP_URL="http://127.0.0.1:8002"
 *   node scripts/test-dashboard-transaction-entity-asset.mjs
 */
import { chromium } from 'playwright';

const baseUrl = process.env.APP_URL ?? 'http://127.0.0.1:8002';
const email = process.env.TEST_USER_EMAIL ?? 'admin@gmail.com';
const password = process.env.TEST_USER_PASSWORD ?? 'password';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

function fail(msg) {
    console.error('FAIL:', msg);
    process.exit(1);
}

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

    await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });

    const readNative = async () => page.evaluate(() => ({
        entity: document.getElementById('business_entity_id')?.value ?? '',
        asset: document.getElementById('transaction_asset_id')?.value ?? '',
        assetDisabledCount: Array.from(document.getElementById('transaction_asset_id')?.options ?? [])
            .filter((o) => o.value && o.disabled).length,
        assetTotalCount: Array.from(document.getElementById('transaction_asset_id')?.options ?? [])
            .filter((o) => o.value).length,
        formHidden: document.getElementById('add-transaction-section')?.classList.contains('hidden'),
    }));

    const beforeOpen = await readNative();
    console.log('Before open:', beforeOpen);

    await page.click('#add-transaction-btn');
    await page.waitForTimeout(500);

    const afterOpen = await readNative();
    console.log('After open (before picks):', afterOpen);

    // Pick first real entity option
    const entityId = await page.evaluate(() => {
        const sel = document.getElementById('business_entity_id');
        const opt = Array.from(sel.options).find((o) => o.value);
        return opt?.value ?? '';
    });
    if (!entityId) fail('No entity options found');

    await page.locator('#business_entity_id').locator('..').locator('.ts-control').click();
    await page.locator('.ts-dropdown-content .option').filter({ hasText: /.+/ }).first().click();
    await page.waitForTimeout(300);

    const afterEntity = await readNative();
    console.log('After entity pick:', afterEntity);

    if (!afterEntity.entity) fail('Entity native value cleared after Tom Select pick');

    const assetId = await page.evaluate((eid) => {
        const sel = document.getElementById('transaction_asset_id');
        const opt = Array.from(sel.options).find((o) => o.value && o.dataset.entityId === eid && !o.disabled);
        return opt?.value ?? '';
    }, afterEntity.entity);

    if (!assetId) {
        console.log('No asset for entity — skipping asset pick');
    } else {
        await page.locator('#transaction_asset_id').locator('..').locator('.ts-control').click();
        await page.locator('.ts-dropdown-content .option').filter({ hasText: /.+/ }).nth(1).click();
        await page.waitForTimeout(300);
    }

    const afterAsset = await readNative();
    console.log('After asset pick:', afterAsset);

    if (assetId && !afterAsset.asset) fail('Asset native value cleared after Tom Select pick');

    // Simulate reinit (same as opening form again)
    await page.evaluate(() => {
        document.getElementById('store-transaction-form')?.querySelectorAll('select[data-tomselect]').forEach((select) => {
            window.reinitTomSelect?.(select);
        });
    });
    await page.waitForTimeout(300);

    const afterReinit = await page.evaluate(() => ({
        entity: document.getElementById('business_entity_id')?.value ?? '',
        asset: document.getElementById('transaction_asset_id')?.value ?? '',
        entityLabel: document.getElementById('business_entity_id')?.tomselect?.getValue?.() ?? '',
        assetLabel: document.getElementById('transaction_asset_id')?.tomselect?.getValue?.() ?? '',
    }));
    console.log('After reinit:', afterReinit);

    if (afterReinit.entity !== afterEntity.entity) fail('Entity cleared after reinitTomSelect');
    if (assetId && afterReinit.asset !== afterAsset.asset) fail('Asset cleared after reinitTomSelect');

    console.log('PASS: entity/asset persisted through open + reinit');
} catch (err) {
    console.error('ERROR:', err.message);
    process.exit(1);
} finally {
    await browser.close();
}
