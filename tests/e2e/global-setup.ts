import { execSync } from 'child_process';

async function globalSetup() {
  // Bersihkan data test yang tidak idempotent sebelum suite berjalan
  try {
    execSync(
      `mysql -u root tourandtravel -e "
        DELETE FROM hotel_bookings WHERE id > 1;
        DELETE FROM tours WHERE title LIKE 'E2E%' OR title LIKE 'Test%';
        DELETE FROM bookings WHERE booking_code = 'TAT-DEL01';
      "`,
      { stdio: 'pipe' }
    );
  } catch {
    // DB cleanup optional — jangan blokir test
  }
}

export default globalSetup;