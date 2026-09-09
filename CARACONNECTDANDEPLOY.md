# CARACONNECTDANDEPLOY.md — SSH & Deploy Guide

## Server Info

| Item | Value |
|------|-------|
| Host | `82.25.62.13` |
| User | `bikinweb` |
| SSH Key | `macbook_rsa` (di project root) |
| Web Root | `/home/bikinweb/domains/tourandtravel.web.id/public_html` |
| Domain | `tourandtravel.web.id` |
| Database | `bikinweb_tourandtravel` |
| DB User | `bikinweb_tourandtravel` |

---

## 1. Connect SSH

```bash
ssh -i macbook_rsa bikinweb@82.25.62.13
```

Untuk non-interactive (eksekusi command langsung):

```bash
ssh -o StrictHostKeyChecking=no -i macbook_rsa bikinweb@82.25.62.13 "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && git status"
```

---

## 2. Deploy via Git Pull

```bash
ssh -o StrictHostKeyChecking=no -i macbook_rsa bikinweb@82.25.62.13 \
  "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && \
   git stash && \
   git pull --rebase origin main && \
   echo 'DONE'"
```

### Jika ada merge conflict di `includes/config.php`

```bash
ssh -o StrictHostKeyChecking=no -i macbook_rsa bikinweb@82.25.62.13 \
  "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && \
   git checkout --theirs includes/config.php && \
   git add includes/config.php && \
   git commit -m 'merge: accept server config' && \
   git pull --rebase origin main && \
   echo 'DONE'"
```

### Restore config produksi (jika ter-overwrite)

```bash
ssh -o StrictHostKeyChecking=no -i macbook_rsa bikinweb@82.25.62.13 \
  "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && \
   cp /tmp/config.php.bak includes/config.php"
```

---

## 3. Run Database Migrations

```bash
ssh -i macbook_rsa bikinweb@82.25.62.13 \
  "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && \
   for f in database/migrate-*.sql; do \
     echo \"== \$f ==\"; \
     mysql -u bikinweb_tourandtravel -p'123qwe!@#QWE' bikinweb_tourandtravel < \"\$f\" || echo \"FAILED: \$f\"; \
   done"
```

Migrasi idempotent (aman diulang). PHP migrations:

```bash
ssh -i macbook_rsa bikinweb@82.25.62.13 \
  "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && \
   php database/migrate-translations-zh.php && \
   php database/migrate-expenses.php"
```

---

## 4. Backup Database

```bash
ssh -i macbook_rsa bikinweb@82.25.62.13 \
  "mysqldump -u bikinweb_tourandtravel -p'123qwe!@#QWE' bikinweb_tourandtravel | gzip > /home/bikinweb/db-backup/tourandtravel-\$(date +%Y%m%d).sql.gz"
```

---

## 5. Check Server Logs

```bash
ssh -i macbook_rsa bikinweb@82.25.62.13 "tail -50 /home/bikinweb/logs/error.log"
```

---

## 6. Verify Deploy

```bash
curl -sI https://tourandtravel.web.id/ | head -5
curl -sI https://tourandtravel.web.id/ferries.php | head -5
curl -sI https://tourandtravel.web.id/flights.php | head -5
```

---

## Quick One-Liner Deploy

```bash
# Full deploy: pull + backup + migrate
ssh -i macbook_rsa bikinweb@82.25.62.13 "cd /home/bikinweb/domains/tourandtravel.web.id/public_html && git stash && git pull --rebase origin main && for f in database/migrate-*.sql; do mysql -u bikinweb_tourandtravel -p'123qwe!@#QWE' bikinweb_tourandtravel < \"\$f\" 2>/dev/null; done && echo 'DEPLOY OK'"
```

---

## Important Notes

- **JANGAN** overwrite `includes/config.php` — sudah di-custom untuk produksi
- `uploads/` dan `assets/img/` sudah ada di server, tidak perlu upload ulang
- Semua migrasi SQL idempotent (CREATE TABLE IF NOT EXISTS, INSERT IGNORE)
- `.env` dan `config.php` tidak di-commit (sudah di `.gitignore`)
