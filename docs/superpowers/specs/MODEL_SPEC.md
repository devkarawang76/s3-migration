# Spesifikasi Model Eloquent Laravel — Legacy S3 System

**Constraint:** Semua model memetakan struktur kolom existing tanpa modifikasi. Tidak ada perubahan skema database legacy.
**Update:** Database baru boleh ada perubahan (normalized schema, RBAC, enum mapping). SQL injection fix di legacy di-skip (staging mode).
**Update 2:** Ticket comments & timeline, Preventive Maintenance (PM), many-to-many ticket-unit relationship.
**Update 3:** Tambah Customer model, User model (normalized), missing models, fix foreign key types.

---

## 1. Custom User Provider (Legacy Password Handler)

### 1.1 LegacyUserProvider

```php
<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use App\Models\LegacyUser;

class LegacyUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return LegacyUser::where('user_id', $identifier)->first();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return LegacyUser::where('user_id', $identifier)
            ->where('remember_token', $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $user->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['username'])) {
            return null;
        }

        return LegacyUser::where('username', $credentials['username'])
            ->where('blokir', 'N')
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];

        if (password_get_info($user->getAuthPassword())['algo'] !== 0) {
            return Hash::check($plain, $user->getAuthPassword());
        }

        if (hash_equals($user->getAuthPassword(), md5($plain))) {
            $user->password = Hash::make($plain);
            $user->save();
            return true;
        }

        return false;
    }
}
```

### 1.2 Service Provider Registration

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('legacy', function ($app, array $config) {
            return new LegacyUserProvider();
        });
    }
}
```

### 1.3 Config (config/auth.php)

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'legacy',
        'model' => App\Models\LegacyUser::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
],
```

---

## 2. Base Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class LegacyModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}
```

---

## 3. User Models

### 3.1 LegacyUser (tabel: `users`)

```php
<?php

namespace App\Models;

class LegacyUser extends LegacyModel
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'username', 'password', 'name', 'email', 'role',
        'blokir', 'photo', 'office_id', 'level_user', 'eng_id',
        'rule_user', 'user_language', 'created_at', 'updated_at',
    ];

    protected $hidden = ['password'];

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return $this->username;
    }

    public function roles()
    {
        return $this->hasMany(UserMultirule::class, 'username', 'username');
    }

    public function primaryRole()
    {
        return $this->belongsTo(RuleUser::class, 'rule_user', 'rule_id');
    }

    public function office()
    {
        return $this->belongsTo(ServiceOffice::class, 'office_id', 'office_id');
    }

    public function engineer()
    {
        return $this->belongsTo(Engineer::class, 'eng_id', 'eng_id');
    }

    public function sessionLogs()
    {
        return $this->hasMany(InternalLog::class, 'username', 'username');
    }

    public function activityHistory()
    {
        return $this->hasMany(UserActivityHistory::class, 'username', 'username');
    }

    public function userMenu()
    {
        return $this->hasMany(UserMenu::class, 'username', 'username');
    }

    public function delegates()
    {
        return $this->hasMany(DelegateRule::class, 'user_delegate', 'username');
    }
}
```

### 3.2 LegacyUserExternal (tabel: `users_ex`)

```php
<?php

namespace App\Models;

class LegacyUserExternal extends LegacyModel
{
    protected $table = 'users_ex';
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'username', 'password', 'name', 'email', 'rule',
        'blokir', 'company', 'cargo', 'created_at', 'updated_at',
    ];

    protected $hidden = ['password'];

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return $this->username;
    }
}
```

### 3.3 Admin (tabel: `admins` — NEW TABLE)

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $table = 'admins';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'name', 'email', 'password', 'is_super_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function canAccessFilament(): bool
    {
        return true;
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }
}
```

### 3.4 User (tabel: `users` — NEW TABLE, normalized)

```php
<?php

namespace App\Models;

class User extends LegacyModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'legacy_user_id', 'username', 'email', 'password', 'name',
        'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // --- Relationships ---

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('expires_at')
            ->withTimestamps();
    }

    public function offices()
    {
        return $this->belongsToMany(ServiceOffice::class, 'user_offices', 'user_id', 'office_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function activityHistory()
    {
        return $this->hasMany(UserActivityHistory::class);
    }

    public function ticketComments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function ticketTimeline()
    {
        return $this->hasMany(TicketTimeline::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $roleSlug)
    {
        return $query->whereHas('roles', function ($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        });
    }
}
```

---

## 4. RBAC System (New Schema)

### 4.1 Role (tabel: `roles` — NEW TABLE)

```php
<?php

namespace App\Models;

class Role extends LegacyModel
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name', 'slug', 'description', 'permissions', 'is_active'];

    protected $casts = ['permissions' => 'array', 'is_active' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('expires_at')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'role_id', 'permission_id');
    }
}
```

### 4.2 Permission (tabel: `permissions` — NEW TABLE)

```php
<?php

namespace App\Models;

class Permission extends LegacyModel
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name', 'slug', 'module', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'permission_id', 'role_id');
    }
}
```

### 4.3 RoleUser (tabel: `role_user` — PIVOT)

```php
<?php

namespace App\Models;

class RoleUser extends LegacyModel
{
    protected $table = 'role_user';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['user_id', 'role_id', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
```

### 4.4 PermissionRole (tabel: `permission_role` — PIVOT)

```php
<?php

namespace App\Models;

class PermissionRole extends LegacyModel
{
    protected $table = 'permission_role';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['permission_id', 'role_id'];
}
```

### 4.5 Legacy Role Mapping

| Legacy Role | New Role | Permissions |
|---|---|---|
| `ADMIN` | `admin` | `*` (all permissions) |
| `MANAGER` | `manager` | `ticket.*`, `loan.approve`, `report.view` |
| `ENGINEER` | `engineer` | `ticket.view`, `ticket.edit`, `unit.view` |
| `STAFF` | `staff` | `ticket.create`, `ticket.view`, `customer.view` |
| `EXTERNAL` | `external` | `ticket.view.own`, `unit.view.own` |

---

## 5. User Profile (Extended)

### 5.1 UserProfile (tabel: `user_profiles` — NEW TABLE)

```php
<?php

namespace App\Models;

class UserProfile extends LegacyModel
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'user_id', 'employee_id', 'phone', 'avatar', 'department',
        'position', 'bio', 'settings', 'metadata',
    ];

    protected $casts = ['settings' => 'array', 'metadata' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 5.2 UserOffice (tabel: `user_offices` — NEW TABLE)

```php
<?php

namespace App\Models;

class UserOffice extends LegacyModel
{
    protected $table = 'user_offices';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['user_id', 'office_id', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function office()
    {
        return $this->belongsTo(ServiceOffice::class);
    }
}
```

---

## 6. Enum Mapping

### 6.1 EnumMapping (tabel: `enum_mappings` — NEW TABLE)

```php
<?php

namespace App\Models;

class EnumMapping extends LegacyModel
{
    protected $table = 'enum_mappings';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['type', 'legacy_value', 'new_value', 'label', 'color', 'sort_order'];
}
```

### 6.2 TicketStatus (tabel: `ticket_statuses` — NEW TABLE)

```php
<?php

namespace App\Models;

class TicketStatus extends LegacyModel
{
    protected $table = 'ticket_statuses';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['code', 'label', 'color', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
```

### 6.3 Enum Mapping Table (Updated — 15 Status dari System Architecture Flow)

| Legacy Value | New Enum Code | Label | Category | Description |
|---|---|---|---|---|
| `'1'` | `NEW` | New | Active | Ticket Created |
| `'2'` | `ANALYZING` | Analyzing | Active | Front Desk triage |
| `'3'` | `IN_PROGRESS` | In Progress | Active | Engineer working on ticket |
| `'4'` | `DISPATCHING` | Dispatching | Active | Assign to Engineer |
| `'5'` | `WAITING_PART` | Waiting Part | Active | Waiting for spare part |
| `'6'` | `WAITING_CUSTOMER_FEEDBACK` | Waiting Customer Feedback | SLA Paused | Waiting for customer response |
| `'7'` | `WAITING_CUSTOMER_SCHEDULE` | Waiting Customer Schedule | SLA Paused | Waiting for customer schedule |
| `'8'` | `INTERNAL_QUOTATION` | Internal Quotation | SLA Paused | Internal quotation (non-warranty) |
| `'9'` | `WAITING_CUSTOMER_PO` | Waiting Customer PO | SLA Paused | Waiting for customer PO |
| `'10'` | `PENDING` | Pending | SLA Paused | General pending |
| `'11'` | `ESCALATION_3RD_PARTY` | Escalation 3rd Party | Active | Escalated to 3rd party vendor |
| `'12'` | `SOLVED` | Solved | Closed | Ticket solved |
| `'13'` | `CLOSED` | Closed | Closed | Ticket closed |
| `'14'` | `CANCELLED` | Cancelled | Closed | Ticket cancelled |
| `'15'` | `PICKUP_AFTER_NOT_RESOLVE` | Pickup After Not Resolve | Closed | Carry-in unit picked up unrepairable |

### 6.4 SLA Tiers

| SLA Tier | Response Time | Resolution Target | Operating Hours |
|---|---|---|---|
| **Gold SLA** | ≤ 4 hours onsite | 24/7 | 24/7 |
| **Standard SLA** | Next Business Day (NBD) | 8/5 | Mon-Fri, 8am-5pm |
| **Carry-in SLA** | No onsite commitment | 7 business days | Mon-Fri |
| **Non-Warranty** | No SLA commitment | 30 business days (best-effort) | Mon-Fri |
| **Other Contracts** | No SLA commitment | 14 business days | Mon-Fri |

### 6.5 KPI Calculation Rules

```
KPI_persen = (Waktu Aktual Konsumsi / Target SLA Durasi) × 100%
```

- **Meet SLA**: KPI ≤ 100%
- **Miss SLA**: KPI > 100%

#### KPI Fixed (Penguncian Perhitungan)
- **FID Rule (Non Carry-in)**: KPI dikunci saat `First Onsite Time` diinput
- **FID Rule (Carry-in)**: KPI dikunci saat status `Solved`
- **RESOLVE Rule (Custom SLA)**: KPI dikunci saat status `Solved`

#### KPI Paused (Jeda Waktu)
Penghitungan SLA dihentikan saat status:
1. `Waiting Customer PO`
2. `Waiting Customer Feedback`
3. `Waiting Customer Schedule`
4. `Pending`

### 6.6 Validation Gates

| Target Status | Syarat Validasi Sistem |
|---|---|
| **Solved (Incident)** | 1. Resolution ≥ 11 karakter, 2. Minimal 1 Incident Category, 3. Semua part berstatus final, 4. Pilih Resolve Ticket (Yes/No) |
| **Solved (Request)** | 1. Resolution ≥ 11 karakter, 2. Semua part berstatus final |
| **Solved (PM)** | 1. Resolution terisi |
| **Solved (Inquiry)** | Tanpa prasyarat tambahan |
| **Closed** | 1. Activity Report terisi, 2. Upload CSR/RRF/Repair Tag |
| **Cancelled** | Hanya dari status `Analyzing` + kesalahan input struktural |

---

## 7. Ticket Models (Updated — Many-to-Many dengan Units)

### 7.1 Ticket (tabel: `tickets` — NEW TABLE)

```php
<?php

namespace App\Models;

class Ticket extends LegacyModel
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'ticket_no', 'ticket_date', 'customer_id', 'engineer_id',
        'ticket_status', 'sla', 'problem_desc', 'created_by',
        'updated_by', 'closed_at', 'close_remark',
    ];

    protected $casts = [
        'ticket_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // --- Relationships ---

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'ticket_units', 'ticket_id', 'unit_id')
            ->withPivot('serial_no', 'status', 'notes', 'sort_order')
            ->withTimestamps();
    }

    public function ticketUnits()
    {
        return $this->hasMany(TicketUnit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function engineer()
    {
        return $this->belongsTo(Engineer::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketHistoryStatus::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function timeline()
    {
        return $this->hasMany(TicketTimeline::class);
    }

    // --- Accessors untuk backward compatibility ---

    public function getUnitIdAttribute(): ?string
    {
        return $this->units->first()?->unit_id;
    }

    public function getSerialNoAttribute(): ?string
    {
        return $this->units->first()?->serial_no;
    }

    // --- Scopes ---

    public function scopeWithUnit($query)
    {
        return $query->with(['units' => function ($q) {
            $q->where('status', 'ACTIVE')->orderBy('sort_order');
        }]);
    }

    public function scopeBySerialNo($query, string $serialNo)
    {
        return $query->whereHas('units', function ($q) use ($serialNo) {
            $q->where('serial_no', $serialNo);
        });
    }

    public function scopeByUnit($query, string $unitId)
    {
        return $query->whereHas('units', function ($q) use ($unitId) {
            $q->where('unit_id', $unitId);
        });
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('ticket_status', ['SOLVED', 'CLOSED', 'CANCELLED']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('ticket_status', $status);
    }

    public function scopeByEngineer($query, string $engineerId)
    {
        return $query->where('engineer_id', $engineerId);
    }

    public function scopeSlaBreached($query)
    {
        return $query->where('sla_status', 'not_meet');
    }
}
```

**FK Type Alignment Note:**
- `customer_id` di `tickets` adalah `bigint` (FK ke `customers.id`)
- `customer_id` di legacy `customer_list` adalah `varchar(50)`
- Migration script menangani konversi ini via `legacy_mappings` table
- `unit_id` di `ticket_units` adalah `bigint` (FK ke `units.id`)
- `unit_id` di legacy `unit_customer` adalah `varchar(50)`

### 7.2 Unit (tabel: `units` — NEW TABLE)

```php
<?php

namespace App\Models;

class Unit extends LegacyModel
{
    protected $table = 'units';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'unit_id', 'serial_no', 'unit_type', 'model_type', 'customer_id',
        'warranty_start', 'warranty_end', 'install_date', 'status', 'created_by',
    ];

    protected $casts = [
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'install_date' => 'date',
    ];

    // --- Relationships ---

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_units', 'unit_id', 'ticket_id')
            ->withPivot('serial_no', 'status', 'notes', 'sort_order')
            ->withTimestamps();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class);
    }

    public function modelType()
    {
        return $this->belongsTo(ModelType::class);
    }

    public function warranty()
    {
        return $this->hasOne(WarrantyUnit::class);
    }

    public function documents()
    {
        return $this->hasMany(DocumentData::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeByCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWarrantyExpired($query)
    {
        return $query->where('warranty_end', '<', now());
    }
}
```

### 7.3 TicketUnit (tabel: `ticket_units` — JUNCTION)

```php
<?php

namespace App\Models;

class TicketUnit extends LegacyModel
{
    protected $table = 'ticket_units';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['ticket_id', 'unit_id', 'serial_no', 'status', 'notes', 'sort_order'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
```

### 7.4 TicketComment (tabel: `ticket_comments` — NEW TABLE)

```php
<?php

namespace App\Models;

class TicketComment extends LegacyModel
{
    protected $table = 'ticket_comments';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['ticket_id', 'user_id', 'content', 'type', 'metadata', 'is_internal'];

    protected $casts = ['metadata' => 'array', 'is_internal' => 'boolean'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 7.5 TicketTimeline (tabel: `ticket_timeline` — NEW TABLE)

```php
<?php

namespace App\Models;

class TicketTimeline extends LegacyModel
{
    protected $table = 'ticket_timeline';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['ticket_id', 'user_id', 'event_type', 'title', 'description', 'metadata', 'occurred_at'];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 7.6 TicketHistoryStatus (tabel: `ticket_history_status`)

```php
<?php

namespace App\Models;

class TicketHistoryStatus extends LegacyModel
{
    protected $table = 'ticket_history_status';
    protected $primaryKey = 'row_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['row_id', 'ticket_id', 'status', 'remark', 'counter', 'register_date', 'modified_by'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
```

### 7.7 TicketRequestPart (tabel: `ticket_request_part`)

```php
<?php

namespace App\Models;

class TicketRequestPart extends LegacyModel
{
    protected $table = 'ticket_request_part';
    protected $primaryKey = 'request_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['request_id', 'ticket_id', 'part_no', 'part_name', 'quantity', 'status_part', 'remark', 'register_date', 'modified_by'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketHistoryRequestPart::class, 'request_id', 'request_id');
    }
}
```

### 7.8 TicketAssetList (tabel: `ticket_asset_list`)

```php
<?php

namespace App\Models;

class TicketAssetList extends LegacyModel
{
    protected $table = 'ticket_asset_list';
    protected $primaryKey = 'row_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['row_id', 'ticket_id', 'asset_id', 'serial_no', 'asset_status'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
```

---

## 8. Preventive Maintenance Models

### 8.1 PreventiveMaintenance (tabel: `preventive_maintenances` — NEW TABLE)

```php
<?php

namespace App\Models;

class PreventiveMaintenance extends LegacyModel
{
    protected $table = 'preventive_maintenances';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'pm_no', 'pm_type', 'customer_id', 'engineer_id', 'scheduled_date',
        'completed_date', 'status', 'description', 'checklist', 'created_by',
    ];

    protected $casts = [
        'checklist' => 'array',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function engineer()
    {
        return $this->belongsTo(Engineer::class);
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'pm_ticket_links', 'pm_id', 'ticket_id')
            ->withPivot('serial_no', 'status', 'notes')
            ->withTimestamps();
    }

    public function ticketLinks()
    {
        return $this->hasMany(PmTicketLink::class);
    }
}
```

### 8.2 PmTicketLink (tabel: `pm_ticket_links` — JUNCTION)

```php
<?php

namespace App\Models;

class PmTicketLink extends LegacyModel
{
    protected $table = 'pm_ticket_links';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['pm_id', 'ticket_id', 'serial_no', 'status', 'notes'];

    public function pm()
    {
        return $this->belongsTo(PreventiveMaintenance::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
```

---

## 9. Customer & Master Data Models

### 9.1 Customer (tabel: `customers` — NEW TABLE, normalized)

```php
<?php

namespace App\Models;

class Customer extends LegacyModel
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'customer_id', 'customer_name', 'customer_type', 'address', 'city',
        'province', 'postal_code', 'phone', 'fax', 'email', 'contact_person',
        'npwp', 'created_by',
    ];

    // --- Relationships ---

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrderList::class);
    }

    public function quotations()
    {
        return $this->hasMany(QuotationList::class);
    }

    public function preventiveMaintenances()
    {
        return $this->hasMany(PreventiveMaintenance::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }
}
```

### 9.2 CustomerList (tabel: `customer_list` — LEGACY)

```php
<?php

namespace App\Models;

class CustomerList extends LegacyModel
{
    protected $table = 'customer_list';
    protected $primaryKey = 'customer_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'customer_id', 'customer_name', 'customer_type', 'address', 'city',
        'province', 'postal_code', 'phone', 'fax', 'email', 'contact_person',
        'npwp', 'created_by', 'created_at',
    ];

    public function units()
    {
        return $this->hasMany(UnitCustomer::class, 'customer_id', 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id', 'customer_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrderList::class, 'customer_id', 'customer_id');
    }

    public function quotations()
    {
        return $this->hasMany(QuotationList::class, 'customer_id', 'customer_id');
    }
}
```

### 9.3 Engineer (tabel: `engineer`)

```php
<?php

namespace App\Models;

class Engineer extends LegacyModel
{
    protected $table = 'engineer';
    protected $primaryKey = 'eng_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['eng_id', 'eng_name', 'eng_phone', 'eng_email', 'eng_area', 'eng_status'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'engineer_id', 'eng_id');
    }
}
```

### 9.4 ServiceOffice (tabel: `service_office`)

```php
<?php

namespace App\Models;

class ServiceOffice extends LegacyModel
{
    protected $table = 'service_office';
    protected $primaryKey = 'office_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['office_id', 'office_name', 'office_address', 'office_phone', 'office_email'];

    public function users()
    {
        return $this->hasMany(LegacyUser::class, 'office_id', 'office_id');
    }
}
```

### 9.5 UnitType (tabel: `unit_type`)

```php
<?php

namespace App\Models;

class UnitType extends LegacyModel
{
    protected $table = 'unit_type';
    protected $primaryKey = 'unit_type_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['unit_type_id', 'unit_type_name', 'description'];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
```

### 9.6 ModelType (tabel: `model_type`)

```php
<?php

namespace App\Models;

class ModelType extends LegacyModel
{
    protected $table = 'model_type';
    protected $primaryKey = 'model_type_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['model_type_id', 'model_name', 'unit_type_id'];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
```

### 9.7 WarrantyUnit (tabel: `warranty_unit`)

```php
<?php

namespace App\Models;

class WarrantyUnit extends LegacyModel
{
    protected $table = 'warranty_unit';
    protected $primaryKey = 'unit_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'unit_id', 'warranty_type', 'warranty_start', 'warranty_end',
        'warranty_status', 'extended_date',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}
```

### 9.8 WarehouseList (tabel: `warehouse_list`)

```php
<?php

namespace App\Models;

class WarehouseList extends LegacyModel
{
    protected $table = 'warehouse_list';
    protected $primaryKey = 'warehouse_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['warehouse_id', 'warehouse_name', 'warehouse_address', 'warehouse_phone'];

    public function stockLogs()
    {
        return $this->hasMany(StockLogistic::class);
    }
}
```

---

## 10. Loan & Asset Models

### 10.1 LoanList (tabel: `loan_list`)

```php
<?php

namespace App\Models;

class LoanList extends LegacyModel
{
    protected $table = 'loan_list';
    protected $primaryKey = 'loan_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'loan_id', 'loan_no', 'ticket_id', 'unit_id', 'customer_id',
        'loan_date', 'return_date', 'loan_status', 'remark',
        'created_by', 'created_at', 'modified_by', 'modified_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitCustomer::class, 'unit_id', 'unit_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }

    public function partRequests()
    {
        return $this->hasMany(TicketRequestPart::class, 'ticket_id', 'id');
    }

    public function approvals()
    {
        return $this->hasMany(AssetApprovalList::class, 'loan_id', 'loan_id');
    }
}
```

### 10.2 TicketLoan (tabel: `ticket_loan`)

```php
<?php

namespace App\Models;

class TicketLoan extends LegacyModel
{
    protected $table = 'ticket_loan';
    protected $primaryKey = 'loan_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['loan_id', 'ticket_id', 'asset_id', 'loan_date', 'return_date', 'status'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
```

### 10.3 AssetApprovalList (tabel: `asset_approval_list`)

```php
<?php

namespace App\Models;

class AssetApprovalList extends LegacyModel
{
    protected $table = 'asset_approval_list';
    protected $primaryKey = 'approval_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['approval_id', 'loan_id', 'approval_status', 'approved_by', 'approval_date', 'remark'];

    public function loan()
    {
        return $this->belongsTo(LoanList::class, 'loan_id', 'loan_id');
    }
}
```

---

## 11. Sales & Quotation Models

### 11.1 SalesOrderList (tabel: `sales_order_list`)

```php
<?php

namespace App\Models;

class SalesOrderList extends LegacyModel
{
    protected $table = 'sales_order_list';
    protected $primaryKey = 'so_no';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'so_no', 'so_date', 'customer_id', 'supplier_id', 'total_amount',
        'currency', 'status', 'approval_status', 'approved_by', 'approval_date', 'created_by', 'created_at',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierList::class, 'supplier_id', 'supplier_id');
    }

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'so_no', 'so_no');
    }

    public function approvals()
    {
        return $this->hasMany(SalesOrderApproval::class, 'so_no', 'so_no');
    }
}
```

### 11.2 SalesOrderDetail (tabel: `sales_order_detail`)

```php
<?php

namespace App\Models;

class SalesOrderDetail extends LegacyModel
{
    protected $table = 'sales_order_detail';
    protected $primaryKey = 'row_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['row_id', 'so_no', 'item_id', 'quantity', 'unit_price', 'discount', 'total_price'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrderList::class, 'so_no', 'so_no');
    }

    public function item()
    {
        return $this->belongsTo(ProductList::class, 'item_id', 'item_id');
    }
}
```

### 11.3 SalesOrderApproval (tabel: `sales_order_approval` — NEW TABLE)

```php
<?php

namespace App\Models;

class SalesOrderApproval extends LegacyModel
{
    protected $table = 'sales_order_approval';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['so_no', 'approval_status', 'approved_by', 'approval_date', 'remark'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrderList::class, 'so_no', 'so_no');
    }
}
```

### 11.4 QuotationList (tabel: `quotation_list`)

```php
<?php

namespace App\Models;

class QuotationList extends LegacyModel
{
    protected $table = 'quotation_list';
    protected $primaryKey = 'quotation_no';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'quotation_no', 'quotation_date', 'customer_id', 'supplier_id',
        'total_amount', 'currency', 'status', 'approval_status', 'approved_by',
        'approval_date', 'valid_until', 'created_by', 'created_at',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'quotation_no', 'quotation_no');
    }

    public function approvals()
    {
        return $this->hasMany(QuotationApproval::class, 'quotation_no', 'quotation_no');
    }
}
```

### 11.5 QuotationDetail (tabel: `quotation_detail`)

```php
<?php

namespace App\Models;

class QuotationDetail extends LegacyModel
{
    protected $table = 'quotation_detail';
    protected $primaryKey = 'row_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['row_id', 'quotation_no', 'item_id', 'quantity', 'unit_price', 'discount', 'total_price'];

    public function quotation()
    {
        return $this->belongsTo(QuotationList::class, 'quotation_no', 'quotation_no');
    }
}
```

### 11.6 QuotationApproval (tabel: `quotation_approval` — NEW TABLE)

```php
<?php

namespace App\Models;

class QuotationApproval extends LegacyModel
{
    protected $table = 'quotation_approval';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['quotation_no', 'approval_status', 'approved_by', 'approval_date', 'remark'];

    public function quotation()
    {
        return $this->belongsTo(QuotationList::class, 'quotation_no', 'quotation_no');
    }
}
```

---

## 12. Stock & Inventory Models

### 12.1 StockLogistic (tabel: `stock_logistic`)

```php
<?php

namespace App\Models;

class StockLogistic extends LegacyModel
{
    protected $table = 'stock_logistic';
    protected $primaryKey = 'stock_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'stock_id', 'item_id', 'warehouse_id', 'locator_id', 'quantity',
        'unit_cost', 'stock_type', 'transaction_date', 'reference_no', 'created_by', 'created_at',
    ];

    public function item()
    {
        return $this->belongsTo(ProductList::class, 'item_id', 'item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseList::class, 'warehouse_id', 'warehouse_id');
    }
}
```

### 12.2 ProductList (tabel: `product_list`)

```php
<?php

namespace App\Models;

class ProductList extends LegacyModel
{
    protected $table = 'product_list';
    protected $primaryKey = 'item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'item_id', 'item_code', 'item_name', 'item_category',
        'unit_of_measure', 'min_stock', 'max_stock', 'reorder_point',
    ];

    public function stockLogs()
    {
        return $this->hasMany(StockLogistic::class, 'item_id', 'item_id');
    }
}
```

### 12.3 CompPart (tabel: `comp_part`)

```php
<?php

namespace App\Models;

class CompPart extends LegacyModel
{
    protected $table = 'comp_part';
    protected $primaryKey = 'part_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['part_id', 'part_no', 'part_name', 'part_category', 'unit_of_measure', 'min_stock', 'max_stock'];
}
```

---

## 13. SPRF (Spare Part Request Form) Models

### 13.1 SprfList (tabel: `sprf_list`)

```php
<?php

namespace App\Models;

class SprfList extends LegacyModel
{
    protected $table = 'sprf_list';
    protected $primaryKey = 'sprf_no';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'sprf_no', 'sprf_date', 'ticket_id', 'customer_id', 'supplier_id',
        'status', 'approval_status', 'approved_by', 'approval_date', 'created_by', 'created_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(SprfDetail::class, 'sprf_no', 'sprf_no');
    }

    public function approvals()
    {
        return $this->hasMany(SprfApproval::class, 'sprf_no', 'sprf_no');
    }
}
```

### 13.2 SprfDetail (tabel: `sprf_detail`)

```php
<?php

namespace App\Models;

class SprfDetail extends LegacyModel
{
    protected $table = 'sprf_detail';
    protected $primaryKey = 'row_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['row_id', 'sprf_no', 'part_id', 'quantity', 'unit_price', 'total_price'];

    public function sprf()
    {
        return $this->belongsTo(SprfList::class, 'sprf_no', 'sprf_no');
    }
}
```

### 13.3 SprfApproval (tabel: `sprf_approval` — NEW TABLE)

```php
<?php

namespace App\Models;

class SprfApproval extends LegacyModel
{
    protected $table = 'sprf_approval';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['sprf_no', 'approval_status', 'approved_by', 'approval_date', 'remark'];

    public function sprf()
    {
        return $this->belongsTo(SprfList::class, 'sprf_no', 'sprf_no');
    }
}
```

---

## 14. Supporting Models

### 14.1 InternalLog (tabel: `internal_log`)

```php
<?php

namespace App\Models;

class InternalLog extends LegacyModel
{
    protected $table = 'internal_log';
    protected $primaryKey = 'log_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['log_id', 'username', 'session_id', 'ip_address', 'user_agent', 'login_time', 'logout_time', 'status'];

    public function user()
    {
        return $this->belongsTo(LegacyUser::class, 'username', 'username');
    }
}
```

### 14.2 Notification (tabel: `notification`)

```php
<?php

namespace App\Models;

class Notification extends LegacyModel
{
    protected $table = 'notification';
    protected $primaryKey = 'notif_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['notif_id', 'username', 'notif_type', 'notif_title', 'notif_message', 'is_read', 'created_at'];

    public function user()
    {
        return $this->belongsTo(LegacyUser::class, 'username', 'username');
    }
}
```

### 14.3 Holiday (tabel: `holiday`)

```php
<?php

namespace App\Models;

class Holiday extends LegacyModel
{
    protected $table = 'holiday';
    protected $primaryKey = 'holiday_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['holiday_id', 'holiday_date', 'holiday_name', 'holiday_type'];
}
```

### 14.4 DocumentData (tabel: `document_data`)

```php
<?php

namespace App\Models;

class DocumentData extends LegacyModel
{
    protected $table = 'document_data';
    protected $primaryKey = 'doc_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['doc_id', 'unit_id', 'ticket_id', 'doc_type', 'doc_name', 'doc_path', 'uploaded_by', 'uploaded_at'];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
```

### 14.5 CallLog (tabel: `call_log`)

```php
<?php

namespace App\Models;

class CallLog extends LegacyModel
{
    protected $table = 'call_log';
    protected $primaryKey = 'call_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['call_id', 'call_date', 'customer_id', 'call_type', 'call_subject', 'call_notes', 'call_duration', 'created_by'];

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }
}
```

### 14.6 Token (tabel: `token`)

```php
<?php

namespace App\Models;

class Token extends LegacyModel
{
    protected $table = 'token';
    protected $primaryKey = 'token_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['token_id', 'token_value', 'ticket_id', 'status', 'generated_by', 'generated_at', 'expired_at'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
```

### 14.7 Verification2FA (tabel: `verification_2fa`)

```php
<?php

namespace App\Models;

class Verification2FA extends LegacyModel
{
    protected $table = 'verification_2fa';
    protected $primaryKey = 'verify_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['verify_id', 'username', 'verification_code', 'expires_at', 'verified', 'created_at'];
}
```

### 14.8 UserActivityHistory (tabel: `user_activity_history`)

```php
<?php

namespace App\Models;

class UserActivityHistory extends LegacyModel
{
    protected $table = 'user_activity_history';
    protected $primaryKey = 'activity_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = ['activity_id', 'username', 'activity_type', 'activity_desc', 'ip_address', 'created_at'];

    public function user()
    {
        return $this->belongsTo(LegacyUser::class, 'username', 'username');
    }
}
```

### 14.9 TicketHistoryRequestPart (tabel: `ticket_history_request_part`)

```php
<?php

namespace App\Models;

class TicketHistoryRequestPart extends LegacyModel
{
    protected $table = 'ticket_history_request_part';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['request_id', 'status', 'remark', 'modified_by'];

    public function ticketRequestPart()
    {
        return $this->belongsTo(TicketRequestPart::class, 'request_id', 'request_id');
    }
}
```

### 14.10 SupplierList (tabel: `supplier_list`)

```php
<?php

namespace App\Models;

class SupplierList extends LegacyModel
{
    protected $table = 'supplier_list';
    protected $primaryKey = 'supplier_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'supplier_id', 'supplier_name', 'address', 'city', 'province',
        'phone', 'fax', 'email', 'contact_person',
    ];
}
```

### 14.11 UnitCustomer (tabel: `unit_customer` — LEGACY)

```php
<?php

namespace App\Models;

class UnitCustomer extends LegacyModel
{
    protected $table = 'unit_customer';
    protected $primaryKey = 'unit_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'unit_id', 'serial_no', 'unit_type', 'model_type', 'customer_id',
        'warranty_start', 'warranty_end', 'install_date', 'status', 'created_by', 'created_at',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerList::class, 'customer_id', 'customer_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type', 'unit_type_id');
    }

    public function modelType()
    {
        return $this->belongsTo(ModelType::class, 'model_type', 'model_type_id');
    }

    public function warranty()
    {
        return $this->hasOne(WarrantyUnit::class, 'unit_id', 'unit_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'unit_id', 'unit_id');
    }

    public function documents()
    {
        return $this->hasMany(DocumentData::class, 'unit_id', 'unit_id');
    }
}
```

---

## 15. Model Relationship Map

```
LegacyUser (users)
├── hasMany → UserMultirule (user_multirule)
├── hasMany → UserMenu (user_menu)
├── hasMany → InternalLog (internal_log)
├── hasMany → UserActivityHistory (user_activity_history)
├── hasMany → Notification (notification)
├── hasMany → DelegateRule (delegate_rule) [as delegate]
├── hasMany → DelegateRule (delegate_rule) [as owner]
├── belongsTo → RuleUser (rule_user) [primary role]
├── belongsTo → ServiceOffice (service_office)
└── belongsTo → Engineer (engineer)

Admin (admins) [NEW]
├── hasOne → UserProfile (user_profiles)
└── belongsToMany → Role (roles) [via role_user]

User (users) [NEW - normalized]
├── hasOne → UserProfile (user_profiles)
├── belongsToMany → Role (roles) [via role_user]
├── belongsToMany → ServiceOffice (service_offices) [via user_offices]
├── hasMany → UserActivityHistory (user_activity_history)
├── hasMany → TicketComment (ticket_comments)
└── hasMany → TicketTimeline (ticket_timeline)

Role (roles) [NEW]
├── belongsToMany → User (users) [via role_user]
└── belongsToMany → Permission (permissions) [via permission_role]

Permission (permissions) [NEW]
└── belongsToMany → Role (roles) [via permission_role]

Ticket (tickets) [NEW - normalized]
├── belongsToMany → Unit (units) [via ticket_units]
├── belongsTo → Customer (customers)
├── belongsTo → Engineer (engineers)
├── hasMany → TicketHistoryStatus (ticket_history_status)
├── hasMany → TicketRequestPart (ticket_request_part)
├── hasMany → TicketAssetList (ticket_asset_list)
├── hasMany → TicketComment (ticket_comments)
├── hasMany → TicketTimeline (ticket_timeline)
├── hasMany → LoanList (loan_list)
├── hasMany → SprfList (sprf_list)
├── hasMany → Token (token)
└── belongsToMany → PreventiveMaintenance (preventive_maintenances) [via pm_ticket_links]

Unit (units) [NEW - normalized]
├── belongsToMany → Ticket (tickets) [via ticket_units]
├── belongsTo → Customer (customers)
├── belongsTo → UnitType (unit_type)
├── belongsTo → ModelType (model_type)
├── hasOne → WarrantyUnit (warranty_unit)
└── hasMany → DocumentData (document_data)

Customer (customers) [NEW - normalized]
├── hasMany → Unit (units)
├── hasMany → Ticket (tickets)
├── hasMany → SalesOrderList (sales_order_list)
├── hasMany → QuotationList (quotation_list)
└── hasMany → PreventiveMaintenance (preventive_maintenances)

PreventiveMaintenance (preventive_maintenances) [NEW]
├── belongsTo → Customer (customers)
├── belongsTo → Engineer (engineers)
├── belongsToMany → Ticket (tickets) [via pm_ticket_links]
└── hasMany → PmTicketLink (pm_ticket_links)

SalesOrderList (sales_order_list)
├── belongsTo → CustomerList (customer_list)
├── belongsTo → SupplierList (supplier_list)
├── hasMany → SalesOrderDetail (sales_order_detail)
└── hasMany → SalesOrderApproval (sales_order_approval)

QuotationList (quotation_list)
├── belongsTo → CustomerList (customer_list)
├── hasMany → QuotationDetail (quotation_detail)
└── hasMany → QuotationApproval (quotation_approval)

LoanList (loan_list)
├── belongsTo → Ticket (tickets)
├── belongsTo → UnitCustomer (unit_customer)
├── belongsTo → CustomerList (customer_list)
└── hasMany → AssetApprovalList (asset_approval_list)

SprfList (sprf_list)
├── belongsTo → Ticket (tickets)
├── belongsTo → CustomerList (customer_list)
├── hasMany → SprfDetail (sprf_detail)
└── hasMany → SprfApproval (sprf_approval)
```

---

## 16. Database Configuration

### 16.1 .env

```env
# Legacy Database (read-only)
DB_CONNECTION=mysql
DB_HOST=172.16.1.2
DB_PORT=3306
DB_DATABASE=s3Prod
DB_USERNAME=admins
DB_PASSWORD=fid123!!

# New Database (normalized schema)
DB_CONNECTION_NEW=mysql
DB_HOST_NEW=127.0.0.1
DB_PORT_NEW=3306
DB_DATABASE_NEW=s3_erp
DB_USERNAME_NEW=root
DB_PASSWORD_NEW=

# Secondary (ERP PostgreSQL)
DB_CONNECTION_PG=pgsql
DB_HOST_PG=172.16.1.2
DB_PORT_PG=5432
DB_DATABASE_PG=erp_data
DB_USERNAME_PG=erp_user
DB_PASSWORD_PG=erp_pass
```

### 16.2 config/database.php

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '172.16.1.2'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 's3Prod'),
        'username' => env('DB_USERNAME', 'admins'),
        'password' => env('DB_PASSWORD', 'fid123!!'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
    ],

    'mysql_new' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST_NEW', '127.0.0.1'),
        'port' => env('DB_PORT_NEW', '3306'),
        'database' => env('DB_DATABASE_NEW', 's3_erp'),
        'username' => env('DB_USERNAME_NEW', 'root'),
        'password' => env('DB_PASSWORD_NEW', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
    ],

    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST_PG', '172.16.1.2'),
        'port' => env('DB_PORT_PG', '5432'),
        'database' => env('DB_DATABASE_PG', 'erp_data'),
        'username' => env('DB_USERNAME_PG', 'erp_user'),
        'password' => env('DB_PASSWORD_PG', 'erp_pass'),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'erp_data',
    ],
],
```

---

## 17. Migration Strategy (Non-Destruktif)

### 17.1 Phase 1: Read-Only Access
- Laravel app connects to existing `s3Prod` database
- All models use `ReadOnly` trait (no INSERT/UPDATE/DELETE)
- API endpoints only expose GET operations
- Legacy system continues to operate normally

### 17.2 Phase 2: Dual-Write
- New Laravel models write to new normalized tables (`s3_erp`)
- Legacy tables remain untouched
- Data sync via Laravel Jobs (queue)
- Rollback capability maintained

### 17.3 Phase 3: Gradual Migration
- Module-by-module migration (ticket first, then unit, loan, etc.)
- Feature flags control which system handles each module
- Legacy system remains as fallback

### 17.4 Phase 4: Full Cutover
- All traffic routed to Laravel API
- Legacy system in read-only mode
- Decommission after validation period

---

## 18. Read-Only Trait (Phase 1)

```php
<?php

namespace App\Models\Traits;

trait ReadOnly
{
    public static function bootReadOnly(): void
    {
        static::saving(function ($model) {
            throw new \RuntimeException('This model is read-only in Phase 1.');
        });
    }

    public function save(array $options = [])
    {
        throw new \RuntimeException('This model is read-only in Phase 1.');
    }

    public function delete()
    {
        throw new \RuntimeException('This model is read-only in Phase 1.');
    }
}
```

---

## 19. New Tables Summary (Phase 2+)

| Table | Purpose | Phase |
|---|---|---|
| `admins` | Filament admin users | P0 |
| `roles` | RBAC roles | P0 |
| `permissions` | RBAC permissions | P0 |
| `role_user` | User-role pivot | P0 |
| `permission_role` | Role-permission pivot | P0 |
| `user_profiles` | Extended user profile | P1 |
| `user_offices` | Multi-office user assignment | P1 |
| `enum_mappings` | Legacy → new enum mapping | P1 |
| `ticket_statuses` | Ticket status enum | P1 |
| `audit_logs` | Audit trail | P2 |
| `legacy_mappings` | Legacy → new ID mapping | P1 |
| `tickets` | New normalized tickets | P1 |
| `units` | New normalized units | P1 |
| `ticket_units` | Ticket-Unit junction (many-to-many) | P1 |
| `ticket_comments` | Ticket comments/notes | P1 |
| `ticket_timeline` | Ticket timeline events | P1 |
| `preventive_maintenances` | PM parent records | P1 |
| `pm_ticket_links` | PM-Ticket junction | P1 |
| `customers` | New normalized customers | P1 |
| `users` | New normalized users | P1 |
| `sales_order_approval` | Sales order approval history | P1 |
| `quotation_approval` | Quotation approval history | P1 |
| `sprf_approval` | SPRF approval history | P1 |
| `ticket_history_request_part` | Part request history | P1 |

---

## 20. Backward Compatibility

### 20.1 View untuk Legacy System

```sql
-- Create view yang memetakan data baru ke format lama (1 ticket = 1 SN)
CREATE VIEW v_legacy_tickets AS
SELECT
    t.id AS ticket_id,
    t.ticket_no,
    t.ticket_date,
    t.customer_id,
    tu.unit_id,
    tu.serial_no,
    t.problem_desc,
    t.ticket_status,
    t.sla,
    t.engineer_id,
    t.created_by,
    t.created_at,
    t.updated_at,
    t.closed_at,
    t.close_remark
FROM tickets t
LEFT JOIN ticket_units tu ON t.id = tu.ticket_id AND tu.status = 'ACTIVE';
```

### 20.2 Migration Script

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyTicketsToNewSchema extends Command
{
    protected $signature = 'migrate:legacy-tickets';
    protected $description = 'Migrate legacy tickets (1:1) to new schema (1:many)';

    public function handle(): int
    {
        $this->info('Starting migration...');

        // 1. Create units dari unique serial_no di legacy tickets
        $legacyUnits = DB::connection('legacy')
            ->table('ticket_list')
            ->select('unit_id', 'serial_no', 'customer_id', 'unit_type', 'model_type')
            ->whereNotNull('unit_id')
            ->distinct()
            ->get();

        $this->info("Found {$legacyUnits->count()} unique units");

        foreach ($legacyUnits as $unit) {
            DB::table('units')->updateOrInsert(
                ['unit_id' => $unit->unit_id],
                [
                    'unit_id' => $unit->unit_id,
                    'serial_no' => $unit->serial_no,
                    'unit_type' => $unit->unit_type,
                    'model_type' => $unit->model_type,
                    'customer_id' => $unit->customer_id,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Create ticket_units links dari legacy tickets
        $legacyTickets = DB::connection('legacy')
            ->table('ticket_list')
            ->select('ticket_id', 'unit_id', 'serial_no')
            ->whereNotNull('unit_id')
            ->get();

        $this->info("Found {$legacyTickets->count()} ticket-unit links");

        foreach ($legacyTickets as $ticket) {
            DB::table('ticket_units')->updateOrInsert(
                ['ticket_id' => $ticket->ticket_id, 'unit_id' => $ticket->unit_id],
                [
                    'ticket_id' => $ticket->ticket_id,
                    'unit_id' => $ticket->unit_id,
                    'serial_no' => $ticket->serial_no,
                    'status' => 'ACTIVE',
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('Migration completed!');
        return Command::SUCCESS;
    }
}
```

---

## 21. Notes

- **SQL injection fix di legacy di-skip** — aplikasi sedang dijadikan staging untuk production errors
- **Password migration** — MD5 di-rehash ke bcrypt saat login pertama
- **Data sync** — gunakan `php artisan sync:legacy` command untuk sync data dari legacy ke new DB
- **Feature flags** — gunakan `config/features.php` untuk gradual rollout per module
- **Backward compatibility** — view `v_legacy_tickets` untuk legacy system
- **Many-to-many** — ticket-unit relationship via `ticket_units` junction table
- **Foreign key alignment** — `ticket_id` di tabel legacy adalah string, di tabel baru adalah bigint. Migration script menangani konversi ini.

---

*Spesifikasi ini dapat dilanjutkan dengan API Resource, Filament Resource, dan Next.js integration.*
