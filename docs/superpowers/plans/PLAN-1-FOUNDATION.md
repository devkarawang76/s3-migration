# Plan 1: Foundation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Setup Laravel project dengan models, migrations, dan authentication yang compatible dengan legacy system.

**Architecture:** Laravel 13 + MySQL dual-connection (legacy read-only + new normalized). Custom User Provider untuk MD5 → bcrypt migration. RBAC via spatie/laravel-permission.

**Tech Stack:** PHP 8.2, Laravel 13, MySQL 8.0, Redis, Sanctum

**Spec:** `docs/superpowers/specs/MODEL_SPEC.md`, `docs/superpowers/specs/MIGRATION_SPEC.md`, `docs/superpowers/specs/BACKEND_DESIGN.md`

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

### Task 1.1: Laravel Project Setup + Dual Database Config

**Files:**
- Create: `backend/composer.json`
- Create: `backend/.env.example`
- Create: `backend/config/database.php`

- [ ] **Step 1: Create Laravel project structure**
- [ ] **Step 2: Install additional dependencies** (sanctum, spatie/laravel-permission, filament)
- [ ] **Step 3: Configure dual database in `config/database.php`**
- [ ] **Step 4: Create `.env.example`**
- [ ] **Step 5: Commit**

---

### Task 1.2: Base Model + LegacyModel Trait

**Files:**
- Create: `backend/app/Models/LegacyModel.php`
- Create: `backend/app/Models/Traits/ReadOnly.php`
- Test: `backend/tests/Unit/Models/LegacyModelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement `LegacyModel`**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.3: User Models (LegacyUser, User, Admin)

**Files:**
- Create: `backend/app/Models/LegacyUser.php`
- Create: `backend/app/Models/User.php`
- Create: `backend/app/Models/Admin.php`
- Test: `backend/tests/Unit/Models/UserTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement models**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.4: Custom User Provider (MD5 → bcrypt)

**Files:**
- Create: `backend/app/Providers/LegacyUserProvider.php`
- Test: `backend/tests/Unit/Providers/LegacyUserProviderTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement `LegacyUserProvider`**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.5: Auth Config + Guards

**Files:**
- Modify: `backend/config/auth.php`
- Test: `backend/tests/Unit/Auth/GuardConfigTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Update `config/auth.php`**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.6: RBAC Models (Role, Permission, Pivots)

**Files:**
- Create: `backend/app/Models/Role.php`
- Create: `backend/app/Models/Permission.php`
- Create: `backend/app/Models/RoleUser.php`
- Create: `backend/app/Models/PermissionRole.php`
- Test: `backend/tests/Unit/Models/RBACModelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement RBAC models**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.7: Core Models (Ticket, Unit, Customer, Engineer, ServiceOffice)

**Files:**
- Create: `backend/app/Models/Ticket.php`
- Create: `backend/app/Models/Unit.php`
- Create: `backend/app/Models/Customer.php`
- Create: `backend/app/Models/Engineer.php`
- Create: `backend/app/Models/ServiceOffice.php`
- Test: `backend/tests/Unit/Models/CoreModelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement core models**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.8: PM Models (PreventiveMaintenance, PmTicketLink)

**Files:**
- Create: `backend/app/Models/PreventiveMaintenance.php`
- Create: `backend/app/Models/PmTicketLink.php`
- Test: `backend/tests/Unit/Models/PMModelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement PM models**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.9: Supporting Models (40+ models)

**Files:**
- Create: `backend/app/Models/TicketComment.php`
- Create: `backend/app/Models/TicketTimeline.php`
- Create: `backend/app/Models/TicketUnit.php`
- Create: `backend/app/Models/TicketHistoryStatus.php`
- Create: `backend/app/Models/TicketRequestPart.php`
- Create: `backend/app/Models/TicketAssetList.php`
- Create: `backend/app/Models/WarrantyUnit.php`
- Create: `backend/app/Models/UnitType.php`
- Create: `backend/app/Models/ModelType.php`
- Create: `backend/app/Models/WarehouseList.php`
- Create: `backend/app/Models/SupplierList.php`
- Create: `backend/app/Models/StockLogistic.php`
- Create: `backend/app/Models/ProductList.php`
- Create: `backend/app/Models/CompPart.php`
- Create: `backend/app/Models/SalesOrderList.php`
- Create: `backend/app/Models/SalesOrderDetail.php`
- Create: `backend/app/Models/SalesOrderApproval.php`
- Create: `backend/app/Models/QuotationList.php`
- Create: `backend/app/Models/QuotationDetail.php`
- Create: `backend/app/Models/QuotationApproval.php`
- Create: `backend/app/Models/SprfList.php`
- Create: `backend/app/Models/SprfDetail.php`
- Create: `backend/app/Models/SprfApproval.php`
- Create: `backend/app/Models/LoanList.php`
- Create: `backend/app/Models/TicketLoan.php`
- Create: `backend/app/Models/AssetApprovalList.php`
- Create: `backend/app/Models/InternalLog.php`
- Create: `backend/app/Models/Notification.php`
- Create: `backend/app/Models/Holiday.php`
- Create: `backend/app/Models/DocumentData.php`
- Create: `backend/app/Models/CallLog.php`
- Create: `backend/app/Models/Token.php`
- Create: `backend/app/Models/Verification2FA.php`
- Create: `backend/app/Models/UserActivityHistory.php`
- Create: `backend/app/Models/TicketHistoryRequestPart.php`
- Create: `backend/app/Models/UserProfile.php`
- Create: `backend/app/Models/UserOffice.php`
- Create: `backend/app/Models/EnumMapping.php`
- Create: `backend/app/Models/TicketStatus.php`
- Create: `backend/app/Models/CustomerList.php`
- Create: `backend/app/Models/UnitCustomer.php`
- Test: `backend/tests/Unit/Models/SupportingModelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement all supporting models**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.10: Migrations untuk Semua Tabel Baru

**Files:**
- Create: `backend/database/migrations/2026_10_01_000001_create_users_table.php` through `2026_10_01_000024_create_audit_logs_table.php`
- Test: `backend/tests/Feature/Migration/MigrationTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Create all 24 migration files**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.11: Seeders (Roles, Permissions, Admin, Enum Mappings)

**Files:**
- Create: `backend/database/seeders/RoleSeeder.php`
- Create: `backend/database/seeders/PermissionSeeder.php`
- Create: `backend/database/seeders/AdminSeeder.php`
- Create: `backend/database/seeders/EnumMappingSeeder.php`
- Test: `backend/tests/Feature/Seeder/SeederTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement seeders**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 1.12: Unit Tests untuk Models

**Files:**
- Create: `backend/tests/Unit/Models/TicketTest.php`
- Create: `backend/tests/Unit/Models/UnitTest.php`
- Create: `backend/tests/Unit/Models/CustomerTest.php`
- Create: `backend/tests/Unit/Models/UserTest.php`
- Create: `backend/tests/Unit/Models/RoleTest.php`

- [ ] **Step 1: Write comprehensive unit tests**
- [ ] **Step 2: Run all tests**
- [ ] **Step 3: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 1.1 | Laravel setup + dual DB | 3 | 30 min |
| 1.2 | Base model + trait | 3 | 15 min |
| 1.3 | User models | 4 | 30 min |
| 1.4 | Custom User Provider | 2 | 20 min |
| 1.5 | Auth config + guards | 2 | 15 min |
| 1.6 | RBAC models | 5 | 30 min |
| 1.7 | Core models | 6 | 45 min |
| 1.8 | PM models | 3 | 20 min |
| 1.9 | Supporting models | 42 | 60 min |
| 1.10 | Migrations | 25 | 90 min |
| 1.11 | Seeders | 5 | 30 min |
| 1.12 | Unit tests | 6 | 45 min |

**Total:** ~100 files, ~7 hours

---

*Last updated: 2026-10-01*
