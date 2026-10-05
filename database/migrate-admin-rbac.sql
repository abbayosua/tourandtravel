-- ============================================================
-- migrate-admin-rbac.sql — hak akses admin (RBAC sederhana)
-- 1) admins.role ENUM('superadmin','staff') DEFAULT 'superadmin'
--    (akun lama otomatis superadmin agar tidak terkunci)
-- 2) admin_permissions(admin_id, page_key) — daftar halaman yang boleh
--    dibuka akun staff. Superadmin bypass (tidak butuh baris).
-- Idempotent via information_schema + CREATE TABLE IF NOT EXISTS.
-- Jalankan: mysql -u root tourandtravel < database/migrate-admin-rbac.sql
-- ============================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS admin_rbac_migrate $$
CREATE PROCEDURE admin_rbac_migrate()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'admins' AND column_name = 'role') THEN
        ALTER TABLE admins ADD COLUMN role ENUM('superadmin','staff') NOT NULL DEFAULT 'superadmin' AFTER password_hash;
    END IF;
END $$

DELIMITER ;

CALL admin_rbac_migrate();
DROP PROCEDURE admin_rbac_migrate;

CREATE TABLE IF NOT EXISTS admin_permissions (
    admin_id INT NOT NULL,
    page_key VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id, page_key),
    CONSTRAINT fk_admin_permissions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
