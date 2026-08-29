import { test, expect, Page } from '@playwright/test';

const LOCAL_ADMIN = 'http://localhost:8765/admin';

async function loginAsAdmin(page: Page) {
    await page.goto(`${LOCAL_ADMIN}/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Admin Bulk Set Content Language', () => {

    test('bulk language form exists', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${LOCAL_ADMIN}/tours.php`);
        await page.waitForLoadState('networkidle');

        await expect(page.locator('form#bulkLangForm')).toBeVisible();
        await expect(page.locator('select[name="bulk_lang"]')).toBeVisible();
        await expect(page.locator('button:has-text("Terapkan")')).toBeVisible();
    });

    test('checkboxes exist on each tour row', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${LOCAL_ADMIN}/tours.php`);
        await page.waitForLoadState('networkidle');

        const count = await page.locator('.tour-check').count();
        expect(count).toBeGreaterThan(0);
    });

    test('select all checkbox works', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${LOCAL_ADMIN}/tours.php`);
        await page.waitForLoadState('networkidle');

        await page.evaluate(() => {
            const checkAll = document.getElementById('checkAll') as HTMLInputElement;
            checkAll.checked = true;
            checkAll.dispatchEvent(new Event('change'));
        });

        const result = await page.evaluate(() => {
            const checked = document.querySelectorAll('.tour-check:checked').length;
            const total = document.querySelectorAll('.tour-check').length;
            const countText = document.getElementById('bulkCount')?.textContent || '';
            return { checked, total, countText };
        });

        expect(result.checked).toBe(result.total);
        expect(result.countText).toContain('tour dipilih');
    });

    test('individual checkbox select shows count', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${LOCAL_ADMIN}/tours.php`);
        await page.waitForLoadState('networkidle');

        await page.evaluate(() => {
            const cb = document.querySelector('.tour-check') as HTMLInputElement;
            cb.checked = true;
            cb.dispatchEvent(new Event('change'));
        });

        const countText = await page.evaluate(() => document.getElementById('bulkCount')?.textContent || '');
        expect(countText).toContain('tour dipilih');
    });

    test('bulk apply redirects with success', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${LOCAL_ADMIN}/tours.php`);
        await page.waitForLoadState('networkidle');

        await page.evaluate(() => {
            const cb = document.querySelector('.tour-check') as HTMLInputElement;
            cb.checked = true;
        });

        await page.locator('select[name="bulk_lang"]').selectOption('en');

        page.on('dialog', dialog => dialog.accept());
        await page.locator('button:has-text("Terapkan")').click();
        await page.waitForLoadState('networkidle');

        expect(page.url()).toContain('msg=bulk_lang');
    });
});
