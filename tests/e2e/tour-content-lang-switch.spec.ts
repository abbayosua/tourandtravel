import { test, expect, Page } from '@playwright/test';

const LOCAL = 'http://localhost:8765';
const TOUR_SLUG = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';

test.describe('Tour Content Language Switch', () => {

    test('tour with content_language=en shows English when lang=en', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=en`);
        await page.waitForLoadState('networkidle');

        // Title should be in English (original)
        const title = await page.textContent('h2.fw-bold');
        expect(title).toContain('SHANGHAI JIANGNAN HIGHLIGHTS');

        // UI should also be English
        const body = await page.textContent('body');
        expect(body).toContain('Image Gallery');
    });

    test('tour with content_language=en shows Indonesian when lang=id', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=id`);
        await page.waitForLoadState('networkidle');

        // Title should be translated to Indonesian
        const title = await page.textContent('h2.fw-bold');
        expect(title).toContain('SOROTAN');

        // UI should also be Indonesian
        const body = await page.textContent('body');
        expect(body).toContain('Galeri Foto');
    });

    test('switching lang via navbar updates title', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=en`);
        await page.waitForLoadState('networkidle');

        // Verify English title
        let title = await page.textContent('h2.fw-bold');
        expect(title).toContain('HIGHLIGHTS');

        // Switch to ID via navbar
        await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
        await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
        await page.waitForLoadState('networkidle');

        // Title should now be Indonesian
        title = await page.textContent('h2.fw-bold');
        expect(title).toContain('SOROTAN');
    });
});
