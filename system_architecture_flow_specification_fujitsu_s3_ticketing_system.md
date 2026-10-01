# Spesifikasi Arsitektur Sistem & Alur Bisnis (System Flow)
## Fujitsu Support Service System Portal (S3) Ticketing System

Dokumen ini ditujukan bagi **System Architect**, **Tech Lead**, dan **Backend Engineer** untuk memahami topologi domain, alur kerja antar-entitas, mesin status (*state machine*), aturan validasi SLA/KPI, serta dependensi sub-modul pada sistem S3 Ticketing.

---

## 1. Ringkasan Eksekutif & Topologi Sistem

S3 (*Support Service System*) adalah platform manajemen layanan purnajual (*after-sales support*) perangkat keras dan infrastruktur IT Fujitsu di Indonesia. Sistem ini mencakup layanan pelanggan (*customer-facing*), operasional *helpdesk/front-desk*, penjadwalan *engineer* lapangan, logistik suku cadang (*spare parts*), dan penagihan/kuotasi non-garansi.

### 1.1 Diagram Konteks Sistem (C4 Context Level)

```mermaid
C4Context
    title Diagram Konteks Sistem S3 Fujitsu

    Person(customer, "Customer / Enterprise User", "Mencari status aset, tracking tiket, atau mengajukan tiket via Self Service")
    Person(frontdesk, "Front Desk / Call Center", "Triage, pembuatan tiket, penugasan, dan pengawasan siklus tiket")
    Person(leader, "Resolver Group Leader", "Manajemen antrean tim, penjadwalan teknisi, approval pending")
    Person(engineer, "Field / Onsite Engineer", "Investigasi teknis, break-fix, request spare part, input CSR")
    Person(asp, "Authorized Service Partner (ASP)", "Pusat servis rekanan untuk tiket carry-in lokal")
    Person(logistics, "Logistic Team", "Manajemen stok part, peminjaman (loan), dan penerimaan part retur")

    System(s3_system, "Fujitsu S3 Portal & Ticketing Engine", "Core monolith/services mengelola tiket, SLA, scheduling, file sharing, dan notifikasi")

    System_Ext(email_gateway, "SMTP / Email Gateway", "Pengiriman notifikasi tiket baru, SLA warning, dan email alert")
    System_Ext(maps_api, "Google Maps API", "Visualisasi 126 lokasi service point di seluruh Indonesia")
    System_Ext(storage_ftp, "Internal FTP Storage Server", "Penyimpanan dokumen CSR, RRF, repair tag, dan customer log files")

    Rel(customer, s3_system, "Akses Web Portal / Self Service / Tracking")
    Rel(frontdesk, s3_system, "Manajemen tiket penuh & triage")
    Rel(leader, s3_system, "Penugasan & Task Scheduling")
    Rel(engineer, s3_system, "Update progres, CSR, & Spare part")
    Rel(asp, s3_system, "Eksekusi tiket carry-in")
    Rel(logistics, s3_system, "Update status part (dispatch / return)")

    Rel(s3_system, email_gateway, "Trigger email otomatis")
    Rel(s3_system, maps_api, "Query titik koordinat service center")
    Rel(s3_system, storage_ftp, "Upload/Download berkas (TTL 7 hari)")
```

---

## 2. Matriks Akses & Kewenangan Berbasis Peran (RBAC Matrix)

Sistem membedakan hak akses berdasarkan peran operasional (*Role-Based Access Control*):

| Kapabilitas / Fitur | Front Desk / Call Center | Resolver Group Leader | Field Engineer | Authorized Service Partner (ASP) | Enterprise Customer |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Create Ticket (Carry-in / Non-Carry-in)** |  Penuh | ❌ | ❌ |  Hanya Carry-in | ❌ (Submit Request Only) |
| **Admit / Decline Customer Request** |  Penuh | ❌ | ❌ | ❌ | ❌ |
| **Change Status: In Progress / Escalation**|  |  |  |  | ❌ |
| **Change Assignment (Reassign)** |  |  | ❌ | ❌ (Coordinator Only) | ❌ |
| **Set Ticket to Pending / Stop SLA** |  |  | ❌ (Perlu Eskalasi) | ❌ (Request ke Leader) | ❌ |
| **Request Spare Part** |  |  |  |  | ❌ |
| **Update Return Part Status** |  |  |  |  | ❌ |
| **Input Activity Report & CSR Upload** |  |  |  (Mandatori) |  (Mandatori) | ❌ |
| **Solve Ticket** |  |  |  |  | ❌ |
| **Close / Cancel Ticket** |  (Penuh) | ❌ | ❌ | ❌ | ❌ |
| **Task Scheduling Management** | ❌ |  (All Members) |  (Own Schedule) | ❌ | ❌ |

---

## 3. Diagram Mesin Status Tiket (Ticket State Machine)

Siklus hidup tiket diatur melalui 15 status operasional yang mengontrol alur proses dan penghitungan SLA/KPI.

```mermaid
stateDiagram-v2
    [*] --> New: Ticket Created (Call / Email / Walk-in / Portal Admit)
    
    New --> Analyzing: Front Desk triage & diagnosa awal
    Analyzing --> Cancelled: Kesalahan input tipe/onsite (Audit exclusion)
    
    Analyzing --> InProgress: Diselesaikan langsung oleh Front Desk
    Analyzing --> Dispatching: Assign ke Resolver Group / Engineer
    
    Dispatching --> InProgress: Engineer menerima penugasan (Response Time)
    
    state "SLA Paused States" as SLA_Paused {
        WaitingCustomerFeedback: Waiting Customer Feedback
        WaitingCustomerSchedule: Waiting Customer Schedule
        InternalQuotation: Internal Quotation (Non-Warranty)
        WaitingCustomerPO: Waiting Customer PO
        Pending: General Pending
    }

    InProgress --> InternalQuotation: Part/Unit Out of Warranty
    InternalQuotation --> WaitingCustomerPO: Quotation diterbitkan
    WaitingCustomerPO --> InProgress: Customer PO approve & payment OK

    InProgress --> WaitingPart: Request Spare Part diajukan
    WaitingPart --> InProgress: Logistic mendistribusikan part

    InProgress --> SLA_Paused: Menunggu respons / jadwal customer
    SLA_Paused --> Dispatching: Target end date terlampaui (Auto-dispatch)
    SLA_Paused --> Dispatching: Manual resume (Manual unlock)

    InProgress --> Escalation3rdParty: Eskalasi vendor pihak ke-3 (Isi CTRS No)
    Escalation3rdParty --> InProgress: Update dari vendor kembali

    InProgress --> Solved: Validasi terpenuhi (Resolution, Category, Part Return)
    
    Solved --> PickupAfterNotResolve: Carry-in unit diambil namun unrepairable
    Solved --> Closed: Validasi terpenuhi (Activity Report + CSR Document Uploaded)

    Closed --> [*]
    Cancelled --> [*]
    PickupAfterNotResolve --> [*]
```

---

## 4. End-to-End Business Flow & Sequence Diagrams

### 4.1 Alur Ingesti Tiket & Triage (Incoming Call, Email, & Portal)

Setiap interaksi pelanggan tunduk pada aturan ketat: **"One Call, One Ticket"**.

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Pelanggan / Entitas
    actor FD as Front Desk / Call Center
    participant S3 as Core S3 Engine
    participant DB as Database / Asset Engine

    alt Via Customer Portal (Self Service)
        Customer->>S3: Submit Ticket Request (Serial Number, Deskripsi)
        S3->>FD: Muncul di antrean "Online Customer Request Ticket"
        FD->>DB: Validasi Status Garansi & Cakupan Kontrak
        alt Request Valid & Garansi Aktif
            FD->>S3: Action "Admit"
            S3->>S3: Generate Ticket ID & Passcode
        else Request Tidak Valid / Di Luar Cakupan
            FD->>S3: Action "Decline"
            S3-->>Customer: Notifikasi penolakan via email/portal
        end
    else Via Call, Email, atau Walk-in
        Customer->>FD: Pelaporan insiden / permintaan servis
        FD->>DB: Query Serial Number / WBS Code
        DB-->>FD: Auto-populate Data Unit, Model, SLA Tier, & Garansi
        FD->>S3: Create Ticket Form (Carry-in / Onsite, Severity, Priority)
        S3->>S3: Generate Primary Ticket ID & Set Status = "New"
        S3-->>Customer: Kirim Email Notifikasi beserta Ticket ID & Passcode Tracking
    end
```

### 4.2 Alur Penugasan & Eskalasi Teknis (Incident & Multi-Engineer Reference)

Ketika tiket membutuhkan penanganan lapangan atau melibatkan lebih dari satu tim resolver:

```mermaid
sequenceDiagram
    autonumber
    actor FD as Front Desk
    actor Leader as Resolver Group Leader
    actor Eng1 as Primary Engineer
    actor Eng2 as Secondary Engineer
    participant S3 as S3 Engine

    FD->>S3: Set Status = "Analyzing"
    FD->>S3: Assign ke Resolver Group (Status -> "Dispatching")
    Leader->>S3: Assign tiket ke Primary Engineer
    Eng1->>S3: Acknowledge Assignment (Ubah Status -> "In Progress")
    
    opt Kebutuhan Multi-Teknisi / Sub-Ticket
        Eng1->>Leader: Memerlukan bantuan spesialisasi lain
        Leader->>S3: Change Assignment dengan flag "Create Ticket Reference = Yes"
        S3->>S3: Buat Reference Ticket ID baru (Sub-ticket) berelasi ke Primary Ticket ID
        S3->>Eng2: Tugaskan Secondary Engineer pada Reference Ticket
        Eng2->>S3: Kerjakan & Selesaikan Reference Ticket
    end

    opt Onsite Field Service
        Eng1->>S3: Input "First Onsite Time" (Menghitung SLA Response Time)
        S3->>S3: Lock / Fix Response KPI Metric
    end
```

### 4.3 Alur Siklus Peminjaman & Pengembalian Suku Cadang (Spare Part Lifecycle)

Penggantian suku cadang hanya diperbolehkan untuk tiket bertipe *Hardware*.

```mermaid
sequenceDiagram
    autonumber
    actor Eng as Engineer / ASP
    participant S3 as S3 Engine
    actor Log as Tim Logistik
    actor Cust as Customer

    Eng->>S3: Buka Tab "Request Part" (Input Part No, Qty, Jenis: Spare Part / Finish Good)
    S3->>S3: Otomatis ubah status tiket -> "Waiting Part"
    S3-->>Log: Notifikasi email permintaan part baru (Request No dibuat)
    
    Log->>S3: Alokasikan Stok & Ubah status item -> "Loaned"
    Log->>Eng: Kirim fisik spare part
    Eng->>S3: Terima part & update "Receiver Name"
    Eng->>S3: Ubah status tiket kembali ke -> "In Progress"
    
    Eng->>Cust: Pasang part & lakukan pengetesan mesin
    
    alt Part Rusak Berhasil Diganti
        Eng->>S3: Update Return Part Status = "Bad Part" (Input Nama Pengembali & Tanggal Aktual)
    else Part Tidak Terpakai / Troubleshooting Selesai
        Eng->>S3: Update Return Part Status = "Good Part"
    else Hanya Sebagian Part Terpakai
        Eng->>S3: Update Return Part Status = "Partial Good" (Tentukan Qty Good vs Qty Bad)
    else Pembelian Non-Garansi (Milik Pelanggan)
        Eng->>S3: Update Return Part Status = "Part At Customer"
    end

    Note over Eng, S3: Tiket TIDAK BISA di-Solve jika ada item 'Loaned' yang belum di-update return statusnya.
```

### 4.4 Alur Non-Garansi & Kuotasi Komersial (Quotation & PO Gate)

Jika unit perangkat keras teridentifikasi berada di luar masa garansi (*Out of Warranty*):

```mermaid
sequenceDiagram
    autonumber
    actor Eng as Engineer
    actor FD as Front Desk
    participant S3 as S3 Engine
    actor Cust as Customer

    Eng->>S3: Ubah status tiket -> "Internal Quotation"
    FD->>S3: Input data costing & generate Nomor Quotation
    FD->>S3: Ubah status tiket -> "Waiting Customer PO" (SLA Counter Ditunda)
    FD->>Cust: Kirimkan Quotation komersial
    
    alt Customer Menyetujui
        Cust->>FD: Terbitkan Purchase Order (PO) & Bukti Pembayaran
        FD->>S3: Input Customer PO No & Ubah Payment Status -> "Payment OK"
        FD->>S3: Ubah status tiket -> "Dispatching" / "In Progress"
        Note over Eng, S3: Engineer melanjutkan pekerjaan perbaikan
    else Customer Menolak Kuotasi (Khusus Carry-in)
        Cust->>FD: Membatalkan perbaikan karena biaya
        FD->>S3: Ubah status tiket -> "Solved" dengan opsi "Resolve Ticket = No"
        Cust->>FD: Mengambil kembali unit tanpa perbaikan
        FD->>S3: Ubah status tiket -> "Pickup after not resolve"
        Note over FD, S3: DILARANG melakukan pembatalan tiket (Cancel) pada skenario ini.
    end
```

### 4.5 Alur Penutupan Tiket & Konsumsi Token Kontrak (Resolution & Closure Gate)

```mermaid
sequenceDiagram
    autonumber
    actor Eng as Engineer
    participant S3 as S3 Engine
    actor FD as Front Desk

    Note over Eng: Syarat Solve Incident:<br/>1. Isi deskripsi Resolution (min 11 char)<br/>2. Pilih Incident Category<br/>3. Seluruh return part berstatus final

    Eng->>S3: Submit "Change Status to Solved" (Pilih: Resolve = Yes/No)
    S3->>S3: Tandai status -> "Solved"
    S3-->>FD: Kirim Notifikasi "Need Close Ticket"

    opt Menggunakan FID Contract Token
        FD->>S3: Input Token ID & Jumlah Token yang digunakan
        S3->>S3: Kurangi kuota token pelanggan secara atomik
    end

    Note over Eng, FD: Syarat Close Incident / Request / PM:<br/>1. Input Activity Report (kronologi & waktu)<br/>2. Upload berkas CSR / RRF / Repair Tag

    Eng->>S3: Simpan Activity Report & Upload Scan Dokumen CSR (PDF/Gambar)
    FD->>S3: Verifikasi kelengkapan berkas & ubah status -> "Closed"
    S3->>S3: Kunci seluruh field tiket (Read-Only)
```

---

## 5. Mesin Aturan SLA, Perhitungan KPI, & Validasi Sistem

### 5.1 Definisi Tingkat SLA (SLA Tiers)

1. **Gold SLA**: Komitmen waktu respons *onsite* $\le 4\text{ jam}$, beroperasi penuh $24/7$.
2. **Standard SLA**: Komitmen waktu respons *onsite* hari kerja berikutnya (*Next Business Day* - NBD), jadwal $8/5$.
3. **Carry-in SLA**: Tidak ada komitmen resolusi onsite; batas standar penyelesaian di *service point* adalah $7\text{ hari kerja}$.
4. **Non-Warranty Ticket**: Tidak ada komitmen SLA resmi; target *best-effort* internal sistem diset default $30\text{ hari kerja}$.
5. **Other Standard Contracts**: Target resolusi sistem default diset $14\text{ hari kerja}$.

### 5.2 Logika Perhitungan KPI (KPI Calculation Rules)

KPI diukur berdasarkan rasio konsumsi waktu terhadap ambang batas komitmen:

$$KPI_{\text{persen}} = \left( \frac{\text{Waktu Aktual Konsumsi}}{\text{Target SLA Durasi}} \right) \times 100\%$$

* **Status Ketercapaian SLA**: Tiket dinyatakan **Meet SLA** jika nilai $KPI \le 100\%$. Sebaliknya, jika $KPI > 100\%$, tiket diklasifikasikan sebagai **Miss SLA**.
* **Mekanisme KPI Fixed (Penguncian Perhitungan)**:
  * **FID Rule (Non Carry-in)**: Nilai KPI dikunci permanen (*Fixed*) saat nilai `First Onsite Time` diinput ke dalam sistem.
  * **FID Rule (Carry-in)**: Nilai KPI dikunci permanen saat tiket mencapai status `Solved`.
  * **RESOLVE Rule (Custom SLA)**: Nilai KPI dikunci permanen saat status tiket menjadi `Solved`.
* **Mekanisme KPI Paused (Jeda Waktu)**:
  Penghitungan durasi SLA otomatis dihentikan (*paused*) saat tiket berada pada status:
  1. `Waiting Customer PO`
  2. `Waiting Customer Feedback`
  3. `Waiting Customer Schedule`
  4. `Pending`

Setiap transisi ke status jeda **wajib** mencantumkan atribut `Target End Date`. Apabila batas waktu *target end date* terlampaui dan belum ada pembaruan manual, *background worker* sistem akan secara otomatis mengembalikan status tiket ke `Dispatching`.

### 5.3 Matriks Validasi Gate Status (Quality Gates)

Sistem memberlakukan guard validation sebelum mengizinkan mutasi status:

```text
+-------------------+--------------------------------------------------------------------------+
| Target Status     | Syarat Validasi Sistem (Pre-conditions)                                 |
+-------------------+--------------------------------------------------------------------------+
| Solved (Incident) | 1. Field 'Resolution' terisi (panjang karakter >= 11 karakter).          |
|                   | 2. Minimal 1 'Incident Category' dipilih.                                |
|                   | 3. Semua item Request Part telah berstatus final (Bukan 'Loaned').       |
|                   | 4. User wajib memilih flag 'Resolve Ticket' (Yes / No).                  |
+-------------------+--------------------------------------------------------------------------+
| Solved (Request)  | 1. Field 'Resolution' terisi (panjang karakter >= 11 karakter).          |
|                   | 2. Semua item Request Part telah berstatus final (Bukan 'Loaned').       |
+-------------------+--------------------------------------------------------------------------+
| Solved (PM)       | 1. Field 'Resolution' terisi.                                            |
+-------------------+--------------------------------------------------------------------------+
| Solved (Inquiry)  | Tanpa prasyarat tambahan (dapat langsung di-solve setelah 'Analyzing'). |
+-------------------+--------------------------------------------------------------------------+
| Closed            | 1. Record 'Activity Report' wajib terisi lengkap.                        |
|                   | 2. File lampiran wajib diunggah: Dokumen CSR (Customer Service Report)    |
|                   |    atau RRF (Return Receive Form) untuk carry-in.                        |
|                   |    Jika tipe unit Server/Storage: File 'Repair Tag' wajib disertakan.   |
+-------------------+--------------------------------------------------------------------------+
| Cancelled         | Hanya diperbolehkan jika berasal dari status 'Analyzing' DAN disebabkan  |
|                   | oleh kesalahan input struktural:                                         |
|                   | - Salah memilih jenis Carry-in (Yes/No).                                 |
|                   | - Salah memilih Ticket Type.                                             |
|                   | - Salah memilih flag Onsite Support.                                     |
|                   | (Tiket yang di-cancel dieksklusikan dari kalkulasi performa bulanan).    |
+-------------------+--------------------------------------------------------------------------+
```

---

## 6. Desain Entitas Data Utama (Data Entity Relationship)

Struktur skema relasional utama yang menyokong alur sistem ticketing S3:

```mermaid
erDiagram
    CUSTOMER ||--o{ TICKET : submits
    ASSET ||--o{ TICKET : "referenced by"
    USER ||--o{ TICKET : "assigned to"
    TICKET ||--o{ TICKET_REFERENCE : "parent of"
    TICKET ||--o{ PART_REQUEST : contains
    TICKET ||--o{ ACTIVITY_REPORT : logs
    TICKET ||--o{ TICKET_DOCUMENT : attaches
    TICKET ||--o{ TASK_SCHEDULE : schedules
    TICKET ||--o{ NOTIFICATION : triggers

    CUSTOMER {
        string customer_id PK
        string customer_name
        string customer_alias
        string address
        string city
        string pic_name
        string phone_no
        string email
        int token_balance
    }

    ASSET {
        string serial_number PK
        string wbs_code
        string product_model
        string unit_type
        date start_warranty
        date end_warranty
        date end_factory_date
        string sla_level
        string sla_rule
    }

    TICKET {
        string ticket_id PK
        string serial_number FK
        string customer_id FK
        string ticket_type
        boolean is_carry_in
        boolean is_onsite
        string call_from
        string product_category
        string severity
        int priority
        string status
        string resolver_group
        string support_office
        string assigned_user_id FK
        datetime open_date
        datetime first_onsite_time
        datetime solve_date
        datetime close_date
        string quotation_no
        string payment_status
        int kpi_percentage
        boolean is_kpi_fixed
        string resolution_desc
        string passcode
    }

    PART_REQUEST {
        int request_no PK
        string ticket_id FK
        string part_no
        string part_description
        int quantity
        string warranty_type
        string part_type
        string customer_po
        string status_request
        string receiver_name
        string return_status
        string returned_by
        datetime actual_return_date
    }

    ACTIVITY_REPORT {
        int report_id PK
        string ticket_id FK
        string engineer_id FK
        datetime activity_datetime
        string description
    }

    TICKET_DOCUMENT {
        int document_id PK
        string ticket_id FK
        string kind_of_file
        string ref_no
        string file_name
        string file_path
        int file_size
        string uploaded_by FK
        datetime upload_date
    }

    TASK_SCHEDULE {
        int schedule_id PK
        string ticket_id FK
        string engineer_id FK
        datetime start_datetime
        datetime end_datetime
        string venue_location
        string event_title
        string task_description
        string status
    }
```

---

## 7. Arsitektur Subsistem Pendukung (Sub-modules)

### 7.1 Subsistem Penjadwalan Teknisi (Task Scheduler)
* **Tujuan**: Mencegah tabrakan jadwal teknisi lapangan (*prevent double-booking*) dan memvisualisasikan ketersediaan teknisi untuk Front Desk dan Resolver Leader saat proses penugasan.
* **Fitur Utama**:
  * Filter kalender mingguan per Resolver Group.
  * Penandaan status jadwal (*Active* vs *Inactive*).
  * Relasi mandatory terhadap `Ticket ID`.
  * Pembatasan izin edit berbasis penanda pin (pin merah dapat diedit, pin abu-abu terkunci/read-only).

### 7.2 Layanan Berbagi Berkas FTP (FTP Storage Service)
* Digunakan untuk pertukaran berkas berukuran besar seperti log kerusakan sistem pelanggan dan buku panduan manual perbaikan.
* **Spesifikasi & Limitasi**:
  * Batas maksimum unggahan default: $75\text{ MB}$ per berkas.
  * Tautan unduhan (*download link share*) memiliki masa kedaluwarsa otomatis (**TTL**): $7\text{ hari}$.
  * Berkas sumber tetap tersimpan permanen di direktori server penyimpanan meskipun tautan publik telah kedaluwarsa.

### 7.3 Mesin Notifikasi & Task Queue (Notification Engine)
Sistem memiliki *in-app notification generator* yang memetakan item aksi langsung ke *dashboard* masing-masing pengguna berdasarkan perubahan status tiket:
* `Need Response`: Dipicu saat tiket baru dibuat dan menuntut respons awal.
* `Need Assignment`: Dipicu saat status tiket berada di fase `Analyzing` untuk segera ditugaskan ke *resolver group*.
* `Need Close Ticket`: Dipicu saat status tiket telah `Solved` dan menunggu dokumen CSR serta laporan aktivitas diunggah.

---

## 8. Catatan Arsitektur & Rekomendasi Modernisasi (Architect's Advisory)

Bagi tim arsitek yang merancang ulang (*re-architecting*) sistem S3 ke arsitektur modern (misal: Microservices atau Modular Monolith):

1. **State Machine Decoupling**: Pisahkan 15 status tiket ke dalam mesin status formal (seperti *Spring State Machine*, *Stateless*, atau *Temporal/Workflow Orchestrator*) agar validasi transisi tidak tersebar di banyak *controller logic*.
2. **SLA Calculation Worker**: Hindari kalkulasi persentase KPI secara sinkron saat kueri halaman (*on-the-fly request*). Gunakan *asynchronous distributed worker* (misal: Celery, BullMQ, atau Kafka consumers) untuk memperbarui konsumsi waktu dan mendeteksi kondisi kedaluwarsa *Target End Date* pada status *Pending*.
3. **Optimistic Locking untuk Part Inventory**: Pada modul `Request Part`, pastikan alokasi stok suku cadang menggunakan transaksi *database lock* untuk mencegah *race condition* atas nomor part yang sama.
4. **Audit Trail Immutability**: Pisahkan histori mutasi tiket, riwayat penugasan, dan perubahan status pengembalian part ke tabel *audit event log* terpisah yang bersifat *append-only*.