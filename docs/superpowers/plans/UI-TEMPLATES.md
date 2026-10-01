# UI Templates — S3 System Migration

**Purpose:** Koleksi template UI untuk Plan 3 (Filament Admin) dan Plan 4 (Frontend Next.js).
**Status:** Menunggu review dan input dari user.

---

## Plan 3: Filament Admin Templates

### 3.1 Ticket Form (Create/Edit)

**Purpose:** Form untuk membuat atau mengedit ticket.

**Fields:**
| Field | Type | Required | Validation |
|---|---|---|---|
| Ticket No | Text | Auto | - |
| Ticket Date | Date | Yes | - |
| Customer | Select | Yes | exists:customers |
| Engineer | Select | No | exists:engineers |
| Problem Description | Textarea | No | max:5000 |
| Status | Select | Yes | 15 status options |
| SLA | Select | No | Gold, Standard, Carry-in, Non-Warranty, Other |
| Units | Multi-select | No | exists:units |
| Close Remark | Textarea | No | max:5000 |

**Actions:**
- Save
- Save & New
- Cancel

---

### 3.2 Ticket Detail (View)

**Purpose:** Menampilkan detail ticket.

**Sections:**
1. **Header** — Ticket No, Status Badge, SLA Badge
2. **Info** — Customer, Engineer, Date, Problem Description
3. **Units** — List of units (many-to-many)
4. **Comments** — Comment thread
5. **Timeline** — Timeline events
6. **Actions** — Edit, Delete, Change Status, Add Comment, Add Unit

---

### 3.3 Ticket List (Table)

**Purpose:** Menampilkan daftar ticket.

**Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Ticket No | Text | Yes | Yes |
| Date | Date | Yes | Yes |
| Customer | Text | Yes | Yes |
| Engineer | Text | Yes | Yes |
| Status | Badge | No | Yes |
| SLA | Badge | No | Yes |
| Actions | - | No | No |

**Filters:**
- Status (15 options)
- Engineer
- Date Range
- Customer

**Bulk Actions:**
- Delete
- Change Status
- Export

---

### 3.4 PM Form (Create/Edit)

**Purpose:** Form untuk membuat atau mengedit Preventive Maintenance.

**Fields:**
| Field | Type | Required | Validation |
|---|---|---|---|
| PM No | Text | Auto | - |
| PM Type | Select | Yes | monthly, quarterly, annual |
| Customer | Select | Yes | exists:customers |
| Engineer | Select | No | exists:engineers |
| Scheduled Date | Date | Yes | - |
| Status | Select | Yes | PLANNED, IN_PROGRESS, COMPLETED, CANCELLED |
| Description | Textarea | No | max:5000 |
| Checklist | Repeater | No | - |
| Serial Numbers | Multi-select | Yes | min:1 |

**Actions:**
- Save
- Save & New
- Generate Tickets
- Cancel

---

### 3.5 PM Detail (View)

**Purpose:** Menampilkan detail PM.

**Sections:**
1. **Header** — PM No, Type, Status
2. **Info** — Customer, Engineer, Scheduled Date, Description
3. **Progress** — Progress bar (completed/total)
4. **Checklist** — Checklist items
5. **Tickets** — Linked tickets list
6. **Actions** — Edit, Delete, Generate Tickets

---

### 3.6 Dashboard (Admin)

**Purpose:** Admin dashboard utama.

**Widgets:**
1. **Stats Overview** — Open Tickets, Active Loans, Active Units, Unread Notifications
2. **Ticket Chart** — Ticket trends (last 30 days)
3. **Recent Tickets** — 10 recent tickets
4. **Notification List** — Unread notifications

---

### 3.7 SLA Report

**Purpose:** Laporan SLA compliance.

**Columns:**
| Column | Type | Filterable |
|---|---|---|
| Ticket No | Text | Yes |
| Customer | Text | Yes |
| Date | Date | Yes |
| SLA | Badge | Yes |
| SLA Status | Badge | Yes |

**Filters:**
- SLA Tier
- SLA Status (Meet, Miss)
- Date Range

---

### 3.8 User Management

**Purpose:** Manajemen user dan role.

**User Table:**
| Column | Type | Filterable |
|---|---|---|
| Name | Text | Yes |
| Email | Text | Yes |
| Role | Badge | Yes |
| Status | Badge | Yes |
| Actions | - | No |

**Role Table:**
| Column | Type | Filterable |
|---|---|---|
| Name | Text | Yes |
| Slug | Text | Yes |
| Users Count | Number | No |
| Status | Badge | Yes |
| Actions | - | No |

---

## Plan 4: Frontend (Next.js) Templates

### 4.1 Login Page

**Purpose:** Halaman login.

**Fields:**
| Field | Type | Required | Validation |
|---|---|---|---|
| Username | Text | Yes | min:1 |
| Password | Password | Yes | min:1 |

**Actions:**
- Login
- Forgot Password

**Layout:**
- Centered card
- Logo
- Title

---

### 4.2 Dashboard Layout

**Purpose:** Layout utama dashboard.

**Components:**
1. **Sidebar** — Navigation menu
2. **Header** — User menu, notifications, search
3. **Content Area** — Page content
4. **Mobile Nav** — Mobile navigation

**Navigation:**
- Dashboard
- Tickets
- Units
- Preventive Maintenance
- Loans
- Sales Orders
- Quotations
- SPRF
- Customers
- Stock
- Engineers
- Reports
- Settings

---

### 4.3 Dashboard Page

**Purpose:** Halaman dashboard utama.

**Widgets:**
1. **Stats Cards** — Open Tickets, Active Loans, Active Units, Unread Notifications
2. **Ticket Chart** — Ticket trends (last 30 days)
3. **Recent Tickets** — 10 recent tickets
4. **Notification List** — Unread notifications

---

### 4.4 Ticket List Page

**Purpose:** Halaman daftar ticket.

**Components:**
1. **Header** — Title, Create Button
2. **Filters** — Status, Engineer, Date Range, Search
3. **Table** — Ticket list
4. **Pagination** — Page navigation

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Ticket No | Text | Yes | Yes |
| Date | Date | Yes | Yes |
| Customer | Text | Yes | Yes |
| Engineer | Text | Yes | Yes |
| Status | Badge | No | Yes |
| SLA | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.5 Ticket Detail Page

**Purpose:** Halaman detail ticket.

**Sections:**
1. **Header** — Ticket No, Status Badge, SLA Badge, Actions
2. **Info** — Customer, Engineer, Date, Problem Description
3. **Units** — List of units
4. **Comments** — Comment thread with form
5. **Timeline** — Timeline events

---

### 4.6 Ticket Form Page

**Purpose:** Form create/edit ticket.

**Fields:**
| Field | Type | Required | Validation |
|---|---|---|---|
| Customer | Select | Yes | exists:customers |
| Engineer | Select | No | exists:engineers |
| Problem Description | Textarea | No | max:5000 |
| Status | Select | Yes | 15 status options |
| SLA | Select | No | Gold, Standard, Carry-in, Non-Warranty, Other |
| Units | Multi-select | No | exists:units |

**Actions:**
- Save
- Save & New
- Cancel

---

### 4.7 Unit List Page

**Purpose:** Halaman daftar unit.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Unit ID | Text | Yes | Yes |
| Serial No | Text | Yes | Yes |
| Type | Text | Yes | Yes |
| Model | Text | Yes | Yes |
| Customer | Text | Yes | Yes |
| Warranty End | Date | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.8 Unit Detail Page

**Purpose:** Halaman detail unit.

**Sections:**
1. **Header** — Unit ID, Serial No, Status
2. **Info** — Type, Model, Customer, Install Date
3. **Warranty** — Warranty Start, End, Status
4. **Tickets** — Related tickets
5. **Documents** — Unit documents

---

### 4.9 PM List Page

**Purpose:** Halaman daftar PM.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| PM No | Text | Yes | Yes |
| Type | Badge | No | Yes |
| Customer | Text | Yes | Yes |
| Scheduled Date | Date | Yes | Yes |
| Progress | Progress Bar | No | No |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.10 PM Detail Page

**Purpose:** Halaman detail PM.

**Sections:**
1. **Header** — PM No, Type, Status
2. **Info** — Customer, Engineer, Scheduled Date, Description
3. **Progress** — Progress bar (completed/total)
4. **Checklist** — Checklist items
5. **Tickets** — Linked tickets list

---

### 4.11 PM Form Page

**Purpose:** Form create/edit PM.

**Fields:**
| Field | Type | Required | Validation |
|---|---|---|---|
| PM Type | Select | Yes | monthly, quarterly, annual |
| Customer | Select | Yes | exists:customers |
| Engineer | Select | No | exists:engineers |
| Scheduled Date | Date | Yes | - |
| Description | Textarea | No | max:5000 |
| Checklist | Repeater | No | - |
| Serial Numbers | Multi-select | Yes | min:1 |

---

### 4.12 Customer List Page

**Purpose:** Halaman daftar customer.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Customer ID | Text | Yes | Yes |
| Name | Text | Yes | Yes |
| Type | Text | Yes | Yes |
| City | Text | Yes | Yes |
| Phone | Text | Yes | Yes |
| Email | Text | Yes | Yes |
| Actions | - | No | No |

---

### 4.13 Customer Detail Page

**Purpose:** Halaman detail customer.

**Sections:**
1. **Header** — Customer ID, Name, Type
2. **Info** — Address, City, Province, Phone, Email, Contact Person
3. **Units** — Customer units
4. **Tickets** — Customer tickets

---

### 4.14 Loan List Page

**Purpose:** Halaman daftar loan.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Loan No | Text | Yes | Yes |
| Ticket No | Text | Yes | Yes |
| Customer | Text | Yes | Yes |
| Loan Date | Date | Yes | Yes |
| Return Date | Date | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.15 Loan Detail Page

**Purpose:** Halaman detail loan.

**Sections:**
1. **Header** — Loan No, Status
2. **Info** — Ticket, Customer, Unit, Loan Date, Return Date
3. **Approvals** — Approval history
4. **Actions** — Approve, Reject, Return

---

### 4.16 Sales Order List Page

**Purpose:** Halaman daftar sales order.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| SO No | Text | Yes | Yes |
| Date | Date | Yes | Yes |
| Customer | Text | Yes | Yes |
| Supplier | Text | Yes | Yes |
| Total | Number | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.17 Quotation List Page

**Purpose:** Halaman daftar quotation.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Quotation No | Text | Yes | Yes |
| Date | Date | Yes | Yes |
| Customer | Text | Yes | Yes |
| Total | Number | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.18 SPRF List Page

**Purpose:** Halaman daftar SPRF.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| SPRF No | Text | Yes | Yes |
| Date | Date | Yes | Yes |
| Customer | Text | Yes | Yes |
| Supplier | Text | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.19 Stock List Page

**Purpose:** Halaman daftar stock.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Item ID | Text | Yes | Yes |
| Item Name | Text | Yes | Yes |
| Category | Text | Yes | Yes |
| Quantity | Number | Yes | Yes |
| Warehouse | Text | Yes | Yes |
| Actions | - | No | No |

---

### 4.20 Engineer List Page

**Purpose:** Halaman daftar engineer.

**Table Columns:**
| Column | Type | Sortable | Filterable |
|---|---|---|---|
| Engineer ID | Text | Yes | Yes |
| Name | Text | Yes | Yes |
| Phone | Text | Yes | Yes |
| Email | Text | Yes | Yes |
| Area | Text | Yes | Yes |
| Status | Badge | No | Yes |
| Actions | - | No | No |

---

### 4.21 Reports Page

**Purpose:** Halaman reports.

**Reports:**
1. **Ticket Summary** — Ticket list dengan filters
2. **SLA Compliance** — SLA status report
3. **Loan Status** — Loan status report

---

### 4.22 Settings Page

**Purpose:** Halaman settings user.

**Sections:**
1. **Profile** — Name, Email, Phone, Avatar
2. **Password** — Change password
3. **Preferences** — Language, Theme, Notifications

---

## Template Delivery Format

Setiap template akan disediakan dalam format:

1. **HTML/Tailwind** — Static HTML dengan Tailwind CSS classes
2. **TypeScript/React** — React component dengan TypeScript types
3. **Form Schema** — Zod validation schema
4. **API Contract** — Request/Response JSON example

---

## Review Checklist

- [ ] Apakah semua template sudah jelas?
- [ ] Apakah ada template yang terlewat?
- [ ] Apakah field validation sudah benar?
- [ ] Apakah API contract sudah sesuai?
- [ ] Apakah ada template yang perlu ditambah?

---

*Last updated: 2026-10-01*
