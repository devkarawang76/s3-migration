# Plan 6: Deployment

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Production-ready deployment dengan Docker, CI/CD, monitoring.

**Architecture:** Docker containers untuk app, queue, scheduler. GitHub Actions CI/CD. Sentry + New Relic monitoring. Cloudflare CDN.

**Tech Stack:** Docker, GitHub Actions, Cloudflare, Sentry, New Relic

**Spec:** `docs/superpowers/specs/DEPLOYMENT_STRATEGY.md`

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

### Task 6.1: Dockerfile + docker-compose.yml

**Files:**
- Create: `backend/Dockerfile`
- Create: `backend/docker-compose.yml`
- Create: `backend/docker/nginx.conf`
- Create: `backend/docker/supervisord.conf`

- [ ] **Step 1: Create Dockerfile**
- [ ] **Step 2: Create docker-compose.yml**
- [ ] **Step 3: Create nginx config**
- [ ] **Step 4: Create supervisor config**
- [ ] **Step 5: Commit**

---

### Task 6.2: GitHub Actions workflow

**Files:**
- Create: `deploy/.github/workflows/deploy.yml`

- [ ] **Step 1: Create GitHub Actions workflow**
- [ ] **Step 2: Commit**

---

### Task 6.3: Deployment script

**Files:**
- Create: `deploy/deploy.sh`

- [ ] **Step 1: Create deployment script**
- [ ] **Step 2: Commit**

---

### Task 6.4: Nginx config + SSL

**Files:**
- Create: `deploy/nginx/ssl.conf`
- Create: `deploy/nginx/deploy.sh`

- [ ] **Step 1: Create SSL config**
- [ ] **Step 2: Create SSL deployment script**
- [ ] **Step 3: Commit**

---

### Task 6.5: Supervisor config

**Files:**
- Create: `deploy/supervisor/laravel-worker.conf`
- Create: `deploy/supervisor/laravel-scheduler.conf`

- [ ] **Step 1: Create supervisor configs**
- [ ] **Step 2: Commit**

---

### Task 6.6: Monitoring setup (Sentry, New Relic)

**Files:**
- Create: `backend/config/sentry.php`
- Create: `deploy/monitoring/newrelic.ini`

- [ ] **Step 1: Create Sentry config**
- [ ] **Step 2: Create New Relic config**
- [ ] **Step 3: Commit**

---

### Task 6.7: Backup script

**Files:**
- Create: `deploy/backup.sh`

- [ ] **Step 1: Create backup script**
- [ ] **Step 2: Commit**

---

### Task 6.8: Runbook + troubleshooting guide

**Files:**
- Create: `docs/runbook/README.md`
- Create: `docs/runbook/troubleshooting.md`
- Create: `docs/runbook/common-operations.md`

- [ ] **Step 1: Create runbook**
- [ ] **Step 2: Create troubleshooting guide**
- [ ] **Step 3: Create common operations guide**
- [ ] **Step 4: Commit**

---

### Task 6.9: Load testing

**Files:**
- Create: `backend/tests/load/ApiLoadTest.php`
- Create: `backend/tests/load/DatabaseLoadTest.php`

- [ ] **Step 1: Create load tests**
- [ ] **Step 2: Run load tests**
- [ ] **Step 3: Commit**

---

## Summary

| Task | Description | Files | Est. |
|---|---|---|---|
| 6.1 | Dockerfile + docker-compose | 4 | 30 min |
| 6.2 | GitHub Actions | 1 | 20 min |
| 6.3 | Deployment script | 1 | 15 min |
| 6.4 | Nginx + SSL | 2 | 20 min |
| 6.5 | Supervisor config | 2 | 10 min |
| 6.6 | Monitoring setup | 2 | 20 min |
| 6.7 | Backup script | 1 | 15 min |
| 6.8 | Runbook | 3 | 30 min |
| 6.9 | Load testing | 2 | 30 min |

**Total:** ~18 files, ~3 hours

---

*Last updated: 2026-10-01*
