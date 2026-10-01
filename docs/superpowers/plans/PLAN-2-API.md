# Plan 2: API Layer

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** RESTful API lengkap dengan Form Requests, API Resources, dan validation.

**Architecture:** API Resource Controllers dengan Sanctum auth. Form Request validation per endpoint. API Resources untuk response transformation.

**Tech Stack:** Laravel 13, Sanctum, Zod (frontend validation)

**Spec:** `docs/superpowers/specs/BACKEND_DESIGN.md`

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

### Task 2.1: AuthController (login, logout, refresh, me)

**Files:**
- Create: `backend/app/Http/Controllers/Api/AuthController.php`
- Test: `backend/tests/Feature/Api/AuthApiTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement AuthController**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.2: TicketController (CRUD + comments + timeline + units)

**Files:**
- Create: `backend/app/Http/Controllers/Api/TicketController.php`
- Test: `backend/tests/Feature/Api/TicketApiTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement TicketController**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.3: UnitController (CRUD + tickets + warranty)

**Files:**
- Create: `backend/app/Http/Controllers/Api/UnitController.php`
- Test: `backend/tests/Feature/Api/UnitApiTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement UnitController**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.4: PreventiveMaintenanceController

**Files:**
- Create: `backend/app/Http/Controllers/Api/PreventiveMaintenanceController.php`
- Test: `backend/tests/Feature/Api/PMApiTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement PreventiveMaintenanceController**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.5: LoanController, SalesOrderController, QuotationController

**Files:**
- Create: `backend/app/Http/Controllers/Api/LoanController.php`
- Create: `backend/app/Http/Controllers/Api/SalesOrderController.php`
- Create: `backend/app/Http/Controllers/Api/QuotationController.php`
- Test: `backend/tests/Feature/Api/LoanApiTest.php`
- Test: `backend/tests/Feature/Api/SalesOrderApiTest.php`
- Test: `backend/tests/Feature/Api/QuotationApiTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement controllers**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 2.6: CustomerController, StockController, EngineerController

**Files:**
- Create: `backend/app/Http/Controllers/Api/CustomerController.php`
- Create: `backend/app/Http/Controllers/Api/StockController.php`
- Create: `backend/app/Http/Controllers/Api/EngineerController.php`
- Test: `backend/tests/Feature/Api/CustomerApiTest.php`
- Test: `backend/tests/Feature/Api/StockApiTest.php`
- Test: `backend/tests/Feature/Api/EngineerApiTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement controllers**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 2.7: DashboardController, ReportController, NotificationController

**Files:**
- Create: `backend/app/Http/Controllers/Api/DashboardController.php`
- Create: `backend/app/Http/Controllers/Api/ReportController.php`
- Create: `backend/app/Http/Controllers/Api/NotificationController.php`
- Test: `backend/tests/Feature/Api/DashboardApiTest.php`
- Test: `backend/tests/Feature/Api/ReportApiTest.php`
- Test: `backend/tests/Feature/Api/NotificationApiTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement controllers**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 2.8: Form Requests (TicketRequest, UnitRequest, etc.)

**Files:**
- Create: `backend/app/Http/Requests/TicketRequest.php`
- Create: `backend/app/Http/Requests/UnitRequest.php`
- Create: `backend/app/Http/Requests/LoanRequest.php`
- Create: `backend/app/Http/Requests/SalesOrderRequest.php`
- Create: `backend/app/Http/Requests/QuotationRequest.php`
- Create: `backend/app/Http/Requests/SprfRequest.php`
- Test: `backend/tests/Unit/Requests/FormRequestTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement Form Requests**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.9: API Resources (TicketResource, UnitResource, etc.)

**Files:**
- Create: `backend/app/Http/Resources/TicketResource.php`
- Create: `backend/app/Http/Resources/UnitResource.php`
- Create: `backend/app/Http/Resources/LoanResource.php`
- Create: `backend/app/Http/Resources/SalesOrderResource.php`
- Create: `backend/app/Http/Resources/QuotationResource.php`
- Create: `backend/app/Http/Resources/SprfResource.php`
- Create: `backend/app/Http/Resources/CustomerResource.php`
- Create: `backend/app/Http/Resources/StockResource.php`
- Test: `backend/tests/Unit/Resources/ApiResourceTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement API Resources**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.10: Services (TicketService, PreventiveMaintenanceService)

**Files:**
- Create: `backend/app/Services/TicketService.php`
- Create: `backend/app/Services/PreventiveMaintenanceService.php`
- Test: `backend/tests/Unit/Services/TicketServiceTest.php`
- Test: `backend/tests/Unit/Services/PreventiveMaintenanceServiceTest.php`

- [ ] **Step 1: Write the failing tests**
- [ ] **Step 2: Run tests to verify they fail**
- [ ] **Step 3: Implement services**
- [ ] **Step 4: Run tests to verify they pass**
- [ ] **Step 5: Commit**

---

### Task 2.11: Events + Listeners (TicketStatusChanged, etc.)

**Files:**
- Create: `backend/app/Events/TicketStatusChanged.php`
- Create: `backend/app/Events/TicketCommentAdded.php`
- Create: `backend/app/Listeners/LogTicketStatusChange.php`
- Test: `backend/tests/Unit/Events/EventTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement events + listeners**
- [ ] **Step 4: Run test to verify it passes**
- [ ] **Step 5: Commit**

---

### Task 2.12: Feature tests untuk API

**Files:**
- Create: `backend/tests/Feature/Api/TicketApiTest.php`
- Create: `backend/tests/Feature/Api/UnitApiTest.php`
- Create: `backend/tests/Feature/Api/AuthApiTest.php`
- Create: `backend/tests/Feature/Api/PMApiTest.php`
- Create: `backend/tests/Feature/Api/HealthApiTest.php`

- [ ] **Step 1: Write comprehensive feature tests**
- [ ] **Step 2: Run all tests**
- [ ] **Step 3: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 2.1 | AuthController | 2 | 30 min |
| 2.2 | TicketController | 2 | 60 min |
| 2.3 | UnitController | 2 | 30 min |
| 2.4 | PreventiveMaintenanceController | 2 | 30 min |
| 2.5 | LoanController, SalesOrderController, QuotationController | 6 | 45 min |
| 2.6 | CustomerController, StockController, EngineerController | 6 | 30 min |
| 2.7 | DashboardController, ReportController, NotificationController | 6 | 30 min |
| 2.8 | Form Requests | 7 | 30 min |
| 2.9 | API Resources | 9 | 30 min |
| 2.10 | Services | 4 | 45 min |
| 2.11 | Events + Listeners | 4 | 20 min |
| 2.12 | Feature tests | 5 | 60 min |

**Total:** ~55 files, ~7 hours

---

*Last updated: 2026-10-01*
