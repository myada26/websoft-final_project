# 📋 Fee Collection and Tracking System (FCATS)
### Implementation Plan — ITSD 82 Web Software Tools | BSIT 3C

> **Stack:** Laravel 11 · Blade Templates · MySQL · Chart.js · Bootstrap 5  
> **Total Points:** 200 pts → converted to 100-scale  
> **Target Grade:** 1.0 (95–100) — Production-Ready

---

## 📑 Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Database Schema](#2-database-schema)
3. [Module Breakdown](#3-module-breakdown)
4. [Business Logic Design](#4-business-logic-design)
5. [UI Strategy (Blade)](#5-ui-strategy-blade)
6. [Charts Integration](#6-charts-integration)
7. [Step-by-Step Development Plan](#7-step-by-step-development-plan)
8. [Sample Code](#8-sample-code)
9. [Criteria Checklist](#9-criteria-checklist)

---

## 1. Architecture Overview

```
fcats/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── BackupDatabase.php          # Scheduled DB backup
│   │       └── AutoArchiveLogs.php         # Archive logs > 90 days
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── MfaController.php       # OTP/2FA
│   │   │   │   └── PasswordResetController.php
│   │   │   ├── Admin/
│   │   │   │   ├── UserManagementController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── BackupController.php
│   │   │   │   └── SiteSettingsController.php
│   │   │   ├── StudentController.php
│   │   │   ├── FeeController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ReportController.php
│   │   │   ├── DashboardController.php
│   │   │   └── NotificationController.php
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php          # RBAC enforcement
│   │   │   ├── AuditMiddleware.php         # Auto-log all requests
│   │   │   ├── RateLimitMiddleware.php     # 100 req/min per IP
│   │   │   └── SessionTimeoutMiddleware.php
│   │   └── Requests/
│   │       ├── StoreStudentRequest.php
│   │       ├── StoreFeeRequest.php
│   │       └── StorePaymentRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Student.php
│   │   ├── Fee.php
│   │   ├── FeeAssignment.php
│   │   ├── Payment.php
│   │   ├── AuditLog.php
│   │   ├── Notification.php
│   │   └── SiteSetting.php
│   └── Services/
│       ├── FeeCalculationService.php       # All fee math lives here
│       ├── PenaltyService.php
│       ├── ReportService.php
│       ├── PdfService.php
│       ├── BackupService.php
│       └── NotificationService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php               # Main shell (sidebar + header)
│       │   └── auth.blade.php              # Login/register shell
│       ├── auth/
│       ├── dashboard/
│       ├── students/
│       ├── fees/
│       ├── payments/
│       ├── reports/
│       ├── admin/
│       └── partials/
│           ├── _sidebar.blade.php
│           ├── _navbar.blade.php
│           ├── _alerts.blade.php
│           └── _modals.blade.php
└── routes/
    ├── web.php
    └── api.php                             # AJAX endpoints for charts
```

### Separation of Concerns

| Layer | Responsibility |
|---|---|
| **Routes** (`web.php`) | URL mapping only — no logic |
| **Controllers** | Receive request, call Service, return view |
| **Services** | Business logic, calculations, PDF, backup |
| **Models** | Eloquent ORM, relationships, scopes |
| **Blade Views** | Display only — no raw PHP math |
| **Form Requests** | Validation rules, authorization |
| **Middleware** | RBAC, rate limiting, audit logging |

---

## 2. Database Schema

### Tables & Fields

#### `users`
```
id               — bigint, PK
role_id          — FK → roles.id
name             — string(100)
email            — string(100), unique
email_verified_at— timestamp, nullable
password         — string (bcrypt)
avatar           — string, nullable
status           — enum('active','inactive','suspended'), default 'active'
mfa_secret       — string, nullable
mfa_enabled      — boolean, default false
login_attempts   — tinyint, default 0
locked_until     — timestamp, nullable
remember_token   — string
last_login_at    — timestamp, nullable
last_login_ip    — string, nullable
created_at / updated_at
```

#### `roles`
```
id               — bigint, PK
name             — string(50)          -- 'admin', 'cashier', 'viewer'
description      — string
created_at / updated_at
```

#### `permissions`
```
id               — bigint, PK
role_id          — FK → roles.id
module           — string(50)          -- 'students', 'fees', 'payments', etc.
can_create       — boolean
can_read         — boolean
can_update       — boolean
can_delete       — boolean
```

#### `students`
```
id               — bigint, PK
student_id_no    — string(20), unique  -- e.g. "2024-0001"
first_name       — string(100)
last_name        — string(100)
email            — string(100), nullable
phone            — string(20), nullable
grade_level      — string(50)
section          — string(50)
school_year      — string(20)          -- "2024-2025"
status           — enum('enrolled','unenrolled','graduated')
avatar           — string, nullable
deleted_at       — timestamp, nullable  -- soft delete
created_at / updated_at
```

#### `fees`
```
id               — bigint, PK
name             — string(100)         -- "Tuition Fee", "Miscellaneous"
description      — text, nullable
amount           — decimal(10,2)
fee_type         — enum('fixed','per_subject','annual','semester')
grade_level      — string(50), nullable -- null = all grade levels
school_year      — string(20)
due_date         — date
penalty_rate     — decimal(5,2), default 0  -- % per day/week
penalty_type     — enum('none','daily','weekly','fixed')
is_active        — boolean, default true
deleted_at       — timestamp, nullable
created_at / updated_at
```

#### `fee_assignments`
```
id               — bigint, PK
student_id       — FK → students.id
fee_id           — FK → fees.id
assigned_by      — FK → users.id
amount_due       — decimal(10,2)       -- snapshot at time of assignment
penalty_amount   — decimal(10,2), default 0
total_due        — decimal(10,2)       -- computed: amount_due + penalty_amount
status           — enum('unpaid','partial','paid','waived')
assigned_at      — timestamp
due_date         — date
notes            — text, nullable
deleted_at       — timestamp, nullable
created_at / updated_at
```

#### `payments`
```
id               — bigint, PK
reference_no     — string(30), unique  -- auto-generated "PAY-20240001"
fee_assignment_id— FK → fee_assignments.id
student_id       — FK → students.id
received_by      — FK → users.id
amount_paid      — decimal(10,2)
payment_method   — enum('cash','gcash','bank_transfer','check')
payment_date     — date
receipt_no       — string(50), nullable
notes            — text, nullable
deleted_at       — timestamp, nullable
created_at / updated_at
```

#### `audit_logs`
```
id               — bigint, PK
user_id          — FK → users.id, nullable
event            — string(100)         -- 'login', 'create_student', etc.
auditable_type   — string              -- 'App\\Models\\Student'
auditable_id     — bigint
old_values       — json, nullable
new_values       — json, nullable
ip_address       — string(45)
user_agent       — text
url              — string
method           — string(10)
created_at       — timestamp
```

#### `notifications`
```
id               — uuid, PK
user_id          — FK → users.id
type             — string              -- class name
data             — json
read_at          — timestamp, nullable
created_at / updated_at
```

#### `site_settings`
```
id               — bigint, PK
key              — string(100), unique
value            — text
group            — string(50)          -- 'branding','email','security','backup'
updated_at
```

### Relationships

```
User          ——< Role             (Many-to-One)
Role          ——< Permissions      (One-to-Many)
Student       ——< FeeAssignments   (One-to-Many)
Fee           ——< FeeAssignments   (One-to-Many)
FeeAssignment ——< Payments         (One-to-Many)
User          ——< Payments         (One-to-Many: received_by)
User          ——< AuditLogs        (One-to-Many)
User          ——< Notifications    (One-to-Many)
```

---

## 3. Module Breakdown

### Module 1 — Authentication *(15 pts)*

**Purpose:** Secure, session-based login with MFA, lockout, and password recovery.

| Route | Method | Controller@Method | View |
|---|---|---|---|
| `/login` | GET | `LoginController@showForm` | `auth/login.blade.php` |
| `/login` | POST | `LoginController@login` | — redirect |
| `/mfa/verify` | GET/POST | `MfaController@verify` | `auth/mfa.blade.php` |
| `/logout` | POST | `LoginController@logout` | — redirect |
| `/password/reset` | GET/POST | `PasswordResetController` | `auth/reset.blade.php` |

**Key implementations:**
- Account lockout after 5 failed attempts → set `locked_until = now() + 15min`
- OTP via `Mail` to user's email (random 6-digit, expires 10 min)
- Password policy enforced via `StoreUserRequest` regex rule
- `SessionTimeoutMiddleware` checks last activity timestamp

---

### Module 2 — Dashboard *(15 pts)*

**Purpose:** Live stats, charts, and quick actions for admin/cashier.

| Route | Method | Controller@Method | View |
|---|---|---|---|
| `/dashboard` | GET | `DashboardController@index` | `dashboard/index.blade.php` |
| `/api/dashboard/stats` | GET | `DashboardController@stats` | — JSON |
| `/api/dashboard/chart` | GET | `DashboardController@chartData` | — JSON |

**Widgets:**
- Total students enrolled
- Total fees collected (current school year)
- Unpaid balances count
- Recent payments feed
- Monthly collection bar chart
- Unpaid vs Paid pie chart

**Data flow:**
```
AJAX Request → DashboardController@chartData
             → FeeCalculationService::getMonthlySummary()
             → returns JSON → Chart.js renders
```

---

### Module 3 — Student Management *(CRUD)*

**Purpose:** Full lifecycle management of student records.

| Route | Method | Controller@Method | View |
|---|---|---|---|
| `/students` | GET | `StudentController@index` | `students/index.blade.php` |
| `/students/create` | GET | `StudentController@create` | `students/create.blade.php` |
| `/students` | POST | `StudentController@store` | — redirect |
| `/students/{id}` | GET | `StudentController@show` | `students/show.blade.php` |
| `/students/{id}/edit` | GET | `StudentController@edit` | `students/edit.blade.php` |
| `/students/{id}` | PUT | `StudentController@update` | — redirect |
| `/students/{id}` | DELETE | `StudentController@destroy` | — redirect (soft delete) |
| `/students/import` | POST | `StudentController@import` | — redirect |
| `/students/export` | GET | `StudentController@export` | — download |
| `/students/trashed` | GET | `StudentController@trashed` | `students/trashed.blade.php` |
| `/students/{id}/restore` | POST | `StudentController@restore` | — redirect |

**Features per route:**
- Index: pagination (10/25/50/100), search, sort, bulk export
- Show: fee summary, payment history, audit trail
- Import: Excel/CSV with preview, duplicate detection
- Trashed: restore or permanently delete (admin only)

---

### Module 4 — Fee Management *(CRUD)*

**Purpose:** Define fees, assign to students, track due dates, compute penalties.

| Route | Method | Controller@Method | View |
|---|---|---|---|
| `/fees` | GET | `FeeController@index` | `fees/index.blade.php` |
| `/fees/create` | GET | `FeeController@create` | `fees/create.blade.php` |
| `/fees` | POST | `FeeController@store` | — redirect |
| `/fees/{id}/assign` | GET | `FeeController@assignForm` | `fees/assign.blade.php` |
| `/fees/{id}/assign` | POST | `FeeController@assign` | — redirect |
| `/fees/{id}` | PUT | `FeeController@update` | — redirect |
| `/fees/{id}` | DELETE | `FeeController@destroy` | — redirect |

**Fee assignment data flow:**
```
POST /fees/{id}/assign
  → FeeController@assign
  → FeeCalculationService::computeDue($fee, $student)
  → Creates FeeAssignment record
  → Fires AssignmentCreated event
  → NotificationService sends in-app alert to cashier
```

---

### Module 5 — Payment Processing *(CRUD)*

**Purpose:** Record payments, issue receipts, update assignment status.

| Route | Method | Controller@Method | View |
|---|---|---|---|
| `/payments` | GET | `PaymentController@index` | `payments/index.blade.php` |
| `/payments/create` | GET | `PaymentController@create` | `payments/create.blade.php` |
| `/payments` | POST | `PaymentController@store` | — redirect |
| `/payments/{id}` | GET | `PaymentController@show` | `payments/show.blade.php` |
| `/payments/{id}/receipt` | GET | `PaymentController@receipt` | PDF download |
| `/payments/{id}` | DELETE | `PaymentController@destroy` | soft delete, admin only |

**Payment flow:**
```
POST /payments
  → Validate amount (cannot exceed total_due)
  → PaymentController@store
  → Creates Payment record
  → FeeCalculationService::updateAssignmentStatus($assignment)
     → if sum(payments) >= total_due → status = 'paid'
     → if sum(payments) > 0          → status = 'partial'
  → Auto-generates reference_no: "PAY-{YEAR}{SEQ}"
  → PdfService::generateReceipt($payment) → store in storage
  → NotificationService::notify(admin, 'New payment recorded')
  → AuditLog::record('create', $payment)
```

---

### Module 6 — Reports & PDF *(10 + 5 pts)*

**Purpose:** Generate, schedule, and export financial summaries.

| Route | Method | View / Output |
|---|---|---|
| `/reports` | GET | `reports/index.blade.php` |
| `/reports/payments` | GET | `reports/payments.blade.php` |
| `/reports/outstanding` | GET | `reports/outstanding.blade.php` |
| `/reports/audit` | GET | `reports/audit.blade.php` |
| `/reports/export/{type}` | GET | Excel / CSV / PDF download |
| `/reports/print/{type}` | GET | Print-optimized view |

---

### Module 7 — Admin Panel *(User Mgmt + Settings + Audit + Backup)*

| Route | Purpose |
|---|---|
| `/admin/users` | CRUD users, assign roles |
| `/admin/users/{id}/impersonate` | Login as user (support) |
| `/admin/audit-logs` | Searchable log viewer |
| `/admin/audit-logs/export` | Export logs to Excel/CSV |
| `/admin/settings` | Branding, email, security, backup config |
| `/admin/backup` | Manual trigger + schedule config |
| `/admin/notifications` | Global notification settings |

---

## 4. Business Logic Design

### Where Logic Lives

```
Controllers  →  receive HTTP, delegate, return response
Services     →  ALL calculations, penalties, PDF, backup
Models       →  relationships, query scopes, accessors
```

### `FeeCalculationService`

```php
// app/Services/FeeCalculationService.php

class FeeCalculationService
{
    // Compute base due amount
    public function computeDue(Fee $fee, Student $student): float

    // Add penalty to an overdue assignment
    public function computePenalty(FeeAssignment $assignment): float
    /*
        if penalty_type == 'daily':
            days_overdue = today - due_date (if > 0)
            penalty = (amount_due * penalty_rate / 100) * days_overdue

        if penalty_type == 'weekly':
            weeks = ceil(days_overdue / 7)
            penalty = (amount_due * penalty_rate / 100) * weeks

        if penalty_type == 'fixed':
            penalty = fee->penalty_rate (flat amount)
    */

    // Update assignment status after payment
    public function updateAssignmentStatus(FeeAssignment $assignment): void

    // Get student's total balance
    public function getStudentBalance(Student $student): array
    // returns ['total_due', 'total_paid', 'balance', 'assignments']

    // Monthly collection summary for charts
    public function getMonthlySummary(string $year): array
}
```

### `PenaltyService` — Scheduled Job

```php
// Runs daily via Laravel Scheduler in app/Console/Kernel.php
// $schedule->command('fees:apply-penalties')->daily();

class ApplyPenaltiesCommand extends Command
{
    // Finds all unpaid/partial FeeAssignments past due_date
    // Calls FeeCalculationService::computePenalty()
    // Updates penalty_amount and total_due
    // Creates AuditLog entry for each update
}
```

---

## 5. UI Strategy (Blade)

### Master Layout — `layouts/app.blade.php`

```
┌─────────────────────────────────────────────────────┐
│  NAVBAR: Logo | Breadcrumb | Notifications🔔 | User  │
├─────────────┬───────────────────────────────────────┤
│             │                                        │
│  SIDEBAR    │   @yield('content')                   │
│  - Dashboard│                                        │
│  - Students │   <!-- Module page renders here -->   │
│  - Fees     │                                        │
│  - Payments │                                        │
│  - Reports  │                                        │
│  - Admin ▼  │                                        │
│    Users    │                                        │
│    Settings │                                        │
│    Logs     │                                        │
│             │                                        │
└─────────────┴───────────────────────────────────────┘
```

### Blade Conventions

```blade
{{-- All pages extend the layout --}}
@extends('layouts.app')

@section('title', 'Students')

@section('breadcrumb')
    Home / Students / List
@endsection

@section('content')
    {{-- page-specific HTML here --}}
@endsection

@push('scripts')
    {{-- page-specific JS here --}}
@endpush
```

### Form Validation Pattern

```blade
{{-- Server-side error display --}}
@error('field_name')
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

{{-- Input with error state --}}
<input type="text"
       name="first_name"
       class="form-control @error('first_name') is-invalid @enderror"
       value="{{ old('first_name') }}">
```

### CSRF on All Forms

```blade
<form method="POST" action="{{ route('students.store') }}">
    @csrf
    @method('PUT') {{-- for PUT/PATCH/DELETE --}}
</form>
```

### Soft Delete Display

```blade
{{-- In index view: show restore option for trashed records --}}
@if($student->trashed())
    <span class="badge bg-danger">Deleted</span>
    <form action="{{ route('students.restore', $student) }}" method="POST">
        @csrf
        <button class="btn btn-sm btn-warning">Restore</button>
    </form>
@endif
```

---

## 6. Charts Integration

### Setup (Chart.js via CDN)

```blade
{{-- In layouts/app.blade.php, before </body> --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('scripts')
```

### Passing Data: Controller → Blade → Chart.js

```php
// DashboardController.php
public function index()
{
    $monthlySummary = $this->feeService->getMonthlySummary(date('Y'));

    return view('dashboard.index', [
        'chartLabels' => json_encode($monthlySummary['labels']),
        'chartData'   => json_encode($monthlySummary['totals']),
    ]);
}
```

```blade
{{-- dashboard/index.blade.php --}}
<canvas id="collectionChart"></canvas>

@push('scripts')
<script>
    const ctx = document.getElementById('collectionChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! $chartLabels !!},       // use {!! !!} for pre-encoded JSON
            datasets: [{
                label: 'Collections (₱)',
                data: {!! $chartData !!},
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
```

### AJAX Refresh (No Page Reload)

```javascript
// Refresh chart data on date range change
document.getElementById('dateRange').addEventListener('change', function() {
    fetch(`/api/dashboard/chart?range=${this.value}`)
        .then(res => res.json())
        .then(data => {
            chart.data.labels   = data.labels;
            chart.data.datasets[0].data = data.totals;
            chart.update();
        });
});
```

### Chart Types per Module

| Location | Chart Type | Data |
|---|---|---|
| Dashboard | Bar chart | Monthly collections |
| Dashboard | Pie chart | Paid vs Unpaid |
| Dashboard | Line chart | Daily transactions |
| Reports | Stacked bar | Fee type breakdown |
| Student Profile | Doughnut | Balance breakdown |

---

## 7. Step-by-Step Development Plan

### Phase 1 — Laravel Setup *(Day 1–2)*
- [ ] `composer create-project laravel/laravel fcats`
- [ ] Configure `.env`: DB credentials, mail (Mailtrap for dev), app URL
- [ ] Install packages:
  - `barryvdh/laravel-dompdf` — PDF generation
  - `maatwebsite/excel` — Excel import/export
  - `spatie/laravel-activitylog` — Audit logging
  - `pragmarx/google2fa-laravel` — MFA/TOTP
  - `laravel/sanctum` — API auth (for AJAX)
- [ ] Set up GitHub repository, add `.gitignore`

### Phase 2 — Database & Models *(Day 3–5)*
- [ ] Write all migrations (refer to schema above)
- [ ] Run `php artisan migrate`
- [ ] Create all Eloquent Models with:
  - `$fillable` arrays
  - Relationships (`hasMany`, `belongsTo`, `belongsToMany`)
  - Soft deletes (`SoftDeletes` trait) on: students, fees, payments
- [ ] Write Seeders: roles, admin user, sample students, sample fees
- [ ] Run `php artisan db:seed`

### Phase 3 — Authentication *(Day 6–8)*
- [ ] Build login form + `LoginController` with lockout logic
- [ ] Implement MFA: OTP email via `Mail`, session-stored secret
- [ ] Password reset via signed URL + `PasswordResetController`
- [ ] `RoleMiddleware` — check role on every protected route
- [ ] `SessionTimeoutMiddleware` — kick idle users
- [ ] Profile management + avatar upload (store in `storage/avatars`)

### Phase 4 — Core CRUD *(Day 9–14)*
- [ ] **Students:** index, create, edit, show, soft-delete, restore
- [ ] **Fees:** index, create, edit, assign to students
- [ ] Implement `StoreStudentRequest` and `StoreFeeRequest` with full validation
- [ ] Implement `AuditMiddleware` — auto-log every POST/PUT/DELETE
- [ ] Build `_sidebar.blade.php`, `layouts/app.blade.php` with Bootstrap 5
- [ ] Implement pagination, search, sort on all index views

### Phase 5 — Payment Logic *(Day 15–18)*
- [ ] Build payment create form with real-time balance check (AJAX)
- [ ] Implement `FeeCalculationService` — all math, status updates
- [ ] Implement `PenaltyService` + `ApplyPenaltiesCommand`
- [ ] Register penalty command in `Kernel.php` scheduler
- [ ] Auto-generate `reference_no` on payment creation
- [ ] Payment receipt PDF via `DomPDF`

### Phase 6 — Dashboard & Charts *(Day 19–21)*
- [ ] Build dashboard layout with Bootstrap grid
- [ ] Implement `DashboardController` with 5 stat widgets
- [ ] Wire up Chart.js bar + pie charts
- [ ] AJAX endpoints for chart refresh (`/api/dashboard/chart`)
- [ ] Real-time notification bell (polling or SSE)

### Phase 7 — Reports, Export & Import *(Day 22–25)*
- [ ] Payment summary report (filterable by date, student, status)
- [ ] Outstanding balances report
- [ ] Audit trail report (admin only)
- [ ] Excel export using `maatwebsite/excel` for each report
- [ ] PDF export using `DomPDF` with branded header/footer
- [ ] CSV/Excel bulk import for students with preview + error report

### Phase 8 — Admin Features *(Day 26–28)*
- [ ] User management CRUD (admin only)
- [ ] Role & permission assignment UI
- [ ] Impersonate user feature
- [ ] Audit log viewer with search + export
- [ ] Site settings page (branding, SMTP, backup schedule)
- [ ] Manual backup button + `BackupService`

### Phase 9 — Security Hardening *(Day 29–30)*
- [ ] Add rate limiting middleware (100 req/min)
- [ ] Set security headers in `AppServiceProvider` or middleware
- [ ] Verify CSRF on all forms, XSS via Blade `{{ }}` encoding
- [ ] Test account lockout, MFA flow end-to-end
- [ ] Input sanitization in Form Requests

### Phase 10 — Polish & Deliverables *(Day 31–35)*
- [ ] Responsive design audit (mobile/tablet)
- [ ] Loading spinners, empty state messages, skeleton screens
- [ ] Breadcrumb navigation on all pages
- [ ] Write `README.md` for GitHub
- [ ] Export ER diagram (dbdiagram.io or MySQL Workbench)
- [ ] Write User Manual PDF (with screenshots)
- [ ] Deploy to free host: Railway / Render / InfinityFree + MySQL

---

## 8. Sample Code

### 8.1 — Migration: `create_students_table`

```php
<?php
// database/migrations/xxxx_create_students_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id_no', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('grade_level', 50);
            $table->string('section', 50);
            $table->string('school_year', 20);
            $table->enum('status', ['enrolled', 'unenrolled', 'graduated'])
                  ->default('enrolled');
            $table->string('avatar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
```

### 8.2 — Model: `Student.php`

```php
<?php
// app/Models/Student.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id_no', 'first_name', 'last_name',
        'email', 'phone', 'grade_level', 'section',
        'school_year', 'status', 'avatar',
    ];

    // ─── Relationships ───────────────────────────────────────────
    public function feeAssignments()
    {
        return $this->hasMany(FeeAssignment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('student_id_no', 'like', "%{$term}%");
        });
    }

    // ─── Accessors ───────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### 8.3 — Controller: `StudentController.php`

```php
<?php
// app/Http/Controllers/StudentController.php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(private FeeCalculationService $feeService) {}

    // GET /students
    public function index(Request $request)
    {
        $students = Student::query()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy($request->sort ?? 'created_at', $request->dir ?? 'desc')
            ->paginate($request->per_page ?? 25)
            ->withQueryString();

        return view('students.index', compact('students'));
    }

    // GET /students/create
    public function create()
    {
        return view('students.create');
    }

    // POST /students
    public function store(StoreStudentRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')
                ->store('avatars', 'public');
        }

        $student = Student::create($data);

        activity('student')->performedOn($student)
            ->causedBy(auth()->user())
            ->log('Student created');

        return redirect()->route('students.show', $student)
            ->with('success', "Student {$student->full_name} has been added.");
    }

    // GET /students/{id}
    public function show(Student $student)
    {
        $balance = $this->feeService->getStudentBalance($student);
        $assignments = $student->feeAssignments()->with('fee', 'payments')->get();

        return view('students.show', compact('student', 'balance', 'assignments'));
    }

    // GET /students/{id}/edit
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    // PUT /students/{id}
    public function update(StoreStudentRequest $request, Student $student)
    {
        $old = $student->toArray();
        $student->update($request->validated());

        activity('student')->performedOn($student)
            ->causedBy(auth()->user())
            ->withProperties(['old' => $old, 'new' => $student->toArray()])
            ->log('Student updated');

        return redirect()->route('students.show', $student)
            ->with('success', 'Student record updated.');
    }

    // DELETE /students/{id}  (soft delete)
    public function destroy(Student $student)
    {
        $student->delete();

        activity('student')->performedOn($student)
            ->causedBy(auth()->user())
            ->log('Student soft-deleted');

        return redirect()->route('students.index')
            ->with('warning', "{$student->full_name} has been removed. You can restore it from Trash.");
    }

    // POST /students/{id}/restore
    public function restore(int $id)
    {
        $student = Student::withTrashed()->findOrFail($id);
        $student->restore();

        return redirect()->route('students.index')
            ->with('success', 'Student record restored.');
    }
}
```

### 8.4 — Form Request: `StoreStudentRequest.php`

```php
<?php
// app/Http/Requests/StoreStudentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by RoleMiddleware
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_id_no' => "required|string|max:20|unique:students,student_id_no,{$studentId}",
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'nullable|email|max:100',
            'phone'         => 'nullable|regex:/^[0-9\+\-\s]+$/|max:20',
            'grade_level'   => 'required|string|max:50',
            'section'       => 'required|string|max:50',
            'school_year'   => 'required|regex:/^\d{4}-\d{4}$/',
            'status'        => 'required|in:enrolled,unenrolled,graduated',
            'avatar'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
```

### 8.5 — Blade View: `students/index.blade.php`

```blade
{{-- resources/views/students/index.blade.php --}}

@extends('layouts.app')
@section('title', 'Students')

@section('content')
<div class="container-fluid px-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-people me-2 text-primary"></i>Student Records
        </h4>
        @can('create', App\Models\Student::class)
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Student
        </a>
        @endcan
    </div>

    {{-- Flash Messages --}}
    @include('partials._alerts')

    {{-- Search & Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('students.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by name or ID..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="enrolled"   {{ request('status') == 'enrolled'   ? 'selected' : '' }}>Enrolled</option>
                        <option value="unenrolled" {{ request('status') == 'unenrolled' ? 'selected' : '' }}>Unenrolled</option>
                        <option value="graduated"  {{ request('status') == 'graduated'  ? 'selected' : '' }}>Graduated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 25) == $n ? 'selected' : '' }}>
                                {{ $n }} / page
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-50">Filter</button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary w-50">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Students Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($students->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No students found. <a href="{{ route('students.create') }}">Add one now.</a>
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ route('students.index', ['sort' => 'student_id_no', 'dir' => request('dir') == 'asc' ? 'desc' : 'asc']) + request()->except('sort', 'dir') }}">
                                    ID No <i class="bi bi-arrow-down-up small"></i>
                                </a>
                            </th>
                            <th>Full Name</th>
                            <th>Grade / Section</th>
                            <th>School Year</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td><code>{{ $student->student_id_no }}</code></td>
                            <td>
                                <a href="{{ route('students.show', $student) }}" class="fw-semibold text-decoration-none">
                                    {{ $student->full_name }}
                                </a>
                            </td>
                            <td>{{ $student->grade_level }} — {{ $student->section }}</td>
                            <td>{{ $student->school_year }}</td>
                            <td>
                                @php
                                    $badges = ['enrolled' => 'success', 'unenrolled' => 'secondary', 'graduated' => 'info'];
                                @endphp
                                <span class="badge bg-{{ $badges[$student->status] }}">
                                    {{ ucfirst($student->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('students.show', $student) }}"   class="btn btn-sm btn-outline-primary me-1" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('students.edit', $student) }}"   class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to remove this student?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <small class="text-muted">
                    Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students
                </small>
                {{ $students->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
```

### 8.6 — Routes: `web.php` (Excerpt)

```php
<?php
// routes/web.php

use App\Http\Controllers\{
    StudentController, FeeController, PaymentController,
    ReportController, DashboardController, NotificationController
};
use App\Http\Controllers\Auth\{LoginController, MfaController, PasswordResetController};
use App\Http\Controllers\Admin\{UserManagementController, AuditLogController, SiteSettingsController, BackupController};

// ─── Auth ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',          [LoginController::class, 'showForm'])->name('login');
    Route::post('/login',         [LoginController::class, 'login']);
    Route::get('/mfa/verify',     [MfaController::class, 'showForm'])->name('mfa.form');
    Route::post('/mfa/verify',    [MfaController::class, 'verify'])->name('mfa.verify');
    Route::get('/password/reset', [PasswordResetController::class, 'showForm'])->name('password.request');
    Route::post('/password/reset',[PasswordResetController::class, 'sendLink'])->name('password.email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ─── Dashboard ────────────────────────────────────────────────
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ─── Students ─────────────────────────────────────────────────
    Route::resource('students', StudentController::class);
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::get('/students/trashed',       [StudentController::class, 'trashed'])->name('students.trashed');
    Route::post('/students/import',       [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/export',        [StudentController::class, 'export'])->name('students.export');

    // ─── Fees ─────────────────────────────────────────────────────
    Route::resource('fees', FeeController::class);
    Route::get('/fees/{fee}/assign',  [FeeController::class, 'assignForm'])->name('fees.assign.form');
    Route::post('/fees/{fee}/assign', [FeeController::class, 'assign'])->name('fees.assign');

    // ─── Payments ─────────────────────────────────────────────────
    Route::resource('payments', PaymentController::class)->except(['edit','update']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    // ─── Reports ──────────────────────────────────────────────────
    Route::get('/reports',                    [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/payments',           [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/outstanding',        [ReportController::class, 'outstanding'])->name('reports.outstanding');
    Route::get('/reports/export/{type}',      [ReportController::class, 'export'])->name('reports.export');

    // ─── Notifications ────────────────────────────────────────────
    Route::get('/notifications',              [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // ─── Admin (role: admin only) ─────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserManagementController::class);
        Route::post('users/{id}/impersonate', [UserManagementController::class, 'impersonate'])->name('users.impersonate');
        Route::get('audit-logs',              [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/export',       [AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('settings',                [SiteSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings',               [SiteSettingsController::class, 'update'])->name('settings.update');
        Route::get('backup',                  [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup/run',             [BackupController::class, 'run'])->name('backup.run');
    });
});

// ─── AJAX / API endpoints (authenticated, returns JSON) ───────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('dashboard/stats',  [DashboardController::class, 'stats']);
    Route::get('dashboard/chart',  [DashboardController::class, 'chartData']);
    Route::get('students/search',  [StudentController::class, 'apiSearch']);
    Route::get('fees/balance/{student}', [FeeController::class, 'getBalance']);
});
```

---

## 9. Criteria Checklist

> Track your progress. Check each item as you implement it.

### 🔐 Authentication System *(15 pts)*
- [ ] Email + password login with bcrypt
- [ ] Remember me (persistent session)
- [ ] MFA via email OTP
- [ ] Password reset via signed URL + email
- [ ] Session timeout after inactivity
- [ ] Account lockout after 5 failed attempts
- [ ] Password policy (8+ chars, upper, lower, number, special)

### 👥 User Role Management *(15 pts)*
- [ ] Admin role — full access
- [ ] Cashier/Standard role — assigned modules only
- [ ] RBAC middleware enforced on all routes
- [ ] Permission assignment per module (can_create, etc.)
- [ ] User status: Active / Inactive / Suspended
- [ ] Profile management with avatar upload
- [ ] Password reset (admin-initiated)

### 📝 Audit Logging *(10 pts)*
- [ ] Auth logs: login/logout, IP, timestamp
- [ ] CRUD logs: old value → new value
- [ ] Searchable + filterable log viewer (admin)
- [ ] Export logs to Excel/CSV
- [ ] Auto-archive logs > 90 days (scheduled command)
- [ ] Visual indicators for suspicious activity

### 📊 Dashboard *(15 pts)*
- [ ] Total students, active users, new registrations
- [ ] Daily/Weekly/Monthly activity graphs
- [ ] Quick action buttons
- [ ] Recent activities feed
- [ ] Interactive Chart.js charts
- [ ] Date range filters
- [ ] AJAX refresh (no full page reload)
- [ ] Responsive grid layout

### 🔔 Real-Time Notifications *(10 pts)*
- [ ] In-app toast for successful operations
- [ ] Warning alerts (failed login, overdue fees)
- [ ] Notification bell with unread count badge
- [ ] Mark all as read / delete
- [ ] Notification preferences per user
- [ ] Polling or SSE for real-time updates

### ⚠️ Warning & Alert System *(5 pts)*
- [ ] CAPTCHA + admin alert after 3+ failed logins
- [ ] Confirmation modal on record deletion
- [ ] Bulk operation warning with impact summary
- [ ] Session expiration countdown alert

### 💾 Automated Backup *(10 pts)*
- [ ] Weekly DB backup via scheduler
- [ ] Email notification on backup success/failure
- [ ] Manual one-click backup button
- [ ] 30-day retention policy
- [ ] Backup integrity check

### 📥 Import & Export *(10 pts)*
- [ ] Bulk student import via Excel/CSV
- [ ] Validation preview before import
- [ ] Duplicate detection
- [ ] Import progress feedback + error report download
- [ ] Export to Excel, CSV, PDF

### 📋 Reporting System *(10 pts)*
- [ ] Payment summary report
- [ ] Outstanding balances report
- [ ] Audit trail report
- [ ] Date range + status filters
- [ ] PDF + Excel output formats
- [ ] Print-friendly CSS

### 🖨️ PDF Generation *(5 pts)*
- [ ] Payment receipt PDF with logo + header/footer
- [ ] Page numbers and generation date
- [ ] Download or email PDF option
- [ ] One-click print optimization

### ✅ CRUD with Notifications *(10 pts)*
- [ ] Success toast on every create/update/delete
- [ ] Admin notification on each CRUD operation
- [ ] Soft delete on students, fees, payments
- [ ] Restore deleted items (admin)
- [ ] Cascade delete warnings

### 📑 Form Validation & UX *(10 pts)*
- [ ] Client-side validation (HTML5 + JS)
- [ ] Server-side via Form Requests
- [ ] Inline validation on blur
- [ ] Loading spinners on submit
- [ ] Breadcrumb navigation
- [ ] Accessible error messages

### 🔢 Advanced Data Controls *(10 pts)*
- [ ] Pagination (10/25/50/100 per page)
- [ ] Global search + column search
- [ ] Date range + status filters
- [ ] Sortable column headers
- [ ] Bulk actions (delete, export)
- [ ] Export current view

### 🛡️ Admin User Management *(10 pts)*
- [ ] Create/edit/delete user accounts
- [ ] Assign roles + permissions
- [ ] Impersonate user feature
- [ ] View login history + device info
- [ ] Force logout from all devices
- [ ] Bulk user import/export

### ⚙️ Site Settings *(10 pts)*
- [ ] Branding: site name, logo, favicon
- [ ] Email settings (SMTP config)
- [ ] Security policy settings
- [ ] Backup schedule settings
- [ ] Maintenance mode toggle

### 🔒 Security & Performance *(15 pts)*
- [ ] Rate limiting (100 req/min per IP)
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] XSS protection (`{{ }}` Blade encoding)
- [ ] CSRF on all forms
- [ ] bcrypt password hashing
- [ ] Security headers (HSTS, X-Frame-Options)
- [ ] Input sanitization in Form Requests

### 🎨 Professional UI/UX *(10 pts)*
- [ ] Responsive (mobile, tablet, desktop)
- [ ] Consistent Bootstrap 5 design system
- [ ] Loading states + skeleton screens
- [ ] Empty state messages/illustrations
- [ ] Breadcrumb navigation
- [ ] Cross-browser compatible

### 📚 Documentation *(10 pts)*
- [ ] GitHub repository with README.md
- [ ] ER Diagram (dbdiagram.io)
- [ ] SQL dump included
- [ ] User manual PDF with screenshots
- [ ] Presentation (10 slides)
- [ ] Live demo URL + test credentials

---

### 🌟 Bonus Features *(+10 pts)*

| Feature | Implementation | Status |
|---|---|---|
| API Documentation | Scramble (Laravel) or Swagger | [ ] |
| Multi-language | Laravel `lang/` + Filipino translations | [ ] |
| Advanced Charts | Chart.js drill-down on click | [ ] |
| PWA | `manifest.json` + service worker | [ ] |
| AI Integration | Fee categorization suggestion via API | [ ] |
| Dark Mode | CSS variables + `localStorage` toggle | [ ] |

---

*Last updated: 2025 · FCATS Implementation Plan v1.0*
