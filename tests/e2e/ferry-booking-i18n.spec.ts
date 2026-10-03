import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi i18n halaman konfirmasi booking ferry (POST-success).
 *
 * Halaman ini hanya tampil SETELAH submit form, sehingga tidak terjangkau sweep
 * halaman publik (yang hanya GET). Test ini mengisi form booking ferry dalam
 * tiap bahasa lalu memastikan label konfirmasi mengikuti bahasa aktif dan tidak
 * menyisakan teks Indonesia pada versi en/zh.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const EMAIL = 'e2e-ferry-i18n@t.local';
const BOOK = 'ferry-booking.php?company=TestFerry&from=A&to=B&date=2026-12-01&time=08:00&price=100000&passengers=1&vessel=V1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

async function submitFerryBooking(page: Page, lang: string): Promise<void> {
  await page.goto(`${BASE}/${BOOK}&lang=${lang}`);
  await page.waitForLoadState('domcontentloaded');
  await page.fill('[data-testid="input-name"]', 'E2E Ferry I18N');
  await page.fill('[data-testid="input-email"]', EMAIL);
  await page.fill('[data-testid="input-phone"]', '081234567890');
  await page.fill('[data-testid="input-pax-1"]', 'E2E Ferry I18N');
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.click('[data-testid="btn-confirm-booking"]'),
  ]);
  await page.waitForLoadState('networkidle');
}

test.describe('i18n konfirmasi booking ferry', () => {
  test.afterAll(() => {
    try { mysql(`DELETE FROM ferry_bookings WHERE email = '${EMAIL}'`); } catch { /* ignore */ }
  });

  test('label konfirmasi ferry mengikuti bahasa', async ({ page }) => {
    test.setTimeout(60_000);
    const expected: Record<string, { code: string; total: string; again: string }> = {
      id: { code: 'Kode booking Anda:', total: 'Total Bayar', again: 'Cari Ferry Lagi' },
      en: { code: 'Your booking code:', total: 'Total Payment', again: 'Search Ferry Again' },
      zh: { code: '您的预订编号：', total: '应付总额', again: '再次搜索船票' },
    };

    for (const [lang, label] of Object.entries(expected)) {
      await submitFerryBooking(page, lang);
      await expect(page.locator('[data-testid="booking-code"]')).toBeVisible();
      await expect(page.locator('body')).toContainText(label.code);
      await expect(page.locator('body')).toContainText(label.total);
      await expect(page.locator('body')).toContainText(label.again);
      if (lang !== 'id') {
        await expect(page.locator('body')).not.toContainText('Kode booking Anda:');
        await expect(page.locator('body')).not.toContainText('Total Bayar');
        await expect(page.locator('body')).not.toContainText('Cari Ferry Lagi');
      }
    }
  });
});
