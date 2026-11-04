import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import * as fs from 'fs';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let SLUG = '';
test.beforeAll(() => {
  SLUG = mysql('SELECT slug FROM tours WHERE is_active = 1 ORDER BY id LIMIT 1');
  if (!SLUG) throw new Error('tidak ada tour aktif untuk PDF');
});

function pdfHtmlFlags(slug: string, lang: string): any {
  const out = execFileSync('php', ['/tmp/pdf-html-check.php', slug, lang], { encoding: 'utf8' });
  return JSON.parse(out);
}

async function dlPdfSize(page: any, lang: string): Promise<number> {
  const dlPromise = page.waitForEvent('download');
  await page.goto(BASE + '/tour-itinerary-pdf.php?slug=' + SLUG + '&pdf_lang=' + lang).catch(() => {});
  const dl = await dlPromise;
  const p = await dl.path();
  if (!p) throw new Error('download path kosong ' + lang);
  return fs.statSync(p).size;
}

test('brosur PDF id terunduh dan memakai brand Royal Dream', async ({ page }) => {
  expect(await dlPdfSize(page, 'id')).toBeGreaterThan(10000);
  const f = pdfHtmlFlags(SLUG, 'id');
  expect(f.ok).toBe(true);
  expect(f.hasName).toBe(true);
  expect(f.noOld).toBe(true);
  expect(f.hasWa).toBe(true);
  expect(f.hasAddr).toBe(true);
  expect(f.hasAccent).toBe(true);
  expect(f.hasGold).toBe(true);
  expect(f.hasTagline).toBe(true);
});

test('brosur PDF en terunduh dan memakai brand Royal Dream', async ({ page }) => {
  expect(await dlPdfSize(page, 'en')).toBeGreaterThan(10000);
  const f = pdfHtmlFlags(SLUG, 'en');
  expect(f.ok).toBe(true);
  expect(f.hasName).toBe(true);
  expect(f.noOld).toBe(true);
});
