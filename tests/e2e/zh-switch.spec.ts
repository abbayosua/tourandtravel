import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8090';

const PHP_ERROR = /(Fatal error|Warning|Deprecated|Parse error|Uncaught|Undefined variable)/i;

test.describe('ZH Language Switch', () => {
  test('switch ke zh via ?lang=zh → cookie ter-set, html lang="zh", label Mandarin', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tours.php?lang=zh`, { waitUntil: 'load' });
    expect(resp?.status() ?? 200).toBe(200);

    // 1) <html lang="zh">
    await page.waitForSelector('html', { timeout: 10000 });
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh');

    // 2) cookie lang=zh ter-set (set oleh config.php + setLang)
    const cookies = await page.context().cookies();
    const langCookie = cookies.find(c => c.name === 'lang');
    expect(langCookie, 'cookie lang ada').toBeTruthy();
    expect(langCookie!.value).toBe('zh');

    // 3) label Mandarin dari seed t() — switcher & navbar
    const body = await page.textContent('body');
    expect(body).toContain('中文');
    // 'Cari' → 搜索 (seed translations lang=zh)
    expect(body).toMatch(/搜索|首页/);

    // 4) tidak ada PHP error
    expect(body).not.toMatch(PHP_ERROR);

    // 5) persist: reload tanpa ?lang → masih zh (cookie)
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh');
  });

  test('lang tak dikenal → fallback (bukan zh)', async ({ page, context }) => {
    await context.clearCookies();
    await page.goto(`${BASE}/tours.php?lang=xx`, { waitUntil: 'load' });
    // 'xx' tidak valid → tidak pernah jadi zh; hasil fallback id/en tergantung
    // Accept-Language browser (Playwright default en-US) — keduanya OK.
    const lang = await page.locator('html').getAttribute('lang');
    expect(['id', 'en']).toContain(lang);
    const cookies = await page.context().cookies();
    const langCookie = cookies.find(c => c.name === 'lang');
    expect(langCookie?.value ?? 'id').not.toBe('zh');
  });
});
