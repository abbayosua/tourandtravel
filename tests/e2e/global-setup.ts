import { execFileSync } from 'child_process';
import * as fs from 'fs';
import * as os from 'os';
import * as path from 'path';

/**
 * Global setup e2e admin modal.
 *
 * Menyiapkan kredensial admin khusus test (admin/tmpcheck123) di DB lokal supaya
 * spec tidak bergantung pada password admin yang sedang dipakai developer.
 * Hash asli disimpan dulu dan dipulihkan oleh global-teardown.ts.
 * Hanya menyentuh DB lokal (mysql -uroot tourandtravel).
 */
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
export const HASH_BACKUP_FILE = path.join(os.tmpdir(), 'tourandtravel-e2e-admin-hash.txt');

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

export default async function globalSetup() {
  const original = mysql(`SELECT password_hash FROM admins WHERE username = '${ADMIN_USER}'`);
  if (original) fs.writeFileSync(HASH_BACKUP_FILE, original, 'utf8');

  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(ADMIN_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();

  mysql(
    `INSERT INTO admins (username, password_hash) VALUES ('${ADMIN_USER}', '${hash}') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
}
