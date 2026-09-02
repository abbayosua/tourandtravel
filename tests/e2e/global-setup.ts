import { execSync } from 'child_process';

async function globalSetup() {
  // Bersihkan data test yang tidak idempotent sebelum suite berjalan
  try {
    execSync(
      `mysql -u root tourandtravel -e "
        DELETE FROM hotel_bookings WHERE id > 1;
        DELETE FROM tours WHERE title LIKE 'E2E%' OR title LIKE 'Test%';
        DELETE FROM bookings WHERE booking_code = 'TAT-DEL01';
        DELETE FROM train_bookings WHERE name = 'Train Guest' OR name LIKE 'Cancel Test%' OR name LIKE 'Wallet Spend%' OR email LIKE 'train_guest@%';
        DELETE FROM connectivity_bookings WHERE name = 'Esim Guest' OR email LIKE 'esim_guest@%';
        DELETE FROM attraction_bookings WHERE name LIKE 'E2E%' OR name LIKE 'Test%';
        DELETE FROM transfer_bookings WHERE name LIKE 'E2E%' OR name LIKE 'Test%';
        DELETE FROM trains WHERE name LIKE 'Test Train%';
        DELETE FROM faq_items WHERE question LIKE 'Test Question%';
        DELETE FROM users WHERE email LIKE 'e2e_%' OR email LIKE 'ref_%' OR email LIKE 'refa_%' OR email LIKE 'refb_%' OR email LIKE 'wallet_%' OR email LIKE 'wallet2_%' OR email LIKE 'wallet3_%' OR email LIKE 'cancel_%' OR email LIKE 'cancel2_%' OR email LIKE 'walletspend_%' OR email LIKE 'user_%';
      "`,
      { stdio: 'pipe' }
    );
  } catch {
    // DB cleanup optional — jangan blokir test
  }
}

export default globalSetup;