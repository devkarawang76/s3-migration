# Desain Backend Baru: Laravel + FilamentPHP

**Constraint:** Semua endpoint API dan Filament Resource memetakan struktur kolom existing tanpa modifikasi database legacy.
**Update:** Database baru boleh ada perubahan (normalized schema, RBAC, enum mapping). SQL injection fix di legacy di-skip (staging mode).
**Update 2:** Ticket comments & timeline, Preventive Maintenance (PM), many-to-many ticket-unit relationship.
**Update 3:** Tambah WebSocket config, API documentation, testing strategy, webhook support.

---

## 1. RESTful API Design

### 1.1 API Route Structure

```php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

Route::prefix('v1')->group(function () {

    // --- Auth ---
    Route::post('/auth/login', [Api\AuthController::class, 'login']);
    Route::post('/auth/logout', [Api\AuthController::class, 'logout']);
    Route::post('/auth/refresh', [Api\AuthController::class, 'refresh']);
    Route::get('/auth/me', [Api\AuthController::class, 'me']);

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // --- Tickets (Many-to-Many dengan Units) ---
        Route::apiResource('tickets', Api\TicketController::class);
        Route::get('tickets/{ticket}/history', [Api\TicketController::class, 'history']);
        Route::get('tickets/{ticket}/parts', [Api\TicketController::class, 'parts']);
        Route::post('tickets/{ticket}/parts', [Api\TicketController::class, 'addPart']);
        Route::post('tickets/{ticket}/status', [Api\TicketController::class, 'updateStatus']);
        Route::post('tickets/{ticket}/assign', [Api\TicketController::class, 'assignEngineer']);

        // --- Ticket Comments & Timeline ---
        Route::get('tickets/{ticket}/comments', [Api\TicketController::class, 'comments']);
        Route::post('tickets/{ticket}/comments', [Api\TicketController::class, 'addComment']);
        Route::put('comments/{comment}', [Api\TicketController::class, 'updateComment']);
        Route::delete('comments/{comment}', [Api\TicketController::class, 'deleteComment']);
        Route::get('tickets/{ticket}/timeline', [Api\TicketController::class, 'timeline']);

        // --- Ticket Units (Many-to-Many) ---
        Route::get('tickets/{ticket}/units', [Api\TicketController::class, 'units']);
        Route::post('tickets/{ticket}/units', [Api\TicketController::class, 'addUnit']);
        Route::delete('tickets/{ticket}/units/{unit}', [Api\TicketController::class, 'removeUnit']);

        // --- Units ---
        Route::apiResource('units', Api\UnitController::class);
        Route::get('units/{unit}/tickets', [Api\UnitController::class, 'tickets']);
        Route::get('units/{unit}/warranty', [Api\UnitController::class, 'warranty']);
        Route::get('units/{unit}/documents', [Api\UnitController::class, 'documents']);

        // --- Preventive Maintenance ---
        Route::apiResource('preventive-maintenances', Api\PreventiveMaintenanceController::class);
        Route::post('preventive-maintenances/{pm}/generate-tickets', [Api\PreventiveMaintenanceController::class, 'generateTickets']);
        Route::get('preventive-maintenances/{pm}/tickets', [Api\PreventiveMaintenanceController::class, 'tickets']);
        Route::get('preventive-maintenances/{pm}/progress', [Api\PreventiveMaintenanceController::class, 'progress']);

        // --- Loans ---
        Route::apiResource('loans', Api\LoanController::class);
        Route::post('loans/{loan}/approve', [Api\LoanController::class, 'approve']);
        Route::post('loans/{loan}/reject', [Api\LoanController::class, 'reject']);
        Route::post('loans/{loan}/return', [Api\LoanController::class, 'return']);

        // --- Sales Orders ---
        Route::apiResource('sales-orders', Api\SalesOrderController::class);
        Route::post('sales-orders/{so}/approve', [Api\SalesOrderController::class, 'approve']);

        // --- Quotations ---
        Route::apiResource('quotations', Api\QuotationController::class);
        Route::post('quotations/{quote}/approve', [Api\QuotationController::class, 'approve']);

        // --- SPRF ---
        Route::apiResource('sprf', Api\SprfController::class);
        Route::post('sprf/{sprf}/approve', [Api\SprfController::class, 'approve']);

        // --- Customers ---
        Route::apiResource('customers', Api\CustomerController::class);
        Route::get('customers/{customer}/units', [Api\CustomerController::class, 'units']);

        // --- Stock ---
        Route::apiResource('stock', Api\StockController::class);
        Route::get('stock/{item}/history', [Api\StockController::class, 'history']);

        // --- Master Data ---
        Route::apiResource('engineers', Api\EngineerController::class);
        Route::apiResource('suppliers', Api\SupplierController::class);
        Route::apiResource('service-offices', Api\ServiceOfficeController::class);

        // --- Dashboard ---
        Route::get('dashboard/summary', Api\DashboardController::class);
        Route::get('dashboard/kpi', [Api\DashboardController::class, 'kpi']);
        Route::get('dashboard/notifications', [Api\DashboardController::class, 'notifications']);

        // --- Reports ---
        Route::get('reports/ticket-summary', [Api\ReportController::class, 'ticketSummary']);
        Route::get('reports/sla-compliance', [Api\ReportController::class, 'slaCompliance']);
        Route::get('reports/loan-status', [Api\ReportController::class, 'loanStatus']);
        Route::get('reports/export/{type}', [Api\ReportController::class, 'export']);
    });

    // --- Health Check ---
    Route::get('/health', [Api\HealthController::class, 'index']);

        // --- Notifications ---
        Route::get('notifications', [Api\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [Api\NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [Api\NotificationController::class, 'markAllAsRead']);
    });
});
```

### 1.2 API Resource Controllers

#### TicketController (Updated)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'engineer', 'units']);

        if ($request->has('status')) {
            $query->where('ticket_status', $request->status);
        }
        if ($request->has('engineer_id')) {
            $query->where('engineer_id', $request->engineer_id);
        }
        if ($request->has('date_from')) {
            $query->where('ticket_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('ticket_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('ticket_no', 'like', "%{$search}%");
            });
        }
        if ($request->has('serial_no')) {
            $query->whereHas('units', function ($q) use ($request) {
                $q->where('serial_no', 'like', "%{$request->serial_no}%");
            });
        }

        $tickets = $query->paginate($request->input('per_page', 15));
        return TicketResource::collection($tickets);
    }

    public function store(TicketRequest $request)
    {
        $ticket = Ticket::create($request->validated());
        return new TicketResource($ticket);
    }

    public function show(Ticket $ticket)
    {
        return new TicketResource($ticket->load([
            'customer', 'engineer', 'units', 'statusHistory', 'comments', 'timeline'
        ]));
    }

    public function update(TicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());
        return new TicketResource($ticket);
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted']);
    }

    // --- Comments ---

    public function comments(Ticket $ticket)
    {
        $comments = $ticket->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'type' => 'sometimes|string|in:comment,note,status_change',
            'is_internal' => 'sometimes|boolean',
        ]);

        $comment = $ticket->comments()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'type' => $request->input('type', 'comment'),
            'is_internal' => $request->input('is_internal', false),
        ]);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'comment_added',
            'title' => 'Comment added',
            'description' => Str::limit($request->content, 100),
            'metadata' => ['comment_id' => $comment->id],
            'occurred_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment->load('user'),
        ], 201);
    }

    public function updateComment(Request $request, TicketComment $comment)
    {
        $request->validate(['content' => 'sometimes|string|max:5000']);
        $comment->update($request->only(['content']));
        return response()->json(['success' => true, 'data' => $comment]);
    }

    public function deleteComment(TicketComment $comment)
    {
        $comment->delete();
        return response()->json(['success' => true]);
    }

    // --- Timeline ---

    public function timeline(Ticket $ticket)
    {
        $timeline = $ticket->timeline()
            ->with('user')
            ->orderBy('occurred_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    // --- Units (Many-to-Many) ---

    public function units(Ticket $ticket)
    {
        $units = $ticket->units()
            ->wherePivot('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }

    public function addUnit(Request $request, Ticket $ticket)
    {
        $request->validate([
            'unit_id' => 'required|string|exists:units,unit_id',
            'notes' => 'nullable|string',
        ]);

        $unit = \App\Models\Unit::findOrFail($request->unit_id);

        $ticket->units()->attach($unit->id, [
            'serial_no' => $unit->serial_no,
            'status' => 'ACTIVE',
            'notes' => $request->notes,
            'sort_order' => $ticket->units()->count(),
        ]);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'unit_added',
            'title' => 'Unit added to ticket',
            'description' => "Unit {$unit->serial_no} added",
            'metadata' => ['unit_id' => $unit->unit_id, 'serial_no' => $unit->serial_no],
            'occurred_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $unit,
        ], 201);
    }

    public function removeUnit(Request $request, Ticket $ticket, string $unitId)
    {
        $ticket->units()->updateExistingPivot($unitId, [
            'status' => 'REMOVED',
        ]);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'unit_removed',
            'title' => 'Unit removed from ticket',
            'description' => "Unit removed from ticket",
            'metadata' => ['unit_id' => $unitId],
            'occurred_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // --- Status & Assignment ---

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|string|in:1,2,10,12,13,14',
            'remark' => 'nullable|string',
        ]);

        $oldStatus = $ticket->ticket_status;
        $ticket->ticket_status = $request->status;
        $ticket->save();

        $ticket->statusHistory()->create([
            'ticket_id' => $ticket->id,
            'status' => $request->status,
            'remark' => $request->remark,
            'modified_by' => auth()->user()->username,
        ]);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'status_changed',
            'title' => 'Status changed',
            'description' => "Status changed from {$oldStatus} to {$request->status}",
            'metadata' => ['old_status' => $oldStatus, 'new_status' => $request->status],
            'occurred_at' => now(),
        ]);

        event(new \App\Events\TicketStatusChanged(
            $ticket->id,
            $oldStatus,
            $request->status,
            auth()->user()->username,
            $request->remark
        ));

        return new TicketResource($ticket);
    }

    public function assignEngineer(Request $request, Ticket $ticket)
    {
        $request->validate(['engineer_id' => 'required|string|exists:engineer,eng_id']);
        $ticket->engineer_id = $request->engineer_id;
        $ticket->save();

        return new TicketResource($ticket);
    }
}
```

#### PreventiveMaintenanceController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PreventiveMaintenanceService;
use App\Models\PreventiveMaintenance;
use Illuminate\Http\Request;

class PreventiveMaintenanceController extends Controller
{
    public function __construct(
        private PreventiveMaintenanceService $pmService
    ) {}

    public function index(Request $request)
    {
        $query = PreventiveMaintenance::with(['customer', 'engineer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pm_type' => 'required|string|in:monthly,quarterly,annual',
            'customer_id' => 'required|string|exists:customers,customer_id',
            'engineer_id' => 'nullable|string|exists:engineer,eng_id',
            'scheduled_date' => 'required|date',
            'description' => 'nullable|string',
            'checklist' => 'nullable|array',
            'serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'string|distinct',
        ]);

        $pm = $this->pmService->createPMWithTickets($request->all(), $request->serial_numbers);

        return response()->json([
            'success' => true,
            'message' => 'PM created successfully',
            'data' => $pm,
        ], 201);
    }

    public function show(PreventiveMaintenance $pm)
    {
        return response()->json([
            'success' => true,
            'data' => $pm->load(['tickets', 'customer', 'engineer']),
        ]);
    }

    public function generateTickets(Request $request, PreventiveMaintenance $pm)
    {
        $request->validate([
            'serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'string|distinct',
        ]);

        $tickets = $this->pmService->generateTicketsForPM($pm, $request->serial_numbers);

        return response()->json([
            'success' => true,
            'message' => 'Tickets generated successfully',
            'data' => $tickets,
        ], 201);
    }

    public function tickets(PreventiveMaintenance $pm)
    {
        $tickets = $pm->tickets()
            ->with(['customer', 'engineer', 'statusHistory'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function progress(PreventiveMaintenance $pm)
    {
        $total = $pm->tickets()->count();
        $completed = $pm->tickets()->where('ticket_status', '12')->count();
        $pending = $pm->tickets()->whereNotIn('ticket_status', ['12', '13', '14'])->count();
        $cancelled = $pm->tickets()->where('ticket_status', '14')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'completed' => $completed,
                'pending' => $pending,
                'cancelled' => $cancelled,
                'progress_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ],
        ]);
    }
}
```

#### NotificationController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('username', auth()->user()->username)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::where('username', auth()->user()->username)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
```

#### DashboardController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\LoanList;
use App\Models\Unit;
use App\Models\Notification;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $openTickets = Ticket::whereNotIn('ticket_status', ['12', '13', '14'])->count();
        $activeLoans = LoanList::where('loan_status', 'ON LOAN')->count();
        $activeUnits = Unit::where('status', 'ACTIVE')->count();
        $unreadNotifications = Notification::where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'open_tickets' => $openTickets,
                'active_loans' => $activeLoans,
                'active_units' => $activeUnits,
                'unread_notifications' => $unreadNotifications,
            ],
        ]);
    }

    public function kpi()
    {
        // KPI calculation logic
        $kpi = [
            'tickets_resolved_this_month' => Ticket::where('ticket_status', '12')
                ->whereMonth('created_at', now()->month)
                ->count(),
            'average_resolution_time' => 0, // Calculate from ticket history
            'sla_compliance_rate' => 0, // Calculate from SLA status
        ];

        return response()->json([
            'success' => true,
            'data' => $kpi,
        ]);
    }

    public function notifications()
    {
        $notifications = Notification::where('username', auth()->user()->username)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }
}
```

#### ReportController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketReportExport;

class ReportController extends Controller
{
    public function ticketSummary(Request $request)
    {
        $tickets = Ticket::with(['customer', 'engineer', 'units'])
            ->whereBetween('ticket_date', [$request->date_from, $request->date_to])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function slaCompliance(Request $request)
    {
        $tickets = Ticket::whereNotNull('sla')
            ->whereBetween('ticket_date', [$request->date_from, $request->date_to])
            ->get();

        $compliance = [
            'total' => $tickets->count(),
            'met' => $tickets->where('sla_status', 'meet')->count(),
            'not_met' => $tickets->where('sla_status', 'not_meet')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $compliance,
        ]);
    }

    public function loanStatus(Request $request)
    {
        $loans = \App\Models\LoanList::with(['customer', 'unit'])
            ->whereBetween('loan_date', [$request->date_from, $request->date_to])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $loans,
        ]);
    }

    public function export(Request $request, string $type)
    {
        return Excel::download(new TicketReportExport($request), "{$type}_report.xlsx");
    }
}
```

### 1.3 Services

#### PreventiveMaintenanceService

```php
<?php

namespace App\Services;

use App\Models\PreventiveMaintenance;
use App\Models\Ticket;
use App\Models\PmTicketLink;
use Illuminate\Support\Facades\DB;

class PreventiveMaintenanceService
{
    public function createPMWithTickets(array $data, array $serialNumbers): PreventiveMaintenance
    {
        return DB::transaction(function () use ($data, $serialNumbers) {
            $pm = PreventiveMaintenance::create([
                'pm_no' => $this->generatePMNo(),
                'pm_type' => $data['pm_type'],
                'customer_id' => $data['customer_id'],
                'engineer_id' => $data['engineer_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'],
                'status' => 'PLANNED',
                'description' => $data['description'] ?? null,
                'checklist' => $data['checklist'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($serialNumbers as $serialNo) {
                $ticket = Ticket::create([
                    'ticket_no' => $this->generateTicketNo(),
                    'ticket_date' => now(),
                    'customer_id' => $data['customer_id'],
                    'ticket_status' => 'NEW',
                    'sla' => $data['sla'] ?? '2',
                    'problem_desc' => "PM: {$pm->pm_no} - {$serialNo}",
                    'created_by' => auth()->id(),
                ]);

                PmTicketLink::create([
                    'pm_id' => $pm->id,
                    'ticket_id' => $ticket->id,
                    'serial_no' => $serialNo,
                    'status' => 'PENDING',
                ]);

                $ticket->timeline()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'event_type' => 'created',
                    'title' => 'Ticket created from PM',
                    'description' => "Created from PM {$pm->pm_no}",
                    'metadata' => ['pm_id' => $pm->id, 'pm_no' => $pm->pm_no],
                    'occurred_at' => now(),
                ]);
            }

            $pm->timeline()->create([
                'pm_id' => $pm->id,
                'user_id' => auth()->id(),
                'event_type' => 'created',
                'title' => 'PM created',
                'description' => "PM created with " . count($serialNumbers) . " tickets",
                'metadata' => ['ticket_count' => count($serialNumbers)],
                'occurred_at' => now(),
            ]);

            return $pm;
        });
    }

    public function generateTicketsForPM(PreventiveMaintenance $pm, array $serialNumbers): array
    {
        $tickets = [];

        DB::transaction(function () use ($pm, $serialNumbers, &$tickets) {
            foreach ($serialNumbers as $serialNo) {
                $ticket = Ticket::create([
                    'ticket_no' => $this->generateTicketNo(),
                    'ticket_date' => now(),
                    'customer_id' => $pm->customer_id,
                    'ticket_status' => 'NEW',
                    'sla' => '2',
                    'problem_desc' => "PM: {$pm->pm_no} - {$serialNo}",
                    'created_by' => auth()->id(),
                ]);

                PmTicketLink::create([
                    'pm_id' => $pm->id,
                    'ticket_id' => $ticket->id,
                    'serial_no' => $serialNo,
                    'status' => 'PENDING',
                ]);

                $tickets[] = $ticket;
            }
        });

        return $tickets;
    }

    public function getPMWithTickets(int $pmId): ?PreventiveMaintenance
    {
        return PreventiveMaintenance::with(['tickets', 'customer', 'engineer'])
            ->find($pmId);
    }

    public function getPMsForSerialNo(string $serialNo)
    {
        return PreventiveMaintenance::whereHas('tickets', function ($query) use ($serialNo) {
            $query->where('serial_no', $serialNo);
        })->get();
    }

    private function generatePMNo(): string
    {
        $year = date('Y');
        $count = PreventiveMaintenance::whereYear('created_at', $year)->count() + 1;
        return 'PM-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function generateTicketNo(): string
    {
        $year = date('y');
        $count = Ticket::whereYear('created_at', date('Y'))->count() + 1;
        return date('d/m/Y') . '-PM-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
```

#### TicketService

```php
<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Unit;
use App\Models\TicketUnit;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function createTicketWithUnits(array $data, array $unitIds): Ticket
    {
        return DB::transaction(function () use ($data, $unitIds) {
            $ticket = Ticket::create([
                'ticket_no' => $this->generateTicketNo(),
                'ticket_date' => now(),
                'customer_id' => $data['customer_id'],
                'engineer_id' => $data['engineer_id'] ?? null,
                'ticket_status' => 'NEW',
                'sla' => $data['sla'] ?? '2',
                'problem_desc' => $data['problem_desc'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($unitIds as $index => $unitId) {
                $unit = Unit::findOrFail($unitId);

                TicketUnit::create([
                    'ticket_id' => $ticket->id,
                    'unit_id' => $unitId,
                    'serial_no' => $unit->serial_no,
                    'status' => 'ACTIVE',
                    'sort_order' => $index,
                ]);
            }

            $ticket->timeline()->create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'event_type' => 'created',
                'title' => 'Ticket created',
                'description' => 'Ticket created with ' . count($unitIds) . ' unit(s)',
                'metadata' => ['unit_ids' => $unitIds],
                'occurred_at' => now(),
            ]);

            return $ticket;
        });
    }

    public function addUnitToTicket(Ticket $ticket, string $unitId): TicketUnit
    {
        $unit = Unit::findOrFail($unitId);

        $ticketUnit = TicketUnit::create([
            'ticket_id' => $ticket->id,
            'unit_id' => $unitId,
            'serial_no' => $unit->serial_no,
            'status' => 'ACTIVE',
            'sort_order' => $ticket->ticketUnits()->count(),
        ]);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'unit_added',
            'title' => 'Unit added to ticket',
            'description' => "Unit {$unit->serial_no} added",
            'metadata' => ['unit_id' => $unitId, 'serial_no' => $unit->serial_no],
            'occurred_at' => now(),
        ]);

        return $ticketUnit;
    }

    public function removeUnitFromTicket(Ticket $ticket, string $unitId): void
    {
        $ticketUnit = TicketUnit::where('ticket_id', $ticket->id)
            ->where('unit_id', $unitId)
            ->firstOrFail();

        $ticketUnit->update(['status' => 'REMOVED']);

        $ticket->timeline()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'event_type' => 'unit_removed',
            'title' => 'Unit removed from ticket',
            'description' => "Unit {$ticketUnit->serial_no} removed",
            'metadata' => ['unit_id' => $unitId, 'serial_no' => $ticketUnit->serial_no],
            'occurred_at' => now(),
        ]);
    }

    public function getTicketWithUnits(int $ticketId): ?Ticket
    {
        return Ticket::with(['units', 'customer', 'engineer', 'comments', 'timeline'])
            ->find($ticketId);
    }

    public function getTicketsForUnit(string $unitId)
    {
        return Ticket::whereHas('units', function ($query) use ($unitId) {
            $query->where('unit_id', $unitId);
        })->with('units')->get();
    }

    private function generateTicketNo(): string
    {
        $year = date('y');
        $count = Ticket::whereYear('created_at', date('Y'))->count() + 1;
        return date('d/m/Y') . '-TK-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
```

### 1.4 Form Requests

#### TicketRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_no' => 'sometimes|string|max:50',
            'ticket_date' => 'sometimes|date',
            'customer_id' => 'sometimes|string|exists:customers,customer_id',
            'engineer_id' => 'nullable|string|exists:engineer,eng_id',
            'ticket_status' => 'sometimes|string|in:1,2,10,12,13,14',
            'sla' => 'nullable|string|in:1,2,3',
            'problem_desc' => 'nullable|string',
            'unit_ids' => 'sometimes|array',
            'unit_ids.*' => 'string|exists:units,unit_id',
        ];
    }
}
```

### 1.5 API Resources

#### TicketResource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticket_no' => $this->ticket_no,
            'ticket_date' => $this->ticket_date,
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->customer_name,
            ],
            'units' => $this->whenLoaded('units', fn() => $this->units->map(fn($u) => [
                'id' => $u->id,
                'unit_id' => $u->unit_id,
                'serial_no' => $u->serial_no,
                'unit_type' => $u->unitType?->unit_type_name,
            ])),
            'engineer' => [
                'id' => $this->engineer?->id,
                'name' => $this->engineer?->eng_name,
            ],
            'problem_desc' => $this->problem_desc,
            'ticket_status' => $this->ticket_status,
            'sla' => $this->sla,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'closed_at' => $this->closed_at,
            'close_remark' => $this->close_remark,
        ];
    }
}
```

---

## 2. WebSocket Configuration (Real-time Updates)

### 2.1 Laravel Reverb (WebSocket Server)

```bash
composer require laravel/reverb
php artisan reverb:install
```

### 2.2 Config (config/reverb.php)

```php
<?php

return [
    'default' => env('REVERB_SERVER', 'reverb'),
    'servers' => [
        'reverb' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'hostname' => env('REVERB_HOSTNAME'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
            ],
        ],
    ],
    'apps' => [
        'provider' => 'config',
        'apps' => [
            [
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => env('REVERB_HOST'),
                    'port' => env('REVERB_PORT', 443),
                    'scheme' => env('REVERB_SCHEME', 'https'),
                    'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => env('REVERB_PING_INTERVAL', 60),
                'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10_000),
            ],
        ],
    ],
];
```

### 2.3 Events

```php
<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('tickets.' . $this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'comment' => $this->comment->load('user'),
        ];
    }
}

class TicketStatusChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('tickets.' . $this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}
```

### 2.4 WebSocket Routes

```php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tickets.{ticketId}', function ($user, $ticketId) {
    // Check if user has access to this ticket
    $ticket = \App\Models\Ticket::find($ticketId);
    return $ticket && $user->id === $ticket->created_by;
});
```

---

## 3. API Documentation (Swagger/OpenAPI)

### 3.1 Install & Config

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

### 3.2 Annotations

```php
<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="S3 System API",
 *     version="1.0.0",
 *     description="Fujitsu Support & Service System API"
 * )
 *
 * @OA\Server(
 *     url="https://api.find-service.co.id/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class TicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tickets",
     *     summary="Get list of tickets",
     *     tags={"Tickets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request) { /* ... */ }

    /**
     * @OA\Post(
     *     path="/tickets",
     *     summary="Create new ticket",
     *     tags={"Tickets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="string"),
     *             @OA\Property(property="problem_desc", type="string"),
     *             @OA\Property(property="unit_ids", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created successfully"
     *     )
     * )
     */
    public function store(TicketRequest $request) { /* ... */ }
}
```

---

## 4. Webhook Support (Legacy Integration)

### 4.1 Webhook Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from legacy system.
     */
    public function handleLegacyWebhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-Legacy-Signature');
        $payload = $request->getContent();

        if (!$this->verifySignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Legacy webhook received', ['event' => $event, 'data' => $data]);

        switch ($event) {
            case 'ticket.created':
                $this->handleLegacyTicketCreated($data);
                break;
            case 'ticket.updated':
                $this->handleLegacyTicketUpdated($data);
                break;
            case 'ticket.status_changed':
                $this->handleLegacyTicketStatusChanged($data);
                break;
        }

        return response()->json(['success' => true]);
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha256', $payload, config('services.webhook.secret'));
        return hash_equals($computed, $signature);
    }

    private function handleLegacyTicketCreated(array $data): void
    {
        // Sync legacy ticket to new system
        \App\Services\LegacySyncService::syncTicket($data);
    }

    private function handleLegacyTicketUpdated(array $data): void
    {
        \App\Services\LegacySyncService::updateTicket($data);
    }

    private function handleLegacyTicketStatusChanged(array $data): void
    {
        \App\Services\LegacySyncService::updateTicketStatus($data);
    }
}
```

### 4.2 Webhook Route

```php
// routes/api.php
Route::post('/webhooks/legacy', [Api\WebhookController::class, 'handleLegacyWebhook']);
```

---

## 5. Testing Strategy

### 5.1 Test Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── TicketTest.php
│   │   ├── UnitTest.php
│   │   └── UserTest.php
│   ├── Services/
│   │   ├── TicketServiceTest.php
│   │   └── PreventiveMaintenanceServiceTest.php
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── TicketControllerTest.php
├── Feature/
│   ├── Api/
│   │   ├── TicketApiTest.php
│   │   ├── UnitApiTest.php
│   │   └── AuthApiTest.php
│   └── Webhook/
│       └── LegacyWebhookTest.php
└── Browser/
    └── (Dusk tests)
```

### 5.2 Unit Test Example

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\Unit;
use App\Models\Customer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_have_multiple_units(): void
    {
        $ticket = Ticket::factory()->create();
        $units = Unit::factory()->count(3)->create();

        foreach ($units as $unit) {
            $ticket->units()->attach($unit->id, [
                'serial_no' => $unit->serial_no,
                'status' => 'ACTIVE',
            ]);
        }

        $this->assertCount(3, $ticket->units);
        $this->assertEquals($units[0]->serial_no, $ticket->units->first()->serial_no);
    }

    public function test_ticket_scope_by_serial_no(): void
    {
        $ticket = Ticket::factory()->create();
        $unit = Unit::factory()->create(['serial_no' => 'SN12345']);

        $ticket->units()->attach($unit->id, [
            'serial_no' => $unit->serial_no,
            'status' => 'ACTIVE',
        ]);

        $found = Ticket::bySerialNo('SN12345')->first();
        $this->assertNotNull($found);
        $this->assertEquals($ticket->id, $found->id);
    }
}
```

### 5.3 Feature Test Example

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Ticket;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_ticket_with_multiple_units(): void
    {
        $user = User::factory()->create();
        $units = \App\Models\Unit::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tickets', [
                'customer_id' => 'CUST001',
                'problem_desc' => 'Test problem',
                'unit_ids' => $units->pluck('unit_id')->toArray(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'ticket_no',
                    'units' => [
                        ['id', 'unit_id', 'serial_no'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('ticket_units', [
            'ticket_id' => $response->json('data.id'),
            'unit_id' => $units[0]->id,
        ]);
    }

    public function test_user_can_add_comment_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
                'content' => 'Test comment',
                'type' => 'comment',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'content', 'user'],
            ]);
    }
}
```

### 5.4 Test Commands

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=TicketApiTest

# Run in parallel
php artisan test --parallel
```

---

## 6. Filament Admin Panel

### 6.1 Filament Resource Structure

```
app/Filament/Resources/
├── TicketResource.php
├── TicketResource/
│   └── Pages/
│       ├── ListTickets.php
│       ├── CreateTicket.php
│       ├── EditTicket.php
│       └── ViewTicket.php
├── UnitResource.php
├── UnitResource/
│   └── Pages/
├── PreventiveMaintenanceResource.php
├── PreventiveMaintenanceResource/
│   └── Pages/
├── LoanResource.php
├── LoanResource/
│   └── Pages/
├── SalesOrderResource.php
├── SalesOrderResource/
│   └── Pages/
├── QuotationResource.php
├── QuotationResource/
│   └── Pages/
├── SprfResource.php
├── SprfResource/
│   └── Pages/
├── CustomerResource.php
├── CustomerResource/
│   └── Pages/
├── StockResource.php
├── StockResource/
│   └── Pages/
├── EngineerResource.php
├── EngineerResource/
│   └── Pages/
├── SupplierResource.php
├── SupplierResource/
│   └── Pages/
├── UserResource.php
├── UserResource/
│   └── Pages/
├── RoleResource.php
├── RoleResource/
│   └── Pages/
└── ...
```

### 6.2 TicketResource (Filament) — Updated

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Service Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ticket_no')
                    ->label('Ticket Number')
                    ->maxLength(50),
                Forms\Components\DatePicker::make('ticket_date')
                    ->label('Ticket Date')
                    ->default(now()),
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'customer_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('engineer_id')
                    ->label('Engineer')
                    ->relationship('engineer', 'eng_name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Textarea::make('problem_desc')
                    ->label('Problem Description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('ticket_status')
                    ->label('Status')
                    ->options([
                        '1' => 'New',
                        '2' => 'Analyzing',
                        '10' => 'Pending Part',
                        '12' => 'Solved',
                        '13' => 'Closed',
                        '14' => 'Cancelled',
                    ])
                    ->default('1')
                    ->required(),
                Forms\Components\Select::make('sla')
                    ->label('SLA Level')
                    ->options([
                        '1' => 'Gold (24/7)',
                        '2' => 'Silver (Business Hours)',
                        '3' => 'Bronze (Extended)',
                    ])
                    ->default('2'),
                Forms\Components\Textarea::make('close_remark')
                    ->label('Close Remark')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_no')
                    ->label('Ticket No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('engineer.eng_name')
                    ->label('Engineer')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('ticket_status')
                    ->label('Status')
                    ->colors([
                        'success' => '12',
                        'warning' => ['1', '2', '10'],
                        'danger' => '14',
                        'gray' => '13',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1' => 'New',
                        '2' => 'Analyzing',
                        '10' => 'Pending Part',
                        '12' => 'Solved',
                        '13' => 'Closed',
                        '14' => 'Cancelled',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('sla')
                    ->label('SLA')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'danger',
                        '2' => 'warning',
                        '3' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ticket_status')
                    ->label('Status')
                    ->options([
                        '1' => 'New',
                        '2' => 'Analyzing',
                        '10' => 'Pending Part',
                        '12' => 'Solved',
                        '13' => 'Closed',
                        '14' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('engineer_id')
                    ->label('Engineer')
                    ->relationship('engineer', 'eng_name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketResource\Relations\CommentsRelationManager::class,
            TicketResource\Relations\TimelineRelationManager::class,
            TicketResource\Relations\UnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::class,
            'create' => Pages\CreateTicket::class,
            'edit' => Pages\EditTicket::class,
            'view' => Pages\ViewTicket::class,
        ];
    }
}
```

### 6.3 PreventiveMaintenanceResource (Filament) — NEW

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PreventiveMaintenanceResource\Pages;
use App\Models\PreventiveMaintenance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PreventiveMaintenanceResource extends Resource
{
    protected static ?string $model = PreventiveMaintenance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Service Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pm_type')
                    ->label('PM Type')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                    ])
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'customer_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('engineer_id')
                    ->label('Engineer')
                    ->relationship('engineer', 'eng_name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\DatePicker::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'PLANNED' => 'Planned',
                        'IN_PROGRESS' => 'In Progress',
                        'COMPLETED' => 'Completed',
                        'CANCELLED' => 'Cancelled',
                    ])
                    ->default('PLANNED'),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Forms\Components\Repeater::make('checklist')
                    ->label('Checklist')
                    ->schema([
                        Forms\Components\TextInput::make('item')
                            ->label('Checklist Item')
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pm_no')
                    ->label('PM No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pm_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->counts('tickets'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PLANNED',
                        'info' => 'IN_PROGRESS',
                        'success' => 'COMPLETED',
                        'danger' => 'CANCELLED',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PLANNED' => 'Planned',
                        'IN_PROGRESS' => 'In Progress',
                        'COMPLETED' => 'Completed',
                        'CANCELLED' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('pm_type')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_tickets')
                    ->label('Generate Tickets')
                    ->icon('heroicon-o-plus')
                    ->visible(fn(PreventiveMaintenance $record) => $record->status === 'PLANNED')
                    ->action(fn(PreventiveMaintenance $record) => $record->update(['status' => 'IN_PROGRESS'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPreventiveMaintenances::class,
            'create' => Pages\CreatePreventiveMaintenance::class,
            'edit' => Pages\EditPreventiveMaintenance::class,
            'view' => Pages\ViewPreventiveMaintenance::class,
        ];
    }
}
```

### 6.4 RoleResource (Filament) — NEW

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Role Name')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Forms\Components\CheckboxList::make('permissions')
                    ->label('Permissions')
                    ->relationship('permissions', 'name')
                    ->columns(3),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::class,
            'create' => Pages\CreateRole::class,
            'edit' => Pages\EditRole::class,
        ];
    }
}
```

---

## 7. Authentication Setup

### 7.1 Laravel Sanctum (API untuk Next.js)

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1')),
    'guard' => ['web'],
    'expiration' => null,
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

### 7.2 Filament Guard (Admin Panel)

```php
// config/filament.php
return [
    'domain' => env('FILAMENT_DOMAIN', null),
    'path' => env('FILAMENT_PATH', 'admin'),
    'home_url' => env('FILAMENT_HOME_URL', '/'),
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'admin'),
    ],
    'middleware' => [
        'auth' => [Authenticate::class],
    ],
];
```

### 7.3 Auth Guards Summary

| Guard | Provider | Usage |
|-------|----------|-------|
| `web` | `legacy` | Legacy user authentication (API) |
| `sanctum` | `legacy` | API tokens (Next.js) |
| `admin` | `admins` | Filament admin panel |

---

## 8. API Response Format

```php
<?php

namespace App\Http\Resources;

class ApiResponse
{
    public static function success($data, string $message = 'Success'): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    public static function error(string $message, int $code = 400, $errors = null): array
    {
        return ['success' => false, 'message' => $message, 'errors' => $errors];
    }

    public static function paginated($collection): array
    {
        return [
            'success' => true,
            'data' => $collection->items(),
            'meta' => [
                'current_page' => $collection->currentPage(),
                'last_page' => $collection->lastPage(),
                'per_page' => $collection->perPage(),
                'total' => $collection->total(),
            ],
        ];
    }
}
```

---

## 9. CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## 10. Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=172.16.1.2
DB_PORT=3306
DB_DATABASE=s3Prod
DB_USERNAME=admins
DB_PASSWORD=fid123!!

# New Database
DB_CONNECTION_NEW=mysql
DB_HOST_NEW=127.0.0.1
DB_PORT_NEW=3306
DB_DATABASE_NEW=s3_erp
DB_USERNAME_NEW=root
DB_PASSWORD_NEW=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000

# CORS
FRONTEND_URL=http://localhost:3000

# Filament
FILAMENT_PATH=admin
FILAMENT_DOMAIN=localhost

# WebSocket
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Webhook
WEBHOOK_SECRET=your-webhook-secret
```

---

## 11. Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── TicketController.php
│   │       ├── UnitController.php
│   │       ├── PreventiveMaintenanceController.php
│   │       ├── LoanController.php
│   │       ├── SalesOrderController.php
│   │       ├── QuotationController.php
│   │       ├── SprfController.php
│   │       ├── CustomerController.php
│   │       ├── StockController.php
│   │       ├── EngineerController.php
│   │       ├── SupplierController.php
│   │       ├── ServiceOfficeController.php
│   │       ├── DashboardController.php
│   │       ├── ReportController.php
│   │       ├── NotificationController.php
│   │       └── WebhookController.php
│   ├── Requests/
│   │   ├── TicketRequest.php
│   │   ├── UnitRequest.php
│   │   ├── LoanRequest.php
│   │   ├── SalesOrderRequest.php
│   │   ├── QuotationRequest.php
│   │   └── SprfRequest.php
│   ├── Resources/
│   │   ├── TicketResource.php
│   │   ├── UnitResource.php
│   │   ├── LoanResource.php
│   │   ├── SalesOrderResource.php
│   │   ├── QuotationResource.php
│   │   ├── SprfResource.php
│   │   ├── CustomerResource.php
│   │   └── StockResource.php
│   └── Middleware/
│       └── EnsureUserActive.php
├── Models/
│   ├── LegacyModel.php
│   ├── LegacyUser.php
│   ├── LegacyUserExternal.php
│   ├── Admin.php
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── RoleUser.php
│   ├── PermissionRole.php
│   ├── UserProfile.php
│   ├── UserOffice.php
│   ├── EnumMapping.php
│   ├── TicketStatus.php
│   ├── Ticket.php
│   ├── Unit.php
│   ├── TicketUnit.php
│   ├── TicketComment.php
│   ├── TicketTimeline.php
│   ├── PreventiveMaintenance.php
│   ├── PmTicketLink.php
│   ├── TicketHistoryStatus.php
│   ├── TicketRequestPart.php
│   ├── TicketAssetList.php
│   ├── Customer.php
│   ├── CustomerList.php
│   ├── UnitCustomer.php
│   ├── WarrantyUnit.php
│   ├── UnitType.php
│   ├── ModelType.php
│   ├── WarehouseList.php
│   ├── LoanList.php
│   ├── TicketLoan.php
│   ├── AssetApprovalList.php
│   ├── SalesOrderList.php
│   ├── SalesOrderDetail.php
│   ├── SalesOrderApproval.php
│   ├── QuotationList.php
│   ├── QuotationDetail.php
│   ├── QuotationApproval.php
│   ├── SprfList.php
│   ├── SprfDetail.php
│   ├── SprfApproval.php
│   ├── SupplierList.php
│   ├── Engineer.php
│   ├── ServiceOffice.php
│   ├── StockLogistic.php
│   ├── ProductList.php
│   ├── CompPart.php
│   ├── InternalLog.php
│   ├── Notification.php
│   ├── Holiday.php
│   ├── DocumentData.php
│   ├── CallLog.php
│   ├── Token.php
│   ├── Verification2FA.php
│   ├── UserActivityHistory.php
│   └── TicketHistoryRequestPart.php
├── Services/
│   ├── PreventiveMaintenanceService.php
│   ├── TicketService.php
│   └── LegacySyncService.php
├── Providers/
│   ├── AuthServiceProvider.php
│   ├── AdminPanelProvider.php
│   └── FilamentAuthServiceProvider.php
├── Filament/
│   ├── Resources/
│   │   ├── TicketResource.php
│   │   ├── UnitResource.php
│   │   ├── PreventiveMaintenanceResource.php
│   │   ├── LoanResource.php
│   │   ├── SalesOrderResource.php
│   │   ├── QuotationResource.php
│   │   ├── SprfResource.php
│   │   ├── CustomerResource.php
│   │   ├── StockResource.php
│   │   ├── EngineerResource.php
│   │   ├── SupplierResource.php
│   │   ├── UserResource.php
│   │   └── RoleResource.php
│   └── Pages/
│       ├── Dashboard.php
│       ├── SlaReport.php
│       └── LoanReport.php
├── Events/
│   ├── TicketStatusChanged.php
│   └── TicketCommentAdded.php
├── Listeners/
│   └── LogTicketStatusChange.php
└── Exceptions/
    └── Handler.php
```

---

## 12. RBAC Matrix (dari System Architecture Flow)

| Capability | Front Desk | Leader | Engineer | ASP | Customer |
|---|---|---|---|---|---|
| Create Ticket | ✅ Full | ❌ | ❌ | ⚠️ Carry-in only | ❌ |
| Admit/Decline Request | ✅ Full | ❌ | ❌ | ❌ | ❌ |
| Change Status | ✅ | ✅ | ✅ | ⚠️ | ❌ |
| Reassign | ✅ | ✅ | ❌ | ❌ | ❌ |
| Set Pending/Stop SLA | ✅ | ✅ | ⚠️ | ❌ | ❌ |
| Request Spare Part | ✅ | ✅ | ✅ | ✅ | ❌ |
| Update Return Part | ❌ | ❌ | ✅ | ✅ | ❌ |
| Input Activity Report & CSR | ❌ | ❌ | ✅ | ✅ | ❌ |
| Solve Ticket | ❌ | ❌ | ✅ | ✅ | ❌ |
| Close/Cancel Ticket | ✅ | ❌ | ❌ | ❌ | ❌ |
| Task Scheduling | ❌ | ✅ | ✅ | ❌ | ❌ |

## 13. Ticket State Machine (15 Status)

```
New → Analyzing → InProgress → Dispatching → WaitingPart → Solved → Closed
                  ↓           ↓              ↓
                  Cancelled   SLA Paused     Escalation3rdParty
                              (5 states)
```

**SLA Paused States:** WaitingCustomerFeedback, WaitingCustomerSchedule, InternalQuotation, WaitingCustomerPO, Pending

## 14. SLA Tiers

| Tier | Response Time | Resolution Target |
|---|---|---|
| Gold | ≤ 4 hours | 24/7 |
| Standard | Next Business Day | 8/5 |
| Carry-in | No onsite commitment | 7 business days |
| Non-Warranty | No SLA commitment | 30 business days |
| Other Contracts | No SLA commitment | 14 business days |

## 15. Validation Gates

| Target Status | Preconditions |
|---|---|
| Solved (Incident) | Resolution ≥ 11 chars, ≥ 1 Category, all parts final, Resolve flag |
| Solved (Request) | Resolution ≥ 11 chars, all parts final |
| Solved (PM) | Resolution filled |
| Solved (Inquiry) | No prerequisites |
| Closed | Activity Report + CSR/RRF/Repair Tag uploaded |
| Cancelled | Only from Analyzing + structural input error |

## 16. Notes

- **SQL injection fix di legacy di-skip** — aplikasi sedang dijadikan staging untuk production errors
- **Password migration** — MD5 di-rehash ke bcrypt saat login pertama
- **Data sync** — gunakan `php artisan sync:legacy` command untuk sync data dari legacy ke new DB
- **Feature flags** — gunakan `config/features.php` untuk gradual rollout per module
- **Rate limiting** — `throttle:api` middleware untuk brute-force protection
- **Audit trail** — event sourcing untuk semua status changes
- **Many-to-many** — ticket-unit relationship via `ticket_units` junction table
- **PM tickets** — 1 PM bisa generate multiple tickets (1 ticket = 1 SN)
- **Comments & Timeline** — setiap ticket punya conversation thread dan audit trail
- **WebSocket** — real-time updates via Laravel Reverb
- **API docs** — Swagger/OpenAPI via L5-Swagger
- **Webhook** — legacy system integration via webhook
- **Testing** — unit, feature, dan browser tests
- **State Machine** — 15 status dengan SLA Paused states
- **Validation Gates** — pre-conditions untuk setiap status transition

---

*Spesifikasi ini dapat dilanjutkan dengan Next.js integration dan deployment strategy.*
