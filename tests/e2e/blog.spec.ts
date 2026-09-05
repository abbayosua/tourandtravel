import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\"')}"`).toString().trim();
}
async function adminLogin(page: import('@playwright/test').Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Blog — CRUD + publik + bilingual', () => {
  test.afterEach(() => {
    dbRun(`DELETE FROM posts WHERE title LIKE 'E2E Blog%';`);
  });

  test('admin: tambah artikel published → tampil di publik (ID)', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/post-edit.php`);
    await page.fill('input[name="title"]', 'E2E Blog Artikel Uji');
    await page.fill('textarea[name="excerpt"]', 'Ringkasan uji');
    await page.fill('textarea[name="body"]', 'Isi artikel uji E2E.');
    await page.selectOption('select[name="status"]', 'published');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('posts.php');

    await page.goto(`${BASE}/blog.php`);
    expect(await page.textContent('body')).toContain('E2E Blog Artikel Uji');
  });

  test('draft tidak tampil di publik', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/post-edit.php`);
    await page.fill('input[name="title"]', 'E2E Blog Draft Saja');
    await page.selectOption('select[name="status"]', 'draft');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/blog.php`);
    expect(await page.textContent('body')).not.toContain('E2E Blog Draft Saja');
  });

  test('admin: toggle publish/draft & hapus dengan konfirmasi', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/post-edit.php`);
    await page.fill('input[name="title"]', 'E2E Blog Toggle');
    await page.selectOption('select[name="status"]', 'published');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.click('tr:has-text("E2E Blog Toggle") a[href*="toggle"]');
    await page.waitForLoadState('load');
    expect(dbOne(`SELECT status FROM posts WHERE title='E2E Blog Toggle'`)).toBe('draft');

    page.once('dialog', d => d.accept());
    await page.click('tr:has-text("E2E Blog Toggle") a[href*="delete"]');
    await page.waitForLoadState('load');
    expect(dbOne(`SELECT COUNT(*) FROM posts WHERE title='E2E Blog Toggle'`)).toBe('0');
  });

  test('bilingual: EN menampilkan title_en', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/post-edit.php`);
    await page.fill('input[name="title"]', 'E2E Blog Bilingual');
    await page.fill('input[name="title_en"]', 'E2E Blog Bilingual EN Title');
    await page.selectOption('select[name="status"]', 'published');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/blog.php?lang=en`);
    expect(await page.textContent('body')).toContain('E2E Blog Bilingual EN Title');
    await page.goto(`${BASE}/blog.php?lang=id`);
    expect(await page.textContent('body')).toContain('E2E Blog Bilingual');
    expect(await page.textContent('body')).not.toContain('E2E Blog Bilingual EN Title');
  });

  test('detail artikel + related tours + meta', async ({ page }) => {
    await page.goto(`${BASE}/blog-detail.php?slug=destinasi-favorit-asia-2026`);
    const body = await page.textContent('body') || '';
    expect(body).toMatch(/Destinasi Favorit Asia|Favorite Asian/i);
    expect(body).toMatch(/Paket Rekomendasi|Recommended/);
    expect(await page.locator('script[type="application/ld+json"]').count()).toBeGreaterThan(0);
  });

  test('404 untuk slug tidak ada', async ({ page }) => {
    await page.goto(`${BASE}/blog-detail.php?slug=tidak-ada-xyz`);
    expect(await page.textContent('body')).toMatch(/tidak ditemukan|not found/i);
  });

  test('sitemap berisi blog posts', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/sitemap.php`);
    expect(await resp.text()).toContain('blog-detail.php');
  });
});
