import { test, expect } from '@playwright/test';

const LOCAL = 'http://localhost/tourandtravel';
const TOUR_SLUG = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';

test.describe('Tour Content Translation - Full', () => {

    test('tour title AND description translate EN->ID', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=id`);
        await page.waitForLoadState('networkidle');

        const title = await page.textContent('h2.fw-bold');
        expect(title).toContain('SOROTAN');

        const body = await page.textContent('body');
        // Description should contain Indonesian words
        const hasIDContent = body.includes('Nikmati') || body.includes('Kunjungi') || body.includes('Rasakan') || body.includes('kunjungi');
        expect(hasIDContent).toBeTruthy();
    });

    test('tour title AND description translate ID->EN', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=en`);
        await page.waitForLoadState('networkidle');

        const title = await page.textContent('h2.fw-bold');
        expect(title).toContain('HIGHLIGHTS');

        const body = await page.textContent('body');
        const hasENContent = body.includes('Experience') || body.includes('Visit') || body.includes('Discover');
        expect(hasENContent).toBeTruthy();
    });

    test('switching lang changes both title AND description', async ({ page }) => {
        await page.goto(`${LOCAL}/tour-detail.php?slug=${TOUR_SLUG}&lang=en`);
        await page.waitForLoadState('networkidle');

        let body = await page.textContent('body');
        const hasEN = body.includes('Experience') || body.includes('Visit');
        expect(hasEN).toBeTruthy();

        // Switch to ID
        await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
        await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
        await page.waitForLoadState('networkidle');

        body = await page.textContent('body');
        const hasID = body.includes('Nikmati') || body.includes('Kunjungi') || body.includes('Rasakan') || body.includes('SOROTAN');
        expect(hasID).toBeTruthy();
    });
});
