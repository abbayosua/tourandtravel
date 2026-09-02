import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;
const TOUR_SLUG = '8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town';

async function registerUser(page) {
  const email = `rev_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'Review Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '081366655544');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

// Submit review via real form navigation (shares session)
async function submitReview(page, fields) {
  const resp = await page.goto(`${BASE}/review-submit.php`, { waitUntil: 'load' });
  // review-submit expects POST; GET will hit the code path but with no POST data
  // Instead: navigate to a form page? review-submit.php is POST-only.
  // Use page.evaluate to do a form POST within page context (shares cookies/session)
  return page.evaluate(async (data) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'review-submit.php';
    for (const [k, v] of Object.entries(data)) {
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = k;
      inp.value = v;
      form.appendChild(inp);
    }
    document.body.appendChild(form);
    // Intercept navigation to capture redirect
    return new Promise((resolve) => {
      form.addEventListener('submit', (e) => e.preventDefault());
      // We'll just submit and let page navigate
      form.submit();
      // Wait — can't easily capture. Use fetch instead:
    });
  }, fields);
}

test.describe('Review flow - sad path (via page fetch in session)', () => {

  test('guest POST review: redirect ke login (status 302)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/review-submit.php`, {
      form: { tour_id: '61', rating: '5', comment: 'Bagus' },
      maxRedirects: 0,
    });
    // Playwright request POST default doesn't follow redirect if maxRedirects 0
    expect(resp.status()).toBe(302);
    const loc = resp.headers()['location'];
    expect(loc).toContain('login.php');
  });
});

test.describe('Review flow - sad path (validasi via login user)', () => {

  test('rating 0: redirect tours.php (validasi)', async ({ page }) => {
    await registerUser(page);
    // Gunakan page context (session) — fetch POST dari dalam halaman
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tour_id=61&rating=0&comment=test',
        redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('rating 6: redirect tours.php (validasi)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tour_id=61&rating=6&comment=test', redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('rating absen: redirect tours.php', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tour_id=61&comment=test', redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('comment kosong: redirect tours.php', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tour_id=61&rating=5&comment=', redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('tour_id 0 (tanpa tour_id): redirect tours.php', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'rating=5&comment=test', redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('user tanpa confirmed booking: redirect tour-detail (canReview gagal)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `tour_id=61&rating=5&comment=Bagus&slug=${encodeURIComponent('8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town')}`,
        redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });

  test('XSS comment: tidak crash (user tanpa booking -> redirect)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.evaluate(async () => {
      const r = await fetch('/tourandtravel/review-submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `tour_id=61&rating=5&comment=${encodeURIComponent('<script>alert(1)</script>')}`,
        redirect: 'manual',
      });
      return { status: r.status, type: r.type, loc: r.headers.get('location') };
    });
    expect(resp.type).toBe('opaqueredirect');
  });
});