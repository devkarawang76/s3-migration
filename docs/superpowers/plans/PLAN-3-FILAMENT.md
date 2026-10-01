# Plan 3: Filament Admin

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin panel functional dengan Filament Resources untuk semua modul.

**Architecture:** Filament 5 dengan custom admin guard. Resources untuk setiap modul bisnis. Custom pages untuk dashboard dan reports.

**Tech Stack:** Filament 5, Laravel 13

**Spec:** `docs/superpowers/specs/BACKEND_DESIGN.md`

**UI Templates:** `docs/superpowers/plans/UI-TEMPLATES.md` (Plan 3 section)

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

### Task 3.1: Filament setup + AdminPanelProvider

**Files:**
- Create: `backend/app/Providers/Filament/AdminPanelProvider.php`
- Test: `backend/tests/Feature/Filament/AdminPanelTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement AdminPanelProvider**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.2: TicketResource + Pages

**Files:**
- Create: `backend/app/Filament/Resources/TicketResource.php`
- Create: `backend/app/Filament/Resources/TicketResource/Pages/ListTickets.php`
- Create: `backend/app/Filament/Resources/TicketResource/Pages/CreateTicket.php`
- Create: `backend/app/Filament/Resources/TicketResource/Pages/EditTicket.php`
- Create: `backend/app/Filament/Resources/TicketResource/Pages/ViewTicket.php`
- Test: `backend/tests/Feature/Filament/TicketResourceTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement TicketResource + Pages**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.3: UnitResource + Pages

**Files:**
- Create: `backend/app/Filament/Resources/UnitResource.php`
- Create: `backend/app/Filament/Resources/UnitResource/Pages/ListUnits.php`
- Create: `backend/app/Filament/Resources/UnitResource/Pages/CreateUnit.php`
- Create: `backend/app/Filament/Resources/UnitResource/Pages/EditUnit.php`
- Create: `backend/app/Filament/Resources/UnitResource/Pages/ViewUnit.php`
- Test: `backend/tests/Feature/Filament/UnitResourceTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement UnitResource + Pages**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.4: PreventiveMaintenanceResource + Pages

**Files:**
- Create: `backend/app/Filament/Resources/PreventiveMaintenanceResource.php`
- Create: `backend/app/Filament/Resources/PreventiveMaintenanceResource/Pages/ListPMs.php`
- Create: `backend/app/Filament/Resources/PreventiveMaintenanceResource/Pages/CreatePM.php`
- Create: `backend/app/Filament/Resources/PreventiveMaintenanceResource/Pages/EditPM.php`
- Create: `backend/app/Filament/Resources/PreventiveMaintenanceResource/Pages/ViewPM.php`
- Test: `backend/tests/Feature/Filament/PMResourceTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement PMResource + Pages**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.5: LoanResource, SalesOrderResource, QuotationResource

**Files:**
- Create: `backend/app/Filament/Resources/LoanResource.php` + Pages
- Create: `backend/app/Filament/Resources/SalesOrderResource.php` + Pages
- Create: `backend/app/Filament/Resources/QuotationResource.php` + Pages
- Test: `backend/tests/Feature/Filament/LoanResourceTest.php`
- Test: `backend/tests/Feature/Filament/SalesOrderResourceTest.php`
- Test: `backend/tests/Feature/Filament/QuotationResourceTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement resources**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 3.6: CustomerResource, StockResource, EngineerResource

**Files:**
- Create: `backend/app/Filament/Resources/CustomerResource.php` + Pages
- Create: `backend/app/Filament/Resources/StockResource.php` + Pages
- Create: `backend/app/Filament/Resources/EngineerResource.php` + Pages
- Test: `backend/tests/Feature/Filament/CustomerResourceTest.php`
- Test: `backend/tests/Feature/Filament/StockResourceTest.php`
- Test: `backend/tests/Feature/Filament/EngineerResourceTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement resources**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 3.7: UserResource, RoleResource

**Files:**
- Create: `backend/app/Filament/Resources/UserResource.php` + Pages
- Create: `backend/app/Filament/Resources/RoleResource.php` + Pages
- Test: `backend/tests/Feature/Filament/UserResourceTest.php`
- Test: `backend/tests/Feature/Filament/RoleResourceTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement resources**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 3.8: Custom Pages (Dashboard, SlaReport, LoanReport)

**Files:**
- Create: `backend/app/Filament/Pages/Dashboard.php`
- Create: `backend/app/Filament/Pages/SlaReport.php`
- Create: `backend/app/Filament/Pages/LoanReport.php`
- Test: `backend/tests/Feature/Filament/DashboardTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement custom pages**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.9: Custom Widgets (StatsOverview, TicketChart, etc.)

**Files:**
- Create: `backend/app/Filament/Widgets/StatsOverviewWidget.php`
- Create: `backend/app/Filament/Widgets/TicketChartWidget.php`
- Create: `backend/app/Filament/Widgets/RecentTicketsTable.php`
- Create: `backend/app/Filament/Widgets/NotificationListWidget.php`
- Test: `backend/tests/Feature/Filament/WidgetTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement widgets**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 3.10: Feature tests untuk Filament

**Files:**
- Create: `backend/tests/Feature/Filament/TicketResourceTest.php`
- Create: `backend/tests/Feature/Filament/UnitResourceTest.php`
- Create: `backend/tests/Feature/Filament/AuthTest.php`

- [ ] **Step 1: Write comprehensive feature tests**
- [ ] **Step 2: Run all tests**
- [ ] **Step 3: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 3.1 | Filament setup + AdminPanelProvider | 2 | 20 min |
| 3.2 | TicketResource + Pages | 6 | 45 min |
| 3.3 | UnitResource + Pages | 6 | 30 min |
| 3.4 | PMResource + Pages | 6 | 30 min |
| 3.5 | LoanResource, SalesOrderResource, QuotationResource | 18 | 45 min |
| 3.6 | CustomerResource, StockResource, EngineerResource | 18 | 30 min |
| 3.7 | UserResource, RoleResource | 12 | 30 min |
| 3.8 | Custom Pages | 4 | 45 min |
| 3.9 | Custom Widgets | 5 | 30 min |
| 3.10 | Feature tests | 3 | 30 min |

**Total:** ~80 files, ~6 hours

---

*Last updated: 2026-10-01*
