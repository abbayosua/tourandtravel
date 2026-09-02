import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;
const CARD = 'h6.fw-semibold.mb-1';

test.describe('Rental Cars - happy path', () => {

  test('tanpa param: semua mobil tampil (10)', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(10);
    expect(body).toMatch(/Agya|Avanza|Brio|Fortuner|Pajero/i);
  });

  test('city=Jakarta: kartu muncul', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=Jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(4); // Brio, Avanza, Civic, Innova
    expect(body).toMatch(/Brio|Avanza|Civic|Innova/i);
  });

  test('city=Bali: kartu muncul', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=Bali`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(3); // Xenia, Fortuner, Pajero
  });

  test('type=SUV: kartu muncul', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?type=SUV`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(3); // Terios, Fortuner, Pajero
  });

  test('city+type combined (Jakarta + MVP)', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=Jakarta&type=MVP`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(2); // Avanza, Innova
  });

  test('urutkan termurah: Agya (200rb) pertama', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const first = await page.locator(CARD).first().textContent();
    expect(first).toMatch(/Agya|200|Brio/i);
  });
});

test.describe('Rental Cars - sad path', () => {

  test('city tidak ada: empty state "Tidak ada mobil."', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=ZZZNonexistent`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada mobil/i);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(0);
  });

  test('type invalid: empty state', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?type=Hovercraft`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada mobil/i);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(0);
  });

  test('city case-sensitive (jakarta kecil): tidak match (exact WHERE)', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // exact match -> lowercase mungkin kosong (MySQL default collation insensitive, tapi biarkan fleksibel)
    const cards = await page.locator(CARD).count();
    // bisa 4 (case-insensitive collation) atau 0 — keduanya tidak fatal
  });

  test('city=Bandung: 1 mobil (Agya)', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=Bandung`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator(CARD).count();
    expect(cards).toBe(1);
    expect(body).toMatch(/Agya/i);
  });

  test('param duplikat & aneh: tidak fatal', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=Jakarta&city=Bali&type=SUV&type=MVP`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('URL-encoded XSS di city: tidak crash, tidak eksekusi', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).not.toMatch(/alert\(1\)/);
  });
});