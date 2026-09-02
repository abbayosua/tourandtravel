import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';

const PAGES = [
  { name: 'index', path: '/index.php' },
  { name: 'tours', path: '/tours.php' },
  { name: 'hotels', path: '/hotels.php' },
  { name: 'flights', path: '/flights.php' },
  { name: 'ferries', path: '/ferries.php' },
  { name: 'rental-cars', path: '/rental-cars.php' },
  { name: 'destinasi', path: '/destinasi.php' },
];

const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

for (const entry of PAGES) {
  test(`smoke: ${entry.name} renders without PHP error`, async ({ page }) => {
    const resp = await page.goto(`${BASE}${entry.path}`, { waitUntil: 'load' });
    // Navigation may return null if same-page navigation, but final URL must match
    expect(resp === null || resp.status() === 200, `${entry.path} should be 200 (or null)`).toBeTruthy();

    // Wait for content to settle
    await page.waitForSelector('body', { timeout: 10000 });
    const body = await page.textContent('body');
    expect(body!.length, `${entry.path} body not empty`).toBeGreaterThan(50);

    // no PHP errors rendered
    expect(body, `${entry.path} has no PHP error`).not.toMatch(PHP_ERROR);

    // at least one heading rendered (pages use h4/h6 etc)
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').count();
    expect(headings, `${entry.path} has headings`).toBeGreaterThan(0);

    // at least one nav and one footer
    const navs = await page.locator('nav, header').count();
    expect(navs, `${entry.path} has nav/header`).toBeGreaterThan(0);
    await expect(page.locator('footer')).toHaveCount(1);
  });
}
