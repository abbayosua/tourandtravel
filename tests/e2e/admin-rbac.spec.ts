import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * RBAC panel admin: superadmin melihat semua menu + Kelola Admin;
 * staff (grant dashboard+tours) disembunyikan menunya dan
 * ditolak saat akses URL langsung (redirect dashboard + flash).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const SUPER_USER = 'admin';
const SUPER_PASS = 'tmpcheck123';
const STAFF_USER = 'e2e-staff@t.local';
const STAFF_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

async function adminLogin(page: Page, username: string, password: string) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(STAFF_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();
  const id = Number(
    mysql(
      `INSERT INTO admins (username, password_hash, role) VALUES ('${STAFF_USER}', '${hash}', 'staff') ` +
        `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'staff'; ` +
        `SELECT id FROM admins WHERE username = '${STAFF_USER}'`
    )
  );
  mysql(`DELETE FROM admin_permissions WHERE admin_id = ${id}`);
  mysql(
    `INSERT INTO admin_permissions (admin_id, page_key) VALUES ` +
      `(${id}, 'tours'), (${id}, 'bookings')`
  );
});

test.afterAll(() => {
  const id = Number(mysql(`SELECT id FROM admins WHERE username = '${STAFF_USER}'`));
  if (id) {
    mysql(`DELETE FROM admin_permissions WHERE admin_id = ${id}`);
    mysql(`DELETE FROM admins WHERE id = ${id}`);
  }
});

test('superadmin melihat Kelola Admin + semua menu', async ({ page }) => {
  await adminLogin(page, SUPER_USER, SUPER_PASS);
  await page.goto(`${BASE}/admin/admins.php?lang=id`);
  await expect(page.locator('[data-testid="admin-add-btn"]')).toBeVisible();
  await expect(page.locator('#adminSidebar a[href="wa-settings.php"]')).toBeVisible();
  await expect(page.locator('#adminSidebar a[href="admins.php"]')).toBeVisible();
});

test('appearance disembunyikan total (tech debt)', async ({ page }) => {
  await adminLogin(page, SUPER_USER, SUPER_PASS);
  await page.goto(`${BASE}/admin/dashboard.php?lang=id`);
  await expect(page.locator('#adminSidebar a[href="appearance.php"]')).toHaveCount(0);
  await page.goto(`${BASE}/admin/appearance.php?lang=id`);
  await expect(page).toHaveURL(/dashboard\.php/);
  await expect(page.locator('[data-testid="admin-flash"]')).toContainText('tidak memiliki akses');
  await page.goto(`${BASE}/admin/admins.php?lang=id`);
  await expect(page.locator('#g-appearance')).toHaveCount(0);
});

test('staff dibatasi: menu disaring + URL langsung ditolak', async ({ page }) => {
  await adminLogin(page, STAFF_USER, STAFF_PASS);
  await page.goto(`${BASE}/admin/dashboard.php?lang=id`);
  await expect(page.locator('#adminSidebar a[href="tours.php"]')).toBeVisible();
  await expect(page.locator('#adminSidebar a[href="bookings.php"]')).toBeVisible();
  await expect(page.locator('#adminSidebar a[href="wa-settings.php"]')).toHaveCount(0);
  await expect(page.locator('#adminSidebar a[href="admins.php"]')).toHaveCount(0);

  await page.goto(`${BASE}/admin/wa-settings.php?lang=id`);
  await expect(page).toHaveURL(/dashboard\.php/);
  await expect(page.locator('[data-testid="admin-flash"]')).toContainText('tidak memiliki akses');

  await page.goto(`${BASE}/admin/admins.php?lang=id`);
  await expect(page).toHaveURL(/dashboard\.php/);
});
