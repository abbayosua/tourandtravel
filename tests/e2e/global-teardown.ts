import { execFileSync } from 'child_process';
import * as fs from 'fs';
import { HASH_BACKUP_FILE } from './global-setup';

/** Pulihkan hash password admin yang disimpan global-setup agar tidak mengubah kredensial developer. */
export default async function globalTeardown() {
  if (!fs.existsSync(HASH_BACKUP_FILE)) return;
  const original = fs.readFileSync(HASH_BACKUP_FILE, 'utf8').trim();
  if (original) {
    execFileSync('mysql', [
      '-uroot',
      'tourandtravel',
      '-e',
      `UPDATE admins SET password_hash = '${original}' WHERE username = 'admin'`,
    ]);
  }
  fs.unlinkSync(HASH_BACKUP_FILE);
}
