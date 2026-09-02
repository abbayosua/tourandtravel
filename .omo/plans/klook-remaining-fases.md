# Plan: klook-remaining-fases

> Created: 2026-09-03 00:16:53
> **Status**: Draft

## Objective

Menyelesaikan follow-up tersisa dari Klook 1:1 redesign: Fase 9 (halaman Trains, eSIM, FAQ + admin CRUD), admin sidebar links untuk modul baru, Fase 8.12-8.15 (E2E referral/cancellation/wallet spend), Fase 10 (responsive polish + full playwright benchmark + commit)

## Scope

**In Scope:**
-

**Out of Scope:**
-

## Context

App PHP vanilla di /Users/user/www/tourandtravel, server XAMPP localhost/tourandtravel, MySQL 9.7 (no ADD COLUMN IF NOT EXISTS - via seed-klook-ui.php), DB tourandtravel, admin/password. Tabel trains, train_bookings, connectivity_products, faq_categories, faq_items sudah ada di database/schema-klook.sql tapi halaman belum dibuat. Pola existing: attractions.php/attraction-detail.php/admin CRUD sebagai template (Fase 7), renderTourCard() dsb. di includes/components/, header/footer klook. Test: npx playwright test tests/e2e/*.spec.ts --workers=1, 43 spec ~354 tests green. Wallet: includes/wallet.php dengan getWalletBalance/addWalletTransaction/spendWallet/refundWallet. Referral di register.php + referral.php. Existing plan: .omo/plans/klook-11-redesign.md

## Acceptance Criteria

1) trains.php + train-detail.php + booking ke train_bookings + admin/trains.php + admin/train-edit.php (pola transfer) 2) esim.php catalog + detail + booking ke connectivity_products 3) faq.php public + admin/faq CRUD (categories+items) 4) Admin sidebar punya link ke attractions, transfers, promo-codes, collections, loyalty-settings, trains, faq, esim admin 5) Spec E2E baru: referral-flow, cancellation, wallet-spend 6) npx playwright test full suite green 7) Responsive mobile pass 8) Commit

## Approach

-

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 1 | - | - | pending |

## Risks & Mitigations

-

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled
