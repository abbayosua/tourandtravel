import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Admin Tours - list', () => {

  test('list 8 tour + tabel render', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kelola Tour/i);
    // 8 rows in table
    const rows = await page.locator('table.table-tour tbody tr').count();
    expect(rows).toBeGreaterThanOrEqual(8);
  });

  test('msg=deleted: pesan sukses', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tours.php?msg=deleted`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/Tour berhasil dihapus/i);
  });
});

test.describe('Admin Tours - add', () => {

  test('submit valid: redirect msg=added + tour baru di DB', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-add.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tambah Tour Baru/i);

    await page.fill('input[name="title"]', 'E2E Test Tour');
    await page.fill('input[name="category"]', 'Test');
    await page.fill('input[name="price"]', '999');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    // Redirect ke tours.php?msg=added
    expect(page.url()).toContain('tours.php?msg=added');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/Tour berhasil ditambahkan/i);

    // Bersihkan tour test dari DB (via delete) — cari & hapus semua row E2E Test Tour
    await page.goto(`${BASE}/admin/tours.php`, { waitUntil: 'load' });
    page.on('dialog', d => d.accept());
    let deleteLinks = page.locator('tr:has-text("E2E Test Tour") a[href*="delete="]');
    let n = await deleteLinks.count();
    while (n > 0) {
      await deleteLinks.first().click();
      await page.waitForLoadState('load');
      deleteLinks = page.locator('tr:has-text("E2E Test Tour") a[href*="delete="]');
      n = await deleteLinks.count();
    }
  });

  test('submit tanpa title: error validasi', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-add.php`, { waitUntil: 'load' });
    // Bypass HTML5 required via evaluate
    await page.evaluate(() => {
      document.querySelector('input[name="title"]').removeAttribute('required');
      document.querySelector('input[name="category"]').value = 'Test';
      document.querySelector('input[name="price"]').value = '999';
      document.querySelector('button[type="submit"]').click();
    });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Judul tour harus diisi/i);
  });

  test('submit tanpa category: error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-add.php`, { waitUntil: 'load' });
    await page.evaluate(() => {
      document.querySelector('input[name="title"]').value = 'Test Tour';
      document.querySelector('input[name="category"]').removeAttribute('required');
      document.querySelector('input[name="price"]').value = '999';
      document.querySelector('button[type="submit"]').click();
    });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Kategori harus diisi/i);
  });

  test('submit dengan price=0: error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-add.php`, { waitUntil: 'load' });
    await page.fill('input[name="title"]', 'Test Tour');
    await page.fill('input[name="category"]', 'Test');
    await page.fill('input[name="price"]', '0');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Harga harus diisi/i);
  });
});

test.describe('Admin Tours - edit', () => {

  test('id valid: form terisi data tour', async ({ page }) => {
    await loginAdmin(page);
    // Ambil tour 62 (TOKYO WONDERS — belum diubah)
    await page.goto(`${BASE}/admin/tour-edit.php?id=62`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Edit Tour/i);
    const titleVal = await page.locator('input[name="title"]').inputValue();
    expect(titleVal).toMatch(/TOKYO|WONDERS|Tokyo/i);
  });

  test('id=0: redirect tours.php', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-edit.php?id=0`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('save perubahan: redirect msg=updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-edit.php?id=62`, { waitUntil: 'load' });
    await page.fill('input[name="title"]', 'E2E Updated Tour Title');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    // Redirect ke tours.php?msg=updated
    expect(page.url()).toContain('tours.php?msg=updated');
    const body = await page.textContent('body');
    expect(body).toMatch(/Tour berhasil diperbarui/i);
    // Kembalikan title asli
    await page.goto(`${BASE}/admin/tour-edit.php?id=62`, { waitUntil: 'load' });
    await page.fill('input[name="title"]', '6D TOKYO WONDERS');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
  });
});

test.describe('Admin Tours - delete', () => {

  test('delete invalid (id=999): tidak crash, redirect', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tours.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Redirect ke tours.php?msg=deleted (query OK meski 0 rows affected)
    expect(page.url()).toContain('tours.php?msg=deleted');
  });
});