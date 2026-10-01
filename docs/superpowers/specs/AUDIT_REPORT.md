# Audit Lengkap: FUJITSU Support & Service System (S3)

**Audit Date:** 2026-09-30
**Project:** Fujitsu Indonesia Service Portal (find-service.co.id)
**Location:** `/home/admins/code_mirror/`
**Database:** MySQL (s3Prod, inoerp) + PostgreSQL (ERP data)

---

## Executive Summary

Aplikasi legacy PHP 5.6 untuk manajemen service & support Fujitsu Indonesia. **1,784 file PHP**, **190 tabel database**, **29 modul bisnis**, **10 cron jobs**. Menggunakan `mysql_*` (deprecated), MD5 passwords, dan SQL injection vulnerabilities yang critical.

---

## 1. Arsitektur & Flow

### Entry Points

| Frontend | File | Routing | Auth |
|---|---|---|---|
| **Desktop** | `/index.php` | `?code=` (encrypted) | Session |
| **Mobile** | `/m/index.php` | File-based | Session |
| **Admin** | `/S3/pages/form.php` → `controller.php` | `?page=` (encrypted) | Session + DB |
| **External** | `/S3/external/pages/` | File-based | Session |

### Bootstrap Chain (Admin)
```
form.php → dist/encrypt.php → dist/panel.php (DB)
  → session_start() → license check → session validation
  → dist/controller.php → decrypt(?page=) → module file
```

### Routing Mechanism
- **Encrypted page names**: `decrypt($_GET['page'], "adel069")` — custom character-shift cipher + base64
- **60+ module routes** via if/else chain di `controller.php`
- **No framework router** — pure PHP includes

### Architecture Diagram
```
┌─────────────────────────────────────────────────────────────────┐
│                        Apache Server                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  /index.php ──────────► Desktop Frontend (customer-facing)        │
│       │                                                          │
│       ├──► config/koneksi.php (DB: 172.16.1.2/s3Prod)           │
│       ├──► Mobile_Detect.php → redirect to /m/ if mobile        │
│       └──► decrypt(?code=) → include HTML page                  │
│                                                                  │
│  /m/index.php ────────► Mobile Frontend (static HTML)            │
│       │                                                          │
│       ├──► view_asset.php (Mobile_Detect + DB)                   │
│       └──► ticket_tracking.php (Mobile_Detect + DB)             │
│                                                                  │
│  /S3/pages/form.php ──► Admin Panel Front Controller             │
│       │                                                          │
│       ├──► dist/encrypt.php → dist/panel.php (DB)               │
│       ├──► License check (php_uname)                            │
│       ├──► Session validation (users + internal_log tables)     │
│       └──► dist/controller.php                                   │
│              │                                                   │
│              └──► decrypt(?page=) → include module file         │
│                     (60+ module routes)                          │
│                                                                  │
│  /S3/external/ ──────► External Customer Portal                 │
│       │                                                          │
│       ├──► login.php → redirect to pages/                       │
│       └──► pages/request_maintenance.php (session-validated)    │
│                                                                  │
│  /adminweb/ ──────────► Admin Web Assets (CSS/JS/lib)            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Authentication & Session

### Login Flow
```
login_dev.php → MD5(password) → 2FA email → verification.php → session created
```

### Session Variables (24 vars)
| Variable | Purpose |
|---|---|
| `user-session` | Username (primary auth flag) |
| `session-login` | Random session token |
| `user-name`, `user-emp`, `user-photo` | Profile data |
| `rule_user`, `multirule`, `rule_name` | Role/permission |
| `user-office`, `level_user`, `eng_id` | Office & level |
| `start` | Timeout timestamp |

### Password Hashing: **MD5 (unsalted)** — CRITICAL
```php
// login_dev.php:160
$query = "SELECT * FROM users WHERE username='$username' AND password=md5('$password') AND blokir='N'";
```

### Role System
- `users.role` → primary role
- `user_multirule` → multiple roles per user
- `rule_user_menu` → menu access per role
- `delegate_rule` → temporary role delegation

---

## 3. Modul & Fitur (29 modul)

### Core Business Modules

| Modul | Purpose | Tables |
|---|---|---|
| **ticket** | Service ticket (hardware + software) | `ticket_list`, `ticket_list_soft`, `ticket_history_status`, `ticket_request_part` |
| **unit** | Unit/asset registration | `unit_customer`, `unit_type`, `model_type` |
| **loan** | Demo unit & asset loan | `loan_list`, `ticket_request_part` |
| **asset** | Asset tracking & delivery | `asset_data`, `asset_loan`, `ticket_loan` |
| **customer** | Customer master data | `customer_list` |
| **sales_order** | Sales order processing | `sales_order_list`, `sales_order_detail` |
| **quotation** | Quotation & approval | `quotation_list`, `quotation_detail` |
| **stock** | Inventory management | `stock_logistic`, `comp_part` |
| **approval** | Multi-level approval workflows | `ticket_request_part`, `quotation_list`, `sales_order_list`, `sprf_list` |
| **sprf** | Spare Part Request Form | `sprf_list` |
| **part_arrival** | Part receiving | `part_arrival` |
| **delivery_instruction** | Delivery tracking | `delivery_instruction` |
| **call_log** | Customer call logging | `call_log` |
| **token** | Service token management | `token` |
| **prev_mtcs** | Preventive maintenance | `prev_mtcs` |
| **master_data** | Master data (customer, engineer, supplier) | `customer_list`, `engineer`, `supplier` |
| **hsk** | Hardware Service Pack | `ticket_list` |
| **report** | Reporting & analytics | various |
| **notification** | Notifications | `notification` |
| **delegate** | User delegation | `delegate_rule` |

### Cron Jobs (10 jobs)
| Job | Purpose |
|---|---|
| `cron_kpi_ticket.php` | KPI metrics calculation |
| `cron_loan_demo_unit_expire.php` | Process expired demo loans |
| `cron_loan_demo_unit_will_expire.php` | Notify upcoming expirations |
| `cron_reminder_approval_loan_demo_unit.php` | Approval reminders |
| `cron_warranty_information*.php` (5 variants) | Warranty reports |
| `test_kpi.php` | KPI testing |

---

## 4. Keamanan & Legacy Quirks

### CRITICAL Vulnerabilities

| # | Vulnerability | Evidence | Impact |
|---|---|---|---|
| 1 | **SQL Injection via `$_GET['sql']`** | `view_paths.php:9` — `$sql = $_GET['sql']` | Full DB compromise |
| 2 | **SQL Injection di auth** | `login_dev.php:160` — raw `$_POST` interpolation | Auth bypass |
| 3 | **MD5 passwords** | 8+ files using `md5($password)` | Rainbow table attack |
| 4 | **Hardcoded credentials** | `koneksi.php:5` — `"fid123!!"` | DB leak via source |
| 5 | **Session fixation** | `login_dev.php:37` — predictable session name | Session hijacking |
| 6 | **No CSRF protection** | All forms | Cross-site request forgery |
| 7 | **2FA brute-force** | 6-digit code, 30min expiry, no rate limit | Account takeover |
| 8 | **`mysql_prep()` returns raw input** | `functions.inc:780` — `return $value;` | No sanitization |

### HIGH Vulnerabilities

| # | Vulnerability | Evidence |
|---|---|---|
| 9 | **Inconsistent auth checks** | `controller.php` only checks `user-session`, not `session-login` |
| 10 | **Password change without old password** | `save_username.php:42-96` |
| 11 | **SQL injection in cron jobs** | `cron_reminder_approval_loan_demo_unit.php:318` |
| 12 | **Delegate rule logic flaw** | `_login.php:235` — `expired_date <= NOW()` |
| 13 | **GROUP BY injection** | `json_select.php:215`, `json_search.php:216` |

### Database Credentials (Exposed)
| File | Server | Username | Password | Database |
|---|---|---|---|---|
| `config/koneksi.php` | 172.16.1.2 | admins | `fid123!!` | s3Prod |
| `S3/dist/panel.php` | 172.16.1.2 | admins | `fid123!!` | s3Prod |
| `dbsettings.inc` | localhost | root | `arif069` | inoerp |
| `login_dev.php:196` | — | admins | `fid123!!` | s3Prod |
| `login_dev.php:203` | — | `g07\callcenter` | `abc123!!` | — |

---

## 5. Database Schema

### Core Tables (MySQL — 190 tables total)
| Table | Purpose |
|---|---|
| `ticket_list` | Hardware service tickets |
| `ticket_list_soft` | Software service tickets |
| `ticket_history_status` | Ticket status history |
| `ticket_request_part` | Part requests |
| `unit_customer` | Customer unit registration |
| `loan_list` | Demo unit loans |
| `sales_order_list` | Sales orders |
| `quotation_list` | Quotations |
| `sprf_list` | Spare Part Request Forms |
| `stock_logistic` | Inventory stock |
| `customer_list` | Customer master data |
| `users` | System users |
| `internal_log` | Session tracking |
| `holiday` | Holiday calendar (SLA calc) |

### ERP Tables (PostgreSQL)
| Table | Purpose |
|---|---|
| `erp_data.asset_data` | Asset master data |
| `erp_data.asset_loan` | Asset loans |
| `erp_data.material_list` | Material master data |
| `erp_data.warehouse_list` | Warehouse master data |
| `erp_data.purchase_order` | Purchase orders |

### Top 20 Most-Queried Tables
| Rank | Table | INSERT Count | Primary Modules |
|------|-------|-------------|-----------------|
| 1 | `notification` | 329 | ticket, system events |
| 2 | `system_event_log` | 219 | audit logging |
| 3 | `ticket_list` | 165 | ticket module |
| 4 | `master_material_unit` | 80 | inventory, SAP |
| 5 | `warranty_unit` | 71 | warranty module |
| 6 | `ticket_history_status` | 42 | ticket tracking |
| 7 | `detail_material_unit` | 37 | inventory |
| 8 | `document_data` | 31 | document management |
| 9 | `ticket_kpi_status` | 16 | KPI reporting |
| 10 | `ticket_request_part` | 12 | parts management |
| 11 | `ticket_report` | 12 | reporting |
| 12 | `ticket_master_warranty_temp` | 11 | warranty |
| 13 | `ticket_history_request_part` | 10 | parts history |
| 14 | `ticket_asset_list` | 10 | asset tracking |
| 15 | `internal_log` | 9 | system logging |
| 16 | `upload_master_unit` | 8 | file upload |
| 17 | `document_upload` | 8 | document management |
| 18 | `BOM` | 8 | inventory |
| 19 | `Masterpart` | 7 | inventory |
| 20 | `ticket_engineer_history` | 5 | engineer tracking |

---

## 6. Business Workflow Patterns

### Status Machines
```
Ticket: [New] → [Analyzing] → [Pending Part] → [Solved] → [Closed]
                                          ↓
                                    [Cancelled]

Loan: [New Request] → [On Loan] → [Close Loan]
                           ↓
                     [Cancel Loan]
```

### Approval Chains
```
[Document Created] → [Pending Approval] → [Approved/Rejected] → [Processed]
```
Found in: Loan, Quotation, Sales Order, SPRF approvals

### SLA Calculation
```php
// Gold SLA (1): 24/7 response
// Silver SLA (2): Business hours (Mon-Fri, 8am-5pm)
// Excludes holidays from 'holiday' table
```

---

## 7. Code Duplication

| Pattern | Occurrences | Severity |
|---|---|---|
| `redirect()` function | 50+ | Medium |
| `DBFormatDate()` function | 50+ | Medium |
| `ReplaceString()` function | 50+ | High |
| `timeDiffWeekendsOff()` | 9 | High |
| Dashboard versions | 8+ | Low |
| Ticket module variants | 4 | Medium |

---

## 8. Risk Assessment

| Vulnerability | Severity | Exploitability | Impact |
|---|---|---|---|
| Direct SQL via `$_GET['sql']` | CRITICAL | Trivial | Full database compromise |
| Authentication bypass | CRITICAL | Easy | Unauthorized admin access |
| GROUP BY injection | CRITICAL | Easy | Data extraction, DoS |
| WHERE clause injection | CRITICAL | Easy | Data extraction, auth bypass |
| Hardcoded credentials | HIGH | Easy | Database compromise |
| No input sanitization | CRITICAL | Trivial | Multiple attack vectors |
| Deprecated mysql_* | MEDIUM | N/A | Future compatibility issues |

---

## 9. Rekomendasi Immediate

1. **Replace MD5** dengan `password_hash()` / `password_verify()` (bcrypt/Argon2)
2. **Use prepared statements** (PDO/MySQLi) everywhere — `ReplaceString()` blacklist tidak cukup
3. **Remove hardcoded credentials** dari source code; gunakan environment variables
4. **Enforce consistent auth checks** — setiap page harus verify `user-session` AND `session-login`
5. **Add CSRF tokens** ke semua form
6. **Implement rate limiting** pada login dan 2FA endpoints
7. **Regenerate session ID** pada privilege change
8. **Fix password change** untuk verify old password sebelum update
9. **Enforce session timeout** di semua page, bukan hanya `form.php`
10. **Remove `$_GET['sql']`** dari `view_paths.php` — ini backdoor SQL injection

---

## 10. Database Connection Methods

### Primary Connection (mysql_* - DEPRECATED)
**File:** `/home/admins/code_mirror/S3/dist/panel.php` (lines 1-22)
```php
$hostname="172.16.1.2";
$userdb="admins";
$passwddb="fid123!!";
$dbname="s3Prod";
$dbconn1 = mysql_connect($hostname,$userdb,$passwddb);
mysql_select_db($dbname,$dbconn1) or die ("Database tidak ditemukan");
```

### Secondary Connection (PDO)
**File:** `/home/admins/code_mirror/S3/includes/basics/settings/dbsettings.inc`
```php
define("DB_SERVER", "localhost");
define("DB_USER", "root");
define("DB_NAME", "inoerp");
define("DB_PASS", "arif069");
```

### Tertiary Connection (mysqli_*)
**File:** `/home/admins/code_mirror/S3/pages/wizard_sap/sap_parsing_detail_unit/dbconfig.php`
```php
$mysqli = new mysqli($hostname_paddumai, $username_paddumai, $password_paddumai, $database_paddumai);
```

---

## 11. Module-to-Table Mapping

| Module Directory | Primary Tables Accessed |
|-----------------|------------------------|
| `ticket/` | ticket_list, ticket_history_status, ticket_kpi_status, ticket_request_part, ticket_report |
| `stock/` | stock_logistic, master_material_unit, detail_material_unit |
| `master_data/` | customer_list, partner, partner_location, product_list, model_type |
| `inv/` | BOM, Masterpart, part_no_list, part_arrival |
| `sprf/` | sprf_list, sprf_detail, sprf_count, sprf_supplier |
| `quotation/` | quotation_list, quotation_detail, quotation_count |
| `sales_order/` | sales_order_list, sales_order_detail, sales_order_count |
| `delivery_instruction/` | delivery_instruction_list, delivery_instruction_detail, delivery_instruction_count |
| `loan/` | loan_list, loan_count, extend_loan_asset |
| `unit/` | unit_type, unit_customer, unit_distributor, warranty_unit |
| `call_log/` | call_log, call_log_count, product_call_log |
| `report/` | ticket_report, ticket_statistik, statistik |
| `dashboard/` | ticket_list, notification, system_event_log, call_log |
| `profile/` | users, users_ex, user_activity_history |
| `token/` | token, tb_temp_token |
| `part_arrival/` | part_arrival, part_request_status |

---

## 12. Key File Paths Summary

| Category | Path |
|---|---|
| Root entry point | `/index.php` |
| Root DB config | `/config/koneksi.php` |
| S3 Admin entry | `/S3/pages/form.php` |
| S3 Admin router | `/S3/dist/controller.php` |
| S3 DB/email config | `/S3/dist/panel.php` |
| S3 Encryption | `/S3/dist/encrypt.php` |
| S3 Login | `/S3/pages/login.php` |
| S3 External pages | `/S3/external/pages/` |
| Mobile entry | `/m/index.php` |
| Mobile ticket tracking | `/m/ticket_tracking.php` |
| Mobile view asset | `/m/view_asset.php` |
| Mobile DB config | `/m/config/koneksi.php` |
| Admin web assets | `/adminweb/` |
| S3 Modules | `/S3/module/` |
| S3 Includes | `/S3/includes/` |
| S3 Config | `/S3/config/` |

---

## 13. SQL Injection Vulnerabilities (Detail)

### CRITICAL: Direct SQL Injection via $_GET
**File:** `/home/admins/code_mirror/S3/includes/extensions/view/view_path/view_paths.php` (line 9)
```php
$sql = $_GET['sql'];
```

### CRITICAL: GROUP BY Injection
**File:** `/home/admins/code_mirror/S3/includes/json/json_select.php` (line 215)
```php
$sql .= " GROUP BY " . $_GET['group_by'][0];
```

### CRITICAL: WHERE Clause Injection
**File:** `/home/admins/code_mirror/S3/includes/general_class/class.search.inc` (lines 695, 697)
```php
$whereFields[] = sprintf("`%s` = %s ", $value, trim(mysql_prep($_GET[$value])));
```

### CRITICAL: Authentication Bypass
**File:** `/home/admins/code_mirror/S3/pages/sign_in.php` (line 23)
```php
$query = "SELECT * FROM erp_data.userdb WHERE username='".$_POST['username']."' AND password=md5('".$_POST['password']."') AND flag='1' AND block_user='N' AND expired_date < current_date ";
```

### CRITICAL: No Sanitization Function
**File:** `/home/admins/code_mirror/S3/includes/functions/functions.inc` (lines 780-782)
```php
function mysql_prep($value) {
  return $value;  // NO SANITIZATION - returns raw input!
}
```

---

## 14. Statistics

| Metric | Value |
|--------|-------|
| Total Modules | 29 |
| Total PHP Files | ~1,784 |
| Cron Jobs | 10 |
| Dashboard Versions | 8+ |
| Shared Functions | 9 |
| Database Tables | 190 |
| Code Duplication Rate | High (~40%) |
| SQL Injection Points | 13+ |
| Exposed Credentials | 5 locations |

---

*File ini dapat dilanjutkan untuk blueprint migrasi Laravel + Filament + Next.js*
