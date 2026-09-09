-- Step 21: kolom lang di fcm_tokens untuk bahasa push per-device
ALTER TABLE fcm_tokens ADD COLUMN lang VARCHAR(5) NULL DEFAULT NULL AFTER platform;
