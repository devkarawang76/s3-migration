# Plan 5: Data Migration

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrasi data dari legacy ke new schema tanpa data loss.

**Architecture:** Migration commands dengan chunked processing. Backward compatibility views. Dual-write strategy untuk zero downtime.

**Tech Stack:** Laravel 13, MySQL 8.0

**Spec:** `docs/superpowers/specs/MODEL_SPEC.md`, `docs/superpowers/specs/DEPLOYMENT_STRATEGY.md`

---

## Global Constraints

- **Legacy database is read-only** — no INSERT/UPDATE/DELETE pada tabel legacy
- **SQL injection fix di legacy di-skip** — aplikasi sedang dijadikan staging
- **Password migration** — MD5 di-rehash ke bcrypt saat login pertama
- **Backward compatibility** — view `v_legacy_tickets` untuk legacy system
- **Feature flags** — gradual rollout per module via `config/features.php`
- **Foreign key alignment** — `ticket_id` di tabel legacy adalah string, di tabel baru adalah bigint

---

## Tasks

### Task 5.1: Migration commands (migrate:legacy-tickets, sync:legacy)

**Files:**
- Create: `backend/app/Console/Commands/MigrateLegacyTicketsCommand.php`
- Create: `backend/app/Console/Commands/SyncLegacyDataCommand.php`
- Test: `backend/tests/Feature/Migration/MigrationCommandTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement migration commands**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 5.2: LegacySyncService

**Files:**
- Create: `backend/app/Services/LegacySyncService.php`
- Test: `backend/tests/Unit/Services/LegacySyncServiceTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement LegacySyncService**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 5.3: Backward compatibility views

**Files:**
- Create: `backend/database/migrations/2026_10_01_000025_create_legacy_views.php`
- Test: `backend/tests/Feature/Migration/LegacyViewTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Create backward compatibility views**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 5.4: Data verification commands

**Files:**
- Create: `backend/app/Console/Commands/DbVerifyCommand.php`
- Test: `backend/tests/Feature/Migration/DbVerifyTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement verification command**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 5.5: Feature tests untuk migration

**Files:**
- Create: `backend/tests/Feature/Migration/MigrationTest.php`
- Create: `backend/tests/Feature/Migration/SyncTest.php`
- Create: `backend/tests/Feature/Migration/LegacyViewTest.php`

- [ ] **Step 1: Write comprehensive feature tests**
- [ ] **Step 2: Run all tests**
- [ ] **Step 3: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 5.1 | Migration commands | 3 | 30 min |
| 5.2 | LegacySyncService | 2 | 30 min |
| 5.3 | Backward compatibility views | 2 | 20 min |
| 5.4 | Data verification commands | 2 | 15 min |
| 5.5 | Feature tests | 3 | 30 min |

**Total:** ~12 files, ~2 hours

---

*Last updated: 2026-10-01*
