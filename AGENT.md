# S3 System Migration — Agent Guide

## Project Overview

Migrasi legacy PHP 5.6 S3 System (Fujitsu Support & Service) ke arsitektur modern:
- **Backend:** Laravel 13 + Filament 5
- **Frontend:** Next.js 14 (App Router)
- **Database:** MySQL 8.0 (dual-database strategy)
- **Cache:** Redis
- **Deployment:** Docker + GitHub Actions

## Directory Structure

```
s3-migration/
├── AGENT.md                          # File ini
├── docs/
│   ├── superpowers/
│   │   ├── plans/
│   │   │   └── 2026-09-30-s3-migration.md    # Master implementation plan
│   │   └── specs/
│   │       ├── AUDIT_REPORT.md              # Audit legacy system
│   │       ├── MODEL_SPEC.md                # Eloquent models + RBAC
│   │       ├── MIGRATION_SPEC.md            # 24 tabel migration + FK alignment
│   │       ├── BACKEND_DESIGN.md            # Laravel API + Filament
│   │       ├── FRONTEND_DESIGN.md           # Next.js App Router
│   │       ├── ARCHITECTURE_FLOW.md         # System flow + state machine
│   │       └── DEPLOYMENT_STRATEGY.md       # Deployment + CI/CD
│   └── runbook/
│       └── (runbook files)
├── backend/                          # Laravel application
│   ├── app/
│   │   ├── Console/Commands/          # Migration & sync commands
│   │   ├── Events/                   # TicketStatusChanged, etc.
│   │   ├── Exceptions/               # Handler
│   │   ├── Filament/
│   │   │   ├── Pages/                # Dashboard, SlaReport, etc.
│   │   │   ├── Resources/            # TicketResource, UnitResource, etc.
│   │   │   └── Widgets/              # StatsOverview, TicketChart, etc.
│   │   ├── Http/
│   │   │   ├── Controllers/Api/      # API controllers
│   │   │   ├── Middleware/           # EnsureUserActive
│   │   │   ├── Requests/             # Form Requests
│   │   │   └── Resources/            # API Resources
│   │   ├── Listeners/                # LogTicketStatusChange
│   │   ├── Models/                   # Eloquent models
│   │   ├── Providers/                # AuthServiceProvider, AdminPanelProvider
│   │   └── Services/                 # TicketService, PreventiveMaintenanceService
│   ├── config/
│   │   ├── auth.php                  # Guards + providers
│   │   ├── database.php              # Dual database connections
│   │   ├── features.php              # Feature flags
│   │   ├── filament.php              # Filament config
│   │   ├── logging.php               # Logging channels
│   │   ├── sanctum.php               # Sanctum config
│   │   └── cors.php                  # CORS config
│   ├── database/
│   │   ├── migrations/               # All migrations
│   │   └── seeders/                  # RoleSeeder, AdminSeeder, etc.
│   ├── docker/
│   │   ├── nginx.conf
│   │   └── supervisord.conf
│   ├── routes/
│   │   ├── api.php                   # API routes
│   │   ├── channels.php              # WebSocket channels
│   │   └── console.php               # Console routes
│   ├── tests/
│   │   ├── Unit/Models/              # Model unit tests
│   │   ├── Feature/Api/              # API feature tests
│   │   ├── Feature/Filament/         # Filament feature tests
│   │   └── Feature/Migration/        # Migration tests
│   ├── .env.example
│   ├── composer.json
│   ├── Dockerfile
│   └── docker-compose.yml
├── frontend/                         # Next.js application
│   ├── src/
│   │   ├── app/
│   │   │   ├── (auth)/                # Login, forgot-password
│   │   │   ├── (dashboard)/           # Dashboard, tickets, units, etc.
│   │   │   ├── layout.tsx
│   │   │   ├── page.tsx
│   │   │   ├── globals.css
│   │   │   ├── error.tsx
│   │   │   └── not-found.tsx
│   │   ├── components/
│   │   │   ├── ui/                    # Button, Input, Modal, etc.
│   │   │   ├── layout/                # Sidebar, Header, etc.
│   │   │   ├── tickets/               # TicketTable, TicketForm, etc.
│   │   │   ├── units/                 # UnitTable, UnitForm, etc.
│   │   │   ├── preventive-maintenances/ # PMTable, PMForm, etc.
│   │   │   ├── loans/                 # LoanTable, LoanForm, etc.
│   │   │   ├── dashboard/             # StatsCard, TicketChart, etc.
│   │   │   ├── notifications/         # NotificationBell, etc.
│   │   │   └── auth/                  # LoginForm, ProtectedRoute, etc.
│   │   ├── hooks/                     # useAuth, useTickets, etc.
│   │   ├── lib/                       # api.ts, query-client.ts, validators.ts
│   │   ├── stores/                    # auth-store.ts, ui-store.ts, filter-store.ts
│   │   ├── types/                     # api.ts, models.ts, auth.ts
│   │   ├── i18n/                      # en.json, id.json, config.ts
│   │   └── middleware.ts              # Auth guard
│   ├── public/
│   ├── .env.example
│   ├── next.config.js
│   ├── tailwind.config.ts
│   ├── tsconfig.json
│   └── package.json
└── deploy/
    ├── .github/workflows/deploy.yml
    ├── deploy.sh
    ├── backup.sh
    └── emergency-rollback.sh
```

## Key Files Reference

| File | Purpose |
|---|---|
| `docs/superpowers/specs/AUDIT_REPORT.md` | Audit legacy system |
| `docs/superpowers/specs/MODEL_SPEC.md` | Eloquent models + RBAC |
| `docs/superpowers/specs/BACKEND_DESIGN.md` | Laravel API + Filament |
| `docs/superpowers/specs/FRONTEND_DESIGN.md` | Next.js App Router |
| `docs/superpowers/specs/DEPLOYMENT_STRATEGY.md` | Deployment + CI/CD |
| `docs/superpowers/plans/2026-09-30-s3-migration.md` | Master implementation plan |

## Development Commands

### Backend (Laravel)

```bash
# Setup
composer install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --database=mysql_new --force
php artisan db:seed

# Development
php artisan serve
php artisan queue:work
php artisan schedule:work

# Testing
php artisan test
php artisan test --parallel

# Migration
php artisan migrate:legacy-tickets
php artisan sync:legacy --table=all
```

### Frontend (Next.js)

```bash
# Setup
npm install

# Development
npm run dev

# Build
npm run build

# Production
npm start
```

### Docker

```bash
# Build and start
docker-compose up -d --build

# Logs
docker-compose logs -f app

# Migrations
docker-compose exec app php artisan migrate --force

# Shell
docker-compose exec app bash
```

## Feature Flags

```php
// config/features.php
'FEATURE_NEW_TICKET' => env('FEATURE_NEW_TICKET', false),
'FEATURE_NEW_LOAN' => env('FEATURE_NEW_LOAN', false),
'FEATURE_NEW_DASHBOARD' => env('FEATURE_NEW_DASHBOARD', false),
'FEATURE_DUAL_WRITE' => env('FEATURE_DUAL_WRITE', false),
```

## Database Connections

| Connection | Database | Purpose |
|---|---|---|
| `mysql` | s3Prod | Legacy (read-only) |
| `mysql_new` | s3_erp | New normalized schema |
| `pgsql` | erp_data | ERP data (PostgreSQL) |

## Authentication

| Guard | Provider | Usage |
|---|---|---|
| `web` | `legacy` | Legacy user auth (API) |
| `sanctum` | `legacy` | API tokens (Next.js) |
| `admin` | `admins` | Filament admin panel |

## API Endpoints

| Endpoint | Method | Description |
|---|---|---|
| `/api/v1/auth/login` | POST | Login |
| `/api/v1/auth/logout` | POST | Logout |
| `/api/v1/auth/me` | GET | Current user |
| `/api/v1/tickets` | GET/POST | List/Create tickets |
| `/api/v1/tickets/{id}` | GET/PUT/DELETE | Ticket detail |
| `/api/v1/tickets/{id}/comments` | GET/POST | Ticket comments |
| `/api/v1/tickets/{id}/timeline` | GET | Ticket timeline |
| `/api/v1/tickets/{id}/units` | GET/POST | Ticket units |
| `/api/v1/units` | GET/POST | List/Create units |
| `/api/v1/preventive-maintenances` | GET/POST | List/Create PM |
| `/api/v1/loans` | GET/POST | List/Create loans |
| `/api/v1/customers` | GET/POST | List/Create customers |
| `/api/v1/dashboard/summary` | GET | Dashboard summary |
| `/api/v1/reports/{type}` | GET | Reports |

## Important Notes

- **Legacy database is read-only** — no INSERT/UPDATE/DELETE
- **SQL injection fix di legacy di-skip** — staging mode
- **Password migration** — MD5 → bcrypt on first login
- **Backward compatibility** — `v_legacy_tickets` view
- **Foreign key alignment** — `ticket_id` string → bigint
- **Feature flags** — gradual rollout per module

## Execution Order

1. Plan 1: Foundation (models, auth, migrations)
2. Plan 2: API Layer (controllers, resources, validation)
3. Plan 3: Filament Admin (resources, pages, widgets)
4. Plan 4: Frontend (Next.js, components, hooks)
5. Plan 5: Data Migration (sync commands, views)
6. Plan 6: Deployment (Docker, CI/CD, monitoring)

---

*Last updated: 2026-09-30*
