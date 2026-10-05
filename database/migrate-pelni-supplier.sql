-- migrate-pelni-supplier.sql — data booking penyedia (klikmbc.biz) untuk pelni_bookings.
-- Dipakai saat payment gateway dimatikan: pembeli bayar langsung via VA/transfer dari penyedia.
ALTER TABLE pelni_bookings
    ADD COLUMN supplier_invoice  VARCHAR(40)   DEFAULT NULL AFTER status,
    ADD COLUMN supplier_va       VARCHAR(50)   DEFAULT NULL AFTER supplier_invoice,
    ADD COLUMN supplier_bank     VARCHAR(80)   DEFAULT NULL AFTER supplier_va,
    ADD COLUMN supplier_method   VARCHAR(40)   DEFAULT NULL AFTER supplier_bank,
    ADD COLUMN supplier_total    DECIMAL(12,2) DEFAULT NULL AFTER supplier_method,
    ADD COLUMN supplier_deadline VARCHAR(60)   DEFAULT NULL AFTER supplier_total,
    ADD COLUMN supplier_status   VARCHAR(255)  DEFAULT NULL AFTER supplier_deadline;
