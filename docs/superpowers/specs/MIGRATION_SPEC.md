# Spesifikasi Migration Database — S3 System Migration

**Constraint:** Non-destruktif, backward compatible, zero downtime.
**Update:** Migration details untuk 24 tabel baru, FK type alignment, health check endpoint.

---

## 1. Migration Details — 24 Tabel Baru

### 1.1 Tabel: `users` (Normalized)

```php
// database/migrations/2026_10_01_000001_create_users_table.php

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('legacy_user_id', 50)->nullable()->index(); // Link ke legacy user_id
    $table->string('username', 50)->unique();
    $table->string('email', 100)->nullable()->unique();
    $table->string('password');
    $table->string('name', 100);
    $table->string('role', 50)->nullable(); // Legacy role
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('legacy_user_id');
    $table->index('role');
    $table->index('is_active');
});
```

### 1.2 Tabel: `customers` (Normalized)

```php
// database/migrations/2026_10_01_000002_create_customers_table.php

Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('customer_id', 50)->unique(); // Legacy customer_id
    $table->string('customer_name', 200);
    $table->string('customer_type', 50)->nullable();
    $table->text('address')->nullable();
    $table->string('city', 100)->nullable();
    $table->string('province', 100)->nullable();
    $table->string('postal_code', 20)->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('fax', 50)->nullable();
    $table->string('email', 100)->nullable();
    $table->string('contact_person', 100)->nullable();
    $table->string('npwp', 50)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('customer_id');
    $table->index('customer_type');
    $table->index('is_active');
});
```

### 1.3 Tabel: `tickets` (Normalized)

```php
// database/migrations/2026_10_01_000003_create_tickets_table.php

Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->string('ticket_no', 50)->unique();
    $table->date('ticket_date');
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('engineer_id')->nullable()->constrained('engineers');
    $table->string('ticket_status', 20)->default('NEW'); // 15 status
    $table->string('sla', 10)->nullable(); // Gold, Standard, Carry-in, Non-Warranty, Other
    $table->text('problem_desc')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamp('closed_at')->nullable();
    $table->text('close_remark')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('ticket_no');
    $table->index('customer_id');
    $table->index('engineer_id');
    $table->index('ticket_status');
    $table->index('sla');
    $table->index('ticket_date');
    $table->index(['ticket_status', 'engineer_id']);
    $table->index(['ticket_status', 'customer_id']);
});
```

### 1.4 Tabel: `units` (Normalized)

```php
// database/migrations/2026_10_01_000004_create_units_table.php

Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->string('unit_id', 50)->unique(); // Legacy unit_id
    $table->string('serial_no', 100)->unique();
    $table->string('unit_type', 50)->nullable();
    $table->string('model_type', 50)->nullable();
    $table->foreignId('customer_id')->constrained('customers');
    $table->date('warranty_start')->nullable();
    $table->date('warranty_end')->nullable();
    $table->date('install_date')->nullable();
    $table->string('status', 20)->default('ACTIVE');
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('unit_id');
    $table->index('serial_no');
    $table->index('customer_id');
    $table->index('unit_type');
    $table->index('status');
});
```

### 1.5 Tabel: `ticket_units` (Junction)

```php
// database/migrations/2026_10_01_000005_create_ticket_units_table.php

Schema::create('ticket_units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
    $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
    $table->string('serial_no', 100); // Denormalized
    $table->string('status', 20)->default('ACTIVE'); // ACTIVE, REMOVED
    $table->text('notes')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    // Indexes
    $table->unique(['ticket_id', 'unit_id']);
    $table->index('serial_no');
    $table->index('status');
});
```

### 1.6 Tabel: `ticket_comments`

```php
// database/migrations/2026_10_01_000006_create_ticket_comments_table.php

Schema::create('ticket_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->text('content');
    $table->string('type', 20)->default('comment'); // comment, note, status_change
    $table->json('metadata')->nullable();
    $table->boolean('is_internal')->default(false);
    $table->timestamps();

    // Indexes
    $table->index('ticket_id');
    $table->index('user_id');
    $table->index('type');
    $table->index('is_internal');
});
```

### 1.7 Tabel: `ticket_timeline`

```php
// database/migrations/2026_10_01_000007_create_ticket_timeline_table.php

Schema::create('ticket_timeline', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained('users');
    $table->string('event_type', 50); // created, status_changed, comment_added, assigned, unit_added, unit_removed
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('occurred_at');
    $table->timestamps();

    // Indexes
    $table->index('ticket_id');
    $table->index('user_id');
    $table->index('event_type');
    $table->index('occurred_at');
});
```

### 1.8 Tabel: `preventive_maintenances`

```php
// database/migrations/2026_10_01_000008_create_preventive_maintenances_table.php

Schema::create('preventive_maintenances', function (Blueprint $table) {
    $table->id();
    $table->string('pm_no', 50)->unique();
    $table->string('pm_type', 20); // monthly, quarterly, annual
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('engineer_id')->nullable()->constrained('engineers');
    $table->date('scheduled_date');
    $table->date('completed_date')->nullable();
    $table->string('status', 20)->default('PLANNED'); // PLANNED, IN_PROGRESS, COMPLETED, CANCELLED
    $table->text('description')->nullable();
    $table->json('checklist')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('pm_no');
    $table->index('customer_id');
    $table->index('engineer_id');
    $table->index('status');
    $table->index('scheduled_date');
    $table->index('pm_type');
});
```

### 1.9 Tabel: `pm_ticket_links` (Junction)

```php
// database/migrations/2026_10_01_000009_create_pm_ticket_links_table.php

Schema::create('pm_ticket_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pm_id')->constrained('preventive_maintenances')->onDelete('cascade');
    $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
    $table->string('serial_no', 100); // Denormalized
    $table->string('status', 20)->default('PENDING'); // PENDING, COMPLETED, SKIPPED
    $table->text('notes')->nullable();
    $table->timestamps();

    // Indexes
    $table->unique(['pm_id', 'ticket_id']);
    $table->index('serial_no');
    $table->index('status');
});
```

### 1.10 Tabel: `admins`

```php
// database/migrations/2026_10_01_000010_create_admins_table.php

Schema::create('admins', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('email', 100)->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->boolean('is_super_admin')->default(false);
    $table->rememberToken();
    $table->timestamps();

    // Indexes
    $table->index('email');
    $table->index('is_super_admin');
});
```

### 1.11 Tabel: `roles`

```php
// database/migrations/2026_10_01_000011_create_roles_table.php

Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();
    $table->string('slug', 50)->unique();
    $table->string('description')->nullable();
    $table->json('permissions')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('is_active');
});
```

### 1.12 Tabel: `permissions`

```php
// database/migrations/2026_10_01_000012_create_permissions_table.php

Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100)->unique();
    $table->string('slug', 100)->unique();
    $table->string('module', 50);
    $table->string('description')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('module');
});
```

### 1.13 Tabel: `role_user` (Pivot)

```php
// database/migrations/2026_10_01_000013_create_role_user_table.php

Schema::create('role_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->unique(['user_id', 'role_id']);
    $table->index('expires_at');
});
```

### 1.14 Tabel: `permission_role` (Pivot)

```php
// database/migrations/2026_10_01_000014_create_permission_role_table.php

Schema::create('permission_role', function (Blueprint $table) {
    $table->id();
    $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
    $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
    $table->timestamps();

    // Indexes
    $table->unique(['permission_id', 'role_id']);
});
```

### 1.15 Tabel: `user_profiles`

```php
// database/migrations/2026_10_01_000015_create_user_profiles_table.php

Schema::create('user_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('employee_id', 50)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('avatar')->nullable();
    $table->string('department', 100)->nullable();
    $table->string('position', 100)->nullable();
    $table->text('bio')->nullable();
    $table->json('settings')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('user_id');
    $table->index('employee_id');
});
```

### 1.16 Tabel: `user_offices` (Pivot)

```php
// database/migrations/2026_10_01_000016_create_user_offices_table.php

Schema::create('user_offices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('office_id')->constrained('service_offices')->onDelete('cascade');
    $table->boolean('is_primary')->default(false);
    $table->timestamps();

    // Indexes
    $table->unique(['user_id', 'office_id']);
    $table->index('is_primary');
});
```

### 1.17 Tabel: `enum_mappings`

```php
// database/migrations/2026_10_01_000017_create_enum_mappings_table.php

Schema::create('enum_mappings', function (Blueprint $table) {
    $table->id();
    $table->string('type', 50); // ticket_status, loan_status, etc.
    $table->string('legacy_value', 50);
    $table->string('new_value', 50);
    $table->string('label', 100);
    $table->string('color', 20)->default('gray');
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    // Indexes
    $table->unique(['type', 'legacy_value']);
    $table->index('type');
    $table->index('new_value');
});
```

### 1.18 Tabel: `ticket_statuses`

```php
// database/migrations/2026_10_01_000018_create_ticket_statuses_table.php

Schema::create('ticket_statuses', function (Blueprint $table) {
    $table->id();
    $table->string('code', 20)->unique();
    $table->string('label', 100);
    $table->string('color', 20)->default('gray');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Indexes
    $table->index('code');
    $table->index('is_active');
});
```

### 1.19 Tabel: `sales_order_approval`

```php
// database/migrations/2026_10_01_000019_create_sales_order_approval_table.php

Schema::create('sales_order_approval', function (Blueprint $table) {
    $table->id();
    $table->string('so_no', 50);
    $table->string('approval_status', 20); // PENDING, APPROVED, REJECTED
    $table->string('approved_by', 100)->nullable();
    $table->timestamp('approval_date')->nullable();
    $table->text('remark')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('so_no');
    $table->index('approval_status');
});
```

### 1.20 Tabel: `quotation_approval`

```php
// database/migrations/2026_10_01_000020_create_quotation_approval_table.php

Schema::create('quotation_approval', function (Blueprint $table) {
    $table->id();
    $table->string('quotation_no', 50);
    $table->string('approval_status', 20); // PENDING, APPROVED, REJECTED
    $table->string('approved_by', 100)->nullable();
    $table->timestamp('approval_date')->nullable();
    $table->text('remark')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('quotation_no');
    $table->index('approval_status');
});
```

### 1.21 Tabel: `sprf_approval`

```php
// database/migrations/2026_10_01_000021_create_sprf_approval_table.php

Schema::create('sprf_approval', function (Blueprint $table) {
    $table->id();
    $table->string('sprf_no', 50);
    $table->string('approval_status', 20); // PENDING, APPROVED, REJECTED
    $table->string('approved_by', 100)->nullable();
    $table->timestamp('approval_date')->nullable();
    $table->text('remark')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('sprf_no');
    $table->index('approval_status');
});
```

### 1.22 Tabel: `ticket_history_request_part`

```php
// database/migrations/2026_10_01_000022_create_ticket_history_request_part_table.php

Schema::create('ticket_history_request_part', function (Blueprint $table) {
    $table->id();
    $table->string('request_id', 50);
    $table->string('status', 20);
    $table->text('remark')->nullable();
    $table->string('modified_by', 100)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('request_id');
    $table->index('status');
});
```

### 1.23 Tabel: `legacy_mappings`

```php
// database/migrations/2026_10_01_000023_create_legacy_mappings_table.php

Schema::create('legacy_mappings', function (Blueprint $table) {
    $table->id();
    $table->string('legacy_table', 50);
    $table->string('legacy_id', 100);
    $table->string('new_table', 50);
    $table->string('new_id', 100);
    $table->json('metadata')->nullable();
    $table->timestamp('migrated_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->unique(['legacy_table', 'legacy_id']);
    $table->index(['new_table', 'new_id']);
    $table->index('migrated_at');
});
```

### 1.24 Tabel: `audit_logs`

```php
// database/migrations/2026_10_01_000024_create_audit_logs_table.php

Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type', 50);
    $table->string('entity_type', 50);
    $table->string('entity_id', 100);
    $table->string('old_value', 100)->nullable();
    $table->string('new_value', 100)->nullable();
    $table->string('changed_by', 100)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at');

    // Indexes
    $table->index('event_type');
    $table->index('entity_type');
    $table->index('entity_id');
    $table->index('changed_by');
    $table->index('created_at');
});
```

---

## 2. FK Type Alignment Fixes

### 2.1 Masalah

| Tabel Legacy | Column | Type Legacy | Type New | Issue |
|---|---|---|---|---|
| `ticket_list` | `ticket_id` | varchar(50) | bigint | Type mismatch |
| `unit_customer` | `unit_id` | varchar(50) | bigint | Type mismatch |
| `customer_list` | `customer_id` | varchar(50) | bigint | Type mismatch |
| `loan_list` | `loan_id` | varchar(50) | bigint | Type mismatch |
| `sales_order_list` | `so_no` | varchar(50) | bigint | Type mismatch |
| `quotation_list` | `quotation_no` | varchar(50) | bigint | Type mismatch |
| `sprf_list` | `sprf_no` | varchar(50) | bigint | Type mismatch |

### 2.2 Solusi

**Opsi A (Recommended):** Keep legacy IDs as string di new tables

```php
// tickets table
$table->string('legacy_ticket_id', 50)->nullable()->index();
$table->foreignId('customer_id')->constrained('customers');

// units table
$table->string('legacy_unit_id', 50)->nullable()->index();
$table->foreignId('customer_id')->constrained('customers');
```

**Opsi B:** Convert legacy IDs ke bigint di migration script

```php
// Migration script converts string IDs to bigint
DB::table('tickets')->update([
    'legacy_ticket_id' => DB::raw('CAST(legacy_ticket_id AS UNSIGNED)')
]);
```

### 2.3 Recommended Approach

**Gunakan Opsi A** — keep legacy IDs sebagai string di new tables untuk backward compatibility. Foreign key constraints hanya untuk relasi internal (new tables).

---

## 3. Health Check Endpoint

### 3.1 Route

```php
// routes/api.php
Route::get('/health', [Api\HealthController::class, 'index']);
```

### 3.2 Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function index()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'legacy_database' => $this->checkLegacyDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection('mysql_new')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkLegacyDatabase(): bool
    {
        try {
            DB::connection('mysql')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        $size = Queue::size('default');
        return $size < 1000;
    }
}
```

### 3.3 Feature Test

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    public function test_health_check_returns_200_when_all_services_healthy(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => true,
                    'legacy_database' => true,
                    'redis' => true,
                    'queue' => true,
                ],
            ]);
    }

    public function test_health_check_returns_503_when_database_down(): void
    {
        // Mock database connection to fail
        config(['database.connections.mysql_new.host' => 'invalid-host']);

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => false,
                ],
            ]);
    }
}
```

---

## 4. Migration Order

```
1.  create_users_table
2.  create_customers_table
3.  create_tickets_table
4.  create_units_table
5.  create_ticket_units_table
6.  create_ticket_comments_table
7.  create_ticket_timeline_table
8.  create_preventive_maintenances_table
9.  create_pm_ticket_links_table
10. create_admins_table
11. create_roles_table
12. create_permissions_table
13. create_role_user_table
14. create_permission_role_table
15. create_user_profiles_table
16. create_user_offices_table
17. create_enum_mappings_table
18. create_ticket_statuses_table
19. create_sales_order_approval_table
20. create_quotation_approval_table
21. create_sprf_approval_table
22. create_ticket_history_request_part_table
23. create_legacy_mappings_table
24. create_audit_logs_table
```

---

## 5. Backward Compatibility Views

### 5.1 v_legacy_tickets

```sql
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

### 5.2 v_legacy_units

```sql
CREATE VIEW v_legacy_units AS
SELECT
    u.id AS unit_id,
    u.serial_no,
    u.unit_type,
    u.model_type,
    u.customer_id,
    u.warranty_start,
    u.warranty_end,
    u.install_date,
    u.status,
    u.created_by,
    u.created_at
FROM units u;
```

---

## 6. Seeders

### 6.1 RoleSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['*']],
            ['name' => 'Manager', 'slug' => 'manager', 'permissions' => ['ticket.*', 'loan.approve', 'report.view']],
            ['name' => 'Engineer', 'slug' => 'engineer', 'permissions' => ['ticket.view', 'ticket.edit', 'unit.view']],
            ['name' => 'Staff', 'slug' => 'staff', 'permissions' => ['ticket.create', 'ticket.view', 'customer.view']],
            ['name' => 'External', 'slug' => 'external', 'permissions' => ['ticket.view.own', 'unit.view.own']],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
```

### 6.2 PermissionSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'ticket.create', 'slug' => 'ticket.create', 'module' => 'ticket'],
            ['name' => 'ticket.view', 'slug' => 'ticket.view', 'module' => 'ticket'],
            ['name' => 'ticket.edit', 'slug' => 'ticket.edit', 'module' => 'ticket'],
            ['name' => 'ticket.solve', 'slug' => 'ticket.solve', 'module' => 'ticket'],
            ['name' => 'ticket.close', 'slug' => 'ticket.close', 'module' => 'ticket'],
            ['name' => 'loan.approve', 'slug' => 'loan.approve', 'module' => 'loan'],
            ['name' => 'report.view', 'slug' => 'report.view', 'module' => 'report'],
            ['name' => 'unit.view', 'slug' => 'unit.view', 'module' => 'unit'],
            ['name' => 'unit.edit', 'slug' => 'unit.edit', 'module' => 'unit'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
```

### 6.3 AdminSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@find-service.co.id',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);
    }
}
```

### 6.4 EnumMappingSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnumMapping;

class EnumMappingSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            // Ticket Status
            ['type' => 'ticket_status', 'legacy_value' => '1', 'new_value' => 'NEW', 'label' => 'New', 'color' => 'warning', 'sort_order' => 1],
            ['type' => 'ticket_status', 'legacy_value' => '2', 'new_value' => 'ANALYZING', 'label' => 'Analyzing', 'color' => 'info', 'sort_order' => 2],
            ['type' => 'ticket_status', 'legacy_value' => '3', 'new_value' => 'IN_PROGRESS', 'label' => 'In Progress', 'color' => 'primary', 'sort_order' => 3],
            ['type' => 'ticket_status', 'legacy_value' => '4', 'new_value' => 'DISPATCHING', 'label' => 'Dispatching', 'color' => 'primary', 'sort_order' => 4],
            ['type' => 'ticket_status', 'legacy_value' => '5', 'new_value' => 'WAITING_PART', 'label' => 'Waiting Part', 'color' => 'warning', 'sort_order' => 5],
            ['type' => 'ticket_status', 'legacy_value' => '6', 'new_value' => 'WAITING_CUSTOMER_FEEDBACK', 'label' => 'Waiting Customer Feedback', 'color' => 'warning', 'sort_order' => 6],
            ['type' => 'ticket_status', 'legacy_value' => '7', 'new_value' => 'WAITING_CUSTOMER_SCHEDULE', 'label' => 'Waiting Customer Schedule', 'color' => 'warning', 'sort_order' => 7],
            ['type' => 'ticket_status', 'legacy_value' => '8', 'new_value' => 'INTERNAL_QUOTATION', 'label' => 'Internal Quotation', 'color' => 'warning', 'sort_order' => 8],
            ['type' => 'ticket_status', 'legacy_value' => '9', 'new_value' => 'WAITING_CUSTOMER_PO', 'label' => 'Waiting Customer PO', 'color' => 'warning', 'sort_order' => 9],
            ['type' => 'ticket_status', 'legacy_value' => '10', 'new_value' => 'PENDING', 'label' => 'Pending', 'color' => 'warning', 'sort_order' => 10],
            ['type' => 'ticket_status', 'legacy_value' => '11', 'new_value' => 'ESCALATION_3RD_PARTY', 'label' => 'Escalation 3rd Party', 'color' => 'danger', 'sort_order' => 11],
            ['type' => 'ticket_status', 'legacy_value' => '12', 'new_value' => 'SOLVED', 'label' => 'Solved', 'color' => 'success', 'sort_order' => 12],
            ['type' => 'ticket_status', 'legacy_value' => '13', 'new_value' => 'CLOSED', 'label' => 'Closed', 'color' => 'gray', 'sort_order' => 13],
            ['type' => 'ticket_status', 'legacy_value' => '14', 'new_value' => 'CANCELLED', 'label' => 'Cancelled', 'color' => 'danger', 'sort_order' => 14],
            ['type' => 'ticket_status', 'legacy_value' => '15', 'new_value' => 'PICKUP_AFTER_NOT_RESOLVE', 'label' => 'Pickup After Not Resolve', 'color' => 'gray', 'sort_order' => 15],
        ];

        foreach ($mappings as $mapping) {
            EnumMapping::create($mapping);
        }
    }
}
```

---

## 7. Migration Commands

```bash
# Run all migrations
php artisan migrate --database=mysql_new --force

# Run seeders
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=EnumMappingSeeder

# Create backward compatibility views
php artisan db:create-views

# Verify data integrity
php artisan db:verify --table=tickets
php artisan db:verify --table=units
php artisan db:verify --table=customers
```

---

## 8. Notes

- Semua tabel baru menggunakan `softDeletes()` untuk data recovery
- Foreign key constraints hanya untuk relasi internal (new tables)
- Legacy IDs disimpan sebagai string untuk backward compatibility
- Index ditambahkan untuk query performance
- Migration order penting — jangan diubah

---

*Last updated: 2026-10-01*
