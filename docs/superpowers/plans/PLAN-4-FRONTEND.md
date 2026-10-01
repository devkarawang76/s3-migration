# Plan 4: Frontend (Next.js)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Working Next.js frontend dengan App Router, auth, dan semua halaman.

**Architecture:** Next.js 14 App Router dengan route groups. Zustand untuk state management. TanStack Query untuk data fetching. Sanctum token auth.

**Tech Stack:** Next.js 14, React 18, TypeScript, Tailwind CSS, TanStack Query, Zustand

**Spec:** `docs/superpowers/specs/FRONTEND_DESIGN.md`

**UI Templates:** `docs/superpowers/plans/UI-TEMPLATES.md` (Plan 4 section)

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

### Task 4.1: Next.js project setup + Tailwind + TypeScript

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/tailwind.config.ts`
- Create: `frontend/tsconfig.json`
- Create: `frontend/next.config.js`

- [ ] **Step 1: Create Next.js project structure**
- [ ] **Step 2: Install dependencies**
- [ ] **Step 3: Configure Tailwind + TypeScript**
- [ ] **Step 4: Commit**

---

### Task 4.2: Root layout + providers

**Files:**
- Create: `frontend/src/app/layout.tsx`
- Create: `frontend/src/app/providers.tsx`
- Create: `frontend/src/app/globals.css`

- [ ] **Step 1: Create root layout**
- [ ] **Step 2: Create providers (QueryClientProvider)**
- [ ] **Step 3: Create global styles**
- [ ] **Step 4: Commit**

---

### Task 4.3: API client + Axios instance

**Files:**
- Create: `frontend/src/lib/api.ts`

- [ ] **Step 1: Create Axios instance**
- [ ] **Step 2: Add request interceptor (auth token)**
- [ ] **Step 3: Add response interceptor (401 handling)**
- [ ] **Step 4: Commit**

---

### Task 4.4: TanStack Query client

**Files:**
- Create: `frontend/src/lib/query-client.ts`

- [ ] **Step 1: Create QueryClient**
- [ ] **Step 2: Configure default options**
- [ ] **Step 3: Commit**

---

### Task 4.5: Zustand stores (auth, ui, filter)

**Files:**
- Create: `frontend/src/stores/auth-store.ts`
- Create: `frontend/src/stores/ui-store.ts`
- Create: `frontend/src/stores/filter-store.ts`

- [ ] **Step 1: Create auth store**
- [ ] **Step 2: Create UI store**
- [ ] **Step 3: Create filter store**
- [ ] **Step 4: Commit**

---

### Task 4.6: Auth hook + ProtectedRoute + middleware

**Files:**
- Create: `frontend/src/hooks/use-auth.ts`
- Create: `frontend/src/components/auth/protected-route.tsx`
- Create: `frontend/src/middleware.ts`

- [ ] **Step 1: Create useAuth hook**
- [ ] **Step 2: Create ProtectedRoute component**
- [ ] **Step 3: Create Next.js middleware**
- [ ] **Step 4: Commit**

---

### Task 4.7: Login page + auth layout

**Files:**
- Create: `frontend/src/app/(auth)/login/page.tsx`
- Create: `frontend/src/app/(auth)/layout.tsx`
- Create: `frontend/src/app/(auth)/forgot-password/page.tsx`

- [ ] **Step 1: Create auth layout**
- [ ] **Step 2: Create login page**
- [ ] **Step 3: Create forgot password page**
- [ ] **Step 4: Commit**

---

### Task 4.8: Dashboard layout + sidebar + header

**Files:**
- Create: `frontend/src/app/(dashboard)/layout.tsx`
- Create: `frontend/src/components/layout/sidebar.tsx`
- Create: `frontend/src/components/layout/header.tsx`
- Create: `frontend/src/components/layout/mobile-nav.tsx`

- [ ] **Step 1: Create dashboard layout**
- [ ] **Step 2: Create sidebar**
- [ ] **Step 3: Create header**
- [ ] **Step 4: Create mobile nav**
- [ ] **Step 5: Commit**

---

### Task 4.9: Dashboard page + widgets

**Files:**
- Create: `frontend/src/app/(dashboard)/dashboard/page.tsx`
- Create: `frontend/src/components/dashboard/stats-card.tsx`
- Create: `frontend/src/components/dashboard/ticket-chart.tsx`
- Create: `frontend/src/components/dashboard/recent-tickets.tsx`
- Create: `frontend/src/components/dashboard/notification-list.tsx`

- [ ] **Step 1: Create dashboard page**
- [ ] **Step 2: Create stats card widget**
- [ ] **Step 3: Create ticket chart widget**
- [ ] **Step 4: Create recent tickets widget**
- [ ] **Step 5: Create notification list widget**
- [ ] **Step 6: Commit**

---

### Task 4.10: Tickets pages (list, detail, new)

**Files:**
- Create: `frontend/src/app/(dashboard)/tickets/page.tsx`
- Create: `frontend/src/app/(dashboard)/tickets/[ticketId]/page.tsx`
- Create: `frontend/src/app/(dashboard)/tickets/new/page.tsx`
- Create: `frontend/src/components/tickets/ticket-table.tsx`
- Create: `frontend/src/components/tickets/ticket-form.tsx`
- Create: `frontend/src/components/tickets/ticket-detail.tsx`
- Create: `frontend/src/components/tickets/ticket-status-badge.tsx`
- Create: `frontend/src/components/tickets/ticket-timeline.tsx`
- Create: `frontend/src/components/tickets/ticket-comments.tsx`
- Create: `frontend/src/components/tickets/comment-form.tsx`
- Create: `frontend/src/components/tickets/timeline-item.tsx`
- Create: `frontend/src/components/tickets/ticket-units.tsx`
- Create: `frontend/src/components/tickets/unit-selector.tsx`
- Create: `frontend/src/components/tickets/ticket-filters.tsx`

- [ ] **Step 1: Create ticket list page**
- [ ] **Step 2: Create ticket detail page**
- [ ] **Step 3: Create ticket new page**
- [ ] **Step 4: Create ticket components**
- [ ] **Step 5: Commit**

---

### Task 4.11: Units pages (list, detail)

**Files:**
- Create: `frontend/src/app/(dashboard)/units/page.tsx`
- Create: `frontend/src/app/(dashboard)/units/[unitId]/page.tsx`
- Create: `frontend/src/components/units/unit-table.tsx`
- Create: `frontend/src/components/units/unit-form.tsx`
- Create: `frontend/src/components/units/unit-detail.tsx`
- Create: `frontend/src/components/units/warranty-badge.tsx`

- [ ] **Step 1: Create unit list page**
- [ ] **Step 2: Create unit detail page**
- [ ] **Step 3: Create unit components**
- [ ] **Step 4: Commit**

---

### Task 4.12: PM pages (list, detail, new)

**Files:**
- Create: `frontend/src/app/(dashboard)/preventive-maintenances/page.tsx`
- Create: `frontend/src/app/(dashboard)/preventive-maintenances/[pmId]/page.tsx`
- Create: `frontend/src/app/(dashboard)/preventive-maintenances/new/page.tsx`
- Create: `frontend/src/components/preventive-maintenances/pm-table.tsx`
- Create: `frontend/src/components/preventive-maintenances/pm-form.tsx`
- Create: `frontend/src/components/preventive-maintenances/pm-detail.tsx`
- Create: `frontend/src/components/preventive-maintenances/pm-progress.tsx`
- Create: `frontend/src/components/preventive-maintenances/pm-tickets.tsx`
- Create: `frontend/src/components/preventive-maintenances/sn-selector.tsx`

- [ ] **Step 1: Create PM list page**
- [ ] **Step 2: Create PM detail page**
- [ ] **Step 3: Create PM new page**
- [ ] **Step 4: Create PM components**
- [ ] **Step 5: Commit**

---

### Task 4.13: Loans, SalesOrders, Quotations, SPRF pages

**Files:**
- Create: `frontend/src/app/(dashboard)/loans/page.tsx` + detail
- Create: `frontend/src/app/(dashboard)/sales-orders/page.tsx` + detail
- Create: `frontend/src/app/(dashboard)/quotations/page.tsx` + detail
- Create: `frontend/src/app/(dashboard)/sprf/page.tsx` + detail
- Create: `frontend/src/components/loans/loan-table.tsx`
- Create: `frontend/src/components/loans/loan-form.tsx`
- Create: `frontend/src/components/loans/loan-detail.tsx`
- Create: `frontend/src/components/loans/approval-actions.tsx`

- [ ] **Step 1: Create loan pages**
- [ ] **Step 2: Create sales order pages**
- [ ] **Step 3: Create quotation pages**
- [ ] **Step 4: Create SPRF pages**
- [ ] **Step 5: Create components**
- [ ] **Step 6: Commit**

---

### Task 4.14: Shared components (ui, layout, tickets, etc.)

**Files:**
- Create: `frontend/src/components/ui/button.tsx`
- Create: `frontend/src/components/ui/input.tsx`
- Create: `frontend/src/components/ui/select.tsx`
- Create: `frontend/src/components/ui/modal.tsx`
- Create: `frontend/src/components/ui/table.tsx`
- Create: `frontend/src/components/ui/pagination.tsx`
- Create: `frontend/src/components/ui/badge.tsx`
- Create: `frontend/src/components/ui/card.tsx`
- Create: `frontend/src/components/ui/dropdown.tsx`
- Create: `frontend/src/components/ui/date-picker.tsx`
- Create: `frontend/src/components/ui/search-input.tsx`
- Create: `frontend/src/components/ui/loading-spinner.tsx`
- Create: `frontend/src/components/ui/error-boundary.tsx`
- Create: `frontend/src/components/ui/empty-state.tsx`
- Create: `frontend/src/components/ui/confirm-dialog.tsx`
- Create: `frontend/src/components/ui/toast.tsx`
- Create: `frontend/src/components/layout/breadcrumb.tsx`
- Create: `frontend/src/components/notifications/notification-bell.tsx`
- Create: `frontend/src/components/notifications/notification-dropdown.tsx`
- Create: `frontend/src/components/auth/user-menu.tsx`
- Create: `frontend/src/components/auth/user-avatar.tsx`

- [ ] **Step 1: Create UI components**
- [ ] **Step 2: Create layout components**
- [ ] **Step 3: Create notification components**
- [ ] **Step 4: Create auth components**
- [ ] **Step 5: Commit**

---

### Task 4.15: Custom hooks (useTickets, useUnits, etc.)

**Files:**
- Create: `frontend/src/hooks/use-tickets.ts`
- Create: `frontend/src/hooks/use-units.ts`
- Create: `frontend/src/hooks/use-preventive-maintenances.ts`
- Create: `frontend/src/hooks/use-loans.ts`
- Create: `frontend/src/hooks/use-debounce.ts`
- Create: `frontend/src/hooks/use-pagination.ts`
- Create: `frontend/src/hooks/use-media-query.ts`
- Create: `frontend/src/hooks/use-websocket.ts`

- [ ] **Step 1: Create all custom hooks**
- [ ] **Step 2: Commit**

---

### Task 4.16: TypeScript types

**Files:**
- Create: `frontend/src/types/api.ts`
- Create: `frontend/src/types/models.ts`
- Create: `frontend/src/types/auth.ts`
- Create: `frontend/src/types/index.ts`

- [ ] **Step 1: Create TypeScript types**
- [ ] **Step 2: Commit**

---

### Task 4.17: Zod validators

**Files:**
- Create: `frontend/src/lib/validators.ts`

- [ ] **Step 1: Create Zod schemas**
- [ ] **Step 2: Commit**

---

### Task 4.18: i18n setup + translations

**Files:**
- Create: `frontend/src/i18n/config.ts`
- Create: `frontend/src/i18n/en.json`
- Create: `frontend/src/i18n/id.json`

- [ ] **Step 1: Create i18n config**
- [ ] **Step 2: Create translations**
- [ ] **Step 3: Commit**

---

### Task 4.19: WebSocket client

**Files:**
- Create: `frontend/src/lib/websocket.ts`

- [ ] **Step 1: Create WebSocket client**
- [ ] **Step 2: Commit**

---

### Task 4.20: E2E tests

**Files:**
- Create: `frontend/tests/e2e/auth.spec.ts`
- Create: `frontend/tests/e2e/tickets.spec.ts`
- Create: `frontend/tests/e2e/units.spec.ts`
- Create: `frontend/tests/e2e/pm.spec.ts`
- Create: `frontend/tests/e2e/dashboard.spec.ts`

- [ ] **Step 1: Create E2E tests**
- [ ] **Step 2: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 4.1 | Next.js setup | 4 | 30 min |
| 4.2 | Root layout + providers | 3 | 15 min |
| 4.3 | API client | 1 | 15 min |
| 4.4 | TanStack Query client | 1 | 10 min |
| 4.5 | Zustand stores | 3 | 20 min |
| 4.6 | Auth hook + middleware | 3 | 20 min |
| 4.7 | Login page | 3 | 20 min |
| 4.8 | Dashboard layout | 4 | 30 min |
| 4.9 | Dashboard page + widgets | 5 | 30 min |
| 4.10 | Tickets pages | 14 | 60 min |
| 4.11 | Units pages | 5 | 30 min |
| 4.12 | PM pages | 8 | 45 min |
| 4.13 | Loans, SO, Quotations, SPRF | 16 | 60 min |
| 4.14 | Shared components | 25 | 90 min |
| 4.15 | Custom hooks | 8 | 45 min |
| 4.16 | TypeScript types | 4 | 20 min |
| 4.17 | Zod validators | 1 | 15 min |
| 4.18 | i18n | 3 | 30 min |
| 4.19 | WebSocket client | 1 | 20 min |
| 4.20 | E2E tests | 5 | 60 min |

**Total:** ~115 files, ~12 hours

---

*Last updated: 2026-10-01*
