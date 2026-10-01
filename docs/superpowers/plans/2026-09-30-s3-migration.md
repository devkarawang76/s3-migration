# S3 System Migration — Master Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate legacy PHP 5.6 S3 System to Laravel 13 + Filament 5 + Next.js 14 with zero data loss and backward compatibility.

**Architecture:** Dual-database strategy (legacy read-only + new normalized). Custom User Provider untuk MD5 → bcrypt migration. RBAC via spatie/laravel-permission. RESTful API dengan Sanctum auth. Filament admin panel. Next.js App Router frontend.

**Tech Stack:** PHP 8.2, Laravel 13, MySQL 8.0, Redis, Filament 5, Next.js 14, React 18, TypeScript, Tailwind CSS, TanStack Query, Zustand, Docker

**Spec:** `docs/superpowers/specs/`

---

## Global Constraints

- **Legacy database is read-only** — no INSERT/UPDATE/DELETE pada tabel legacy
- **SQL injection fix di legacy di-skip** — aplikasi sedang dijadikan staging
- **Password migration** — MD5 di-rehash ke bcrypt saat login pertama
- **Backward compatibility** — view `v_legacy_tickets` untuk legacy system
- **Feature flags** — gradual rollout per module via `config/features.php`
- **Foreign key alignment** — `ticket_id` di tabel legacy adalah string, di tabel baru adalah bigint

---

## Sub-Plans Overview

| Plan | Scope | Deliverable | Status |
|---|---|---|---|
| **Plan 1: Foundation** | Laravel setup, models, migrations, auth | Working API dengan auth + CRUD dasar | ⏳ Pending |
| **Plan 2: API Layer** | Controllers, Form Requests, API Resources | RESTful API lengkap | ⏳ Pending |
| **Plan 3: Filament Admin** | Filament Resources, Pages, Widgets | Admin panel functional | ⏳ Pending |
| **Plan 4: Frontend** | Next.js App Router, components, hooks | Working frontend | ⏳ Pending |
| **Plan 5: Data Migration** | Migration scripts, sync commands | Data legacy → new schema | ⏳ Pending |
| **Plan 6: Deployment** | Docker, CI/CD, monitoring | Production-ready deployment | ⏳ Pending |

---

## Plan 1: Foundation

**Goal:** Setup Laravel project dengan models, migrations, dan authentication yang compatible dengan legacy system.

**Architecture:** Laravel 13 + MySQL dual-connection (legacy read-only + new normalized). Custom User Provider untuk MD5 → bcrypt migration. RBAC via spatie/laravel-permission.

**Tech Stack:** PHP 8.2, Laravel 13, MySQL 8.0, Redis, Sanctum

**Spec:** `docs/superpowers/specs/MODEL_SPEC.md`, `docs/superpowers/specs/BACKEND_DESIGN.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 1.1 | Laravel project setup + dual database config | `config/database.php`, `.env` |
| 1.2 | Base model + LegacyModel trait | `app/Models/LegacyModel.php` |
| 1.3 | User models (LegacyUser, User, Admin) | `app/Models/LegacyUser.php`, `app/Models/User.php`, `app/Models/Admin.php` |
| 1.4 | Custom User Provider (MD5 → bcrypt) | `app/Providers/LegacyUserProvider.php` |
| 1.5 | Auth config + guards | `config/auth.php` |
| 1.6 | RBAC models (Role, Permission, pivots) | `app/Models/Role.php`, `app/Models/Permission.php` |
| 1.7 | Core models (Ticket, Unit, Customer, etc.) | `app/Models/Ticket.php`, `app/Models/Unit.php`, `app/Models/Customer.php` |
| 1.8 | PM models (PreventiveMaintenance, PmTicketLink) | `app/Models/PreventiveMaintenance.php` |
| 1.9 | Supporting models (TicketComment, TicketTimeline, etc.) | `app/Models/TicketComment.php`, `app/Models/TicketTimeline.php` |
| 1.10 | Migrations untuk semua tabel baru | `database/migrations/*.php` |
| 1.11 | Seeders (roles, permissions, admin, enum mappings) | `database/seeders/*.php` |
| 1.12 | Unit tests untuk models | `tests/Unit/Models/*.php` |

---

## Plan 2: API Layer

**Goal:** RESTful API lengkap dengan Form Requests, API Resources, dan validation.

**Architecture:** API Resource Controllers dengan Sanctum auth. Form Request validation per endpoint. API Resources untuk response transformation.

**Tech Stack:** Laravel 13, Sanctum, Zod (frontend validation)

**Spec:** `docs/superpowers/specs/BACKEND_DESIGN.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 2.1 | AuthController (login, logout, refresh, me) | `app/Http/Controllers/Api/AuthController.php` |
| 2.2 | TicketController (CRUD + comments + timeline + units) | `app/Http/Controllers/Api/TicketController.php` |
| 2.3 | UnitController (CRUD + tickets + warranty) | `app/Http/Controllers/Api/UnitController.php` |
| 2.4 | PreventiveMaintenanceController | `app/Http/Controllers/Api/PreventiveMaintenanceController.php` |
| 2.5 | LoanController, SalesOrderController, QuotationController | `app/Http/Controllers/Api/*.php` |
| 2.6 | CustomerController, StockController, EngineerController | `app/Http/Controllers/Api/*.php` |
| 2.7 | DashboardController, ReportController, NotificationController | `app/Http/Controllers/Api/*.php` |
| 2.8 | Form Requests (TicketRequest, UnitRequest, etc.) | `app/Http/Requests/*.php` |
| 2.9 | API Resources (TicketResource, UnitResource, etc.) | `app/Http/Resources/*.php` |
| 2.10 | Services (TicketService, PreventiveMaintenanceService) | `app/Services/*.php` |
| 2.11 | Events + Listeners (TicketStatusChanged, etc.) | `app/Events/*.php`, `app/Listeners/*.php` |
| 2.12 | Feature tests untuk API | `tests/Feature/Api/*.php` |

---

## Plan 3: Filament Admin

**Goal:** Admin panel functional dengan Filament Resources untuk semua modul.

**Architecture:** Filament 5 dengan custom admin guard. Resources untuk setiap modul bisnis. Custom pages untuk dashboard dan reports.

**Tech Stack:** Filament 5, Laravel 13

**Spec:** `docs/superpowers/specs/BACKEND_DESIGN.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 3.1 | Filament setup + AdminPanelProvider | `app/Providers/Filament/AdminPanelProvider.php` |
| 3.2 | TicketResource + Pages | `app/Filament/Resources/TicketResource.php` |
| 3.3 | UnitResource + Pages | `app/Filament/Resources/UnitResource.php` |
| 3.4 | PreventiveMaintenanceResource + Pages | `app/Filament/Resources/PreventiveMaintenanceResource.php` |
| 3.5 | LoanResource, SalesOrderResource, QuotationResource | `app/Filament/Resources/*.php` |
| 3.6 | CustomerResource, StockResource, EngineerResource | `app/Filament/Resources/*.php` |
| 3.7 | UserResource, RoleResource | `app/Filament/Resources/*.php` |
| 3.8 | Custom Pages (Dashboard, SlaReport, LoanReport) | `app/Filament/Pages/*.php` |
| 3.9 | Custom Widgets (StatsOverview, TicketChart, etc.) | `app/Filament/Widgets/*.php` |
| 3.10 | Feature tests untuk Filament | `tests/Feature/Filament/*.php` |

---

## Plan 4: Frontend (Next.js)

**Goal:** Working Next.js frontend dengan App Router, auth, dan semua halaman.

**Architecture:** Next.js 14 App Router dengan route groups. Zustand untuk state management. TanStack Query untuk data fetching. Sanctum token auth.

**Tech Stack:** Next.js 14, React 18, TypeScript, Tailwind CSS, TanStack Query, Zustand

**Spec:** `docs/superpowers/specs/FRONTEND_DESIGN.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 4.1 | Next.js project setup + Tailwind + TypeScript | `package.json`, `tailwind.config.ts`, `tsconfig.json` |
| 4.2 | Root layout + providers | `src/app/layout.tsx`, `src/app/providers.tsx` |
| 4.3 | API client + Axios instance | `src/lib/api.ts` |
| 4.4 | TanStack Query client | `src/lib/query-client.ts` |
| 4.5 | Zustand stores (auth, ui, filter) | `src/stores/*.ts` |
| 4.6 | Auth hook + ProtectedRoute + middleware | `src/hooks/use-auth.ts`, `src/components/auth/protected-route.tsx` |
| 4.7 | Login page + auth layout | `src/app/(auth)/login/page.tsx` |
| 4.8 | Dashboard layout + sidebar + header | `src/app/(dashboard)/layout.tsx` |
| 4.9 | Dashboard page + widgets | `src/app/(dashboard)/dashboard/page.tsx` |
| 4.10 | Tickets pages (list, detail, new) | `src/app/(dashboard)/tickets/**/*.tsx` |
| 4.11 | Units pages (list, detail) | `src/app/(dashboard)/units/**/*.tsx` |
| 4.12 | PM pages (list, detail, new) | `src/app/(dashboard)/preventive-maintenances/**/*.tsx` |
| 4.13 | Loans, SalesOrders, Quotations, SPRF pages | `src/app/(dashboard)/**/*.tsx` |
| 4.14 | Shared components (ui, layout, tickets, etc.) | `src/components/**/*.tsx` |
| 4.15 | Custom hooks (useTickets, useUnits, etc.) | `src/hooks/*.ts` |
| 4.16 | TypeScript types | `src/types/*.ts` |
| 4.17 | Zod validators | `src/lib/validators.ts` |
| 4.18 | i18n setup + translations | `src/i18n/*.json` |
| 4.19 | WebSocket client | `src/lib/websocket.ts` |
| 4.20 | E2E tests | `tests/e2e/*.spec.ts` |

---

## Plan 5: Data Migration

**Goal:** Migrasi data dari legacy ke new schema tanpa data loss.

**Architecture:** Migration commands dengan chunked processing. Backward compatibility views. Dual-write strategy untuk zero downtime.

**Tech Stack:** Laravel 13, MySQL 8.0

**Spec:** `docs/superpowers/specs/MODEL_SPEC.md`, `docs/superpowers/specs/DEPLOYMENT_STRATEGY.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 5.1 | Migration commands (migrate:legacy-tickets, sync:legacy) | `app/Console/Commands/*.php` |
| 5.2 | LegacySyncService | `app/Services/LegacySyncService.php` |
| 5.3 | Backward compatibility views | `database/migrations/*_create_views.php` |
| 5.4 | Data verification commands | `app/Console/Commands/DbVerifyCommand.php` |
| 5.5 | Feature tests untuk migration | `tests/Feature/Migration/*.php` |

---

## Plan 6: Deployment

**Goal:** Production-ready deployment dengan Docker, CI/CD, monitoring.

**Architecture:** Docker containers untuk app, queue, scheduler. GitHub Actions CI/CD. Sentry + New Relic monitoring. Cloudflare CDN.

**Tech Stack:** Docker, GitHub Actions, Cloudflare, Sentry, New Relic

**Spec:** `docs/superpowers/specs/DEPLOYMENT_STRATEGY.md`

### Tasks

| Task | Description | Files |
|---|---|---|
| 6.1 | Dockerfile + docker-compose.yml | `Dockerfile`, `docker-compose.yml` |
| 6.2 | GitHub Actions workflow | `.github/workflows/deploy.yml` |
| 6.3 | Deployment script | `deploy.sh` |
| 6.4 | Nginx config + SSL | `docker/nginx.conf` |
| 6.5 | Supervisor config | `docker/supervisord.conf` |
| 6.6 | Monitoring setup (Sentry, New Relic) | `config/sentry.php` |
| 6.7 | Backup script | `backup.sh` |
| 6.8 | Runbook + troubleshooting guide | `docs/runbook.md` |
| 6.9 | Load testing | `tests/load/*.php` |

---

## Execution Order

```
Plan 1: Foundation
    ↓
Plan 2: API Layer
    ↓
Plan 3: Filament Admin
    ↓
Plan 4: Frontend
    ↓
Plan 5: Data Migration
    ↓
Plan 6: Deployment
```

---

## Notes

- Setiap plan menghasilkan software yang bisa diuji secara independen
- Plan 1 adalah fondasi — semua plan lain bergantung pada models dan auth
- Feature flags mengontrol gradual rollout per module
- Backward compatibility views memastikan legacy system tetap bisa membaca data
- SQL injection fix di legacy di-skip (staging mode)

---

*Plan ini dapat dilanjutkan dengan detail step-by-step TDD untuk setiap plan.*
