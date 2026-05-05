# FCATS Implementation Plan
**Fee Collection and Tracking System — Laravel Build Roadmap**

---

## Context

FCATS is a university student council fee collection platform serving a 3-tier academic hierarchy (College → Department → Program). The system needs to be rebuilt as a working Laravel application from an existing HTML/CSS/JS mockup and a complete requirements + schema document.

**Source documents used:**
- `FCATS_Requirements_and_Schema.md` — 25 FRs, 16 NFRs, 16 tables, 7 modules
- `FCATS_Implementation_Plan.md` — prior 35-day plan draft (reference only)
- `FACS 2 UI Prototype/src/imports/index.html` — 4,996-line single-file prototype (all 17 screens embedded)
- `FACS 2 UI Prototype/src/styles/` — Tailwind + CMU brand theme CSS

---

## ⚠️ Ambiguity Notes

- ⚠️ **Stack mismatch — Laravel version**: The project prompt says "Laravel 11" but `composer.json` declares `laravel/framework: ^13.0`. This plan targets **Laravel 13**. Confirm before Phase 1.
- ⚠️ **Stack mismatch — CSS framework**: The prior plan draft references "Bootstrap 5" but the mockup uses **Tailwind CSS** with CMU brand variables. This plan follows the mockup (Tailwind). If Bootstrap is required for grading, the mockup must be reskinned.
- ⚠️ **Mockup format**: The UI prototype is a React/TypeScript SPA (`/src/main.tsx`), not plain HTML pages. The actual HTML to extract lives in `FACS 2 UI Prototype/src/imports/index.html` — a single file with all 17 pages as `<section id="page-*">` blocks. Blade conversion requires manually extracting each `<section>` block.
- ⚠️ **`page-o-documentation` page**: The mockup shows a file upload zone + report export panel. No corresponding FR explicitly describes file upload storage. This plan implements only the **export** side (FR-0023, FR-0024) and flags the upload feature as out-of-scope unless a FR is identified.
- ⚠️ **Remittance ↔ Transaction relationship**: The schema lists 16 tables with no explicit pivot table for remittance batches. This plan assumes `transactions.remittance_id` is a nullable FK — unremitted transactions have `NULL`, remitted ones point to a remittance row. If a pivot table is intended, Phase 2 must be revised.
- ⚠️ **MFA (Google 2FA)**: The prior plan includes TOTP/email OTP. No FR explicitly mandates MFA. This plan implements only what FR-0004 defines (username/password + lockout). MFA is flagged as a bonus feature.
- ⚠️ **`organizations` table scope column**: FR-0003 mentions `COLLEGE_COUNCIL`, `DEPT_SOCIETY`, `SSC` as org types. The exact column name (`type`, `scope`, `org_type`) is not specified in the schema excerpt. Use what the schema document defines verbatim.

---

## Proposed Laravel Folder Structure

```
facs2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── Admin/
│   │   │   │   ├── CollegeController.php
│   │   │   │   ├── DepartmentController.php
│   │   │   │   ├── ProgramController.php
│   │   │   │   ├── AcademicYearController.php
│   │   │   │   ├── OrganizationController.php
│   │   │   │   ├── StudentController.php        ← admin view of all students
│   │   │   │   ├── UserController.php           ← admin view of all users
│   │   │   │   └── AuditLogController.php       ← system-wide audit
│   │   │   └── Org/
│   │   │       ├── DashboardController.php
│   │   │       ├── StudentController.php        ← org-scoped
│   │   │       ├── TransactionController.php    ← POS
│   │   │       ├── VoidRequestController.php
│   │   │       ├── RemittanceController.php
│   │   │       ├── FeeProfileController.php
│   │   │       ├── UserController.php           ← org-scoped
│   │   │       ├── ReportController.php
│   │   │       └── AuditLogController.php       ← org-scoped
│   │   ├── Middleware/
│   │   │   ├── AuthenticateSession.php          ← 10-min timeout (NFR-005)
│   │   │   ├── CheckPermission.php              ← slug-based RBAC (FR-0005)
│   │   │   └── EnforceOrgScope.php              ← cross-org block (FR-0006)
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   └── LoginRequest.php
│   │       ├── Admin/
│   │       │   ├── StoreCollegeRequest.php
│   │       │   ├── StoreDepartmentRequest.php
│   │       │   ├── StoreProgramRequest.php
│   │       │   ├── StoreAcademicYearRequest.php
│   │       │   ├── StoreOrganizationRequest.php
│   │       │   └── StoreUserRequest.php
│   │       └── Org/
│   │           ├── StoreStudentRequest.php
│   │           ├── ImportStudentsRequest.php
│   │           ├── StoreTransactionRequest.php
│   │           ├── StoreFeeProfileRequest.php
│   │           ├── StoreVoidRequest.php
│   │           └── StoreRemittanceRequest.php
│   ├── Models/
│   │   ├── AcademicYear.php
│   │   ├── College.php
│   │   ├── Department.php
│   │   ├── Program.php
│   │   ├── Organization.php
│   │   ├── User.php
│   │   ├── Permission.php
│   │   ├── UserPermission.php
│   │   ├── Student.php
│   │   ├── StudentEnrollment.php
│   │   ├── FeeProfile.php
│   │   ├── Transaction.php
│   │   ├── VoidRequest.php
│   │   ├── OrSequence.php
│   │   ├── Remittance.php
│   │   └── AuditLog.php
│   └── Services/
│       ├── AuthLockoutService.php               ← FR-0004 lockout logic
│       ├── OrSequenceService.php                ← FR-0018 gap-free OR numbers
│       ├── FeeCalculationService.php            ← FR-0015 dynamic assessment
│       ├── RemittanceService.php                ← FR-0020/FR-0021 workflow
│       ├── VoidRequestService.php               ← FR-0019 two-step void
│       ├── AuditLogService.php                  ← FR-0025 immutable logs
│       ├── ReportService.php                    ← FR-0024 financial summaries
│       └── PdfReceiptService.php                ← FR-0023 receipt generation
├── database/
│   ├── migrations/                              ← 16 FCATS migrations + 3 default
│   └── seeders/
│       ├── PermissionSeeder.php                 ← immutable slugs (FR-0005)
│       └── DatabaseSeeder.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                    ← master shell (sidebar + header)
│       │   ├── auth.blade.php                   ← login wrapper
│       │   └── pdf.blade.php                    ← PDF receipt layout
│       ├── auth/
│       │   └── login.blade.php
│       ├── admin/
│       │   ├── colleges/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── departments/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── programs/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── academic-years/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── organizations/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── students/
│       │   │   └── index.blade.php
│       │   ├── users/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   └── audit-logs/
│       │       └── index.blade.php
│       ├── org/
│       │   ├── dashboard.blade.php
│       │   ├── students/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── transactions/
│       │   │   ├── create.blade.php             ← POS 4-step form
│       │   │   └── show.blade.php               ← receipt view
│       │   ├── void-requests/
│       │   │   └── index.blade.php
│       │   ├── remittances/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── fee-profiles/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── users/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── reports/
│       │   │   └── index.blade.php
│       │   └── audit-logs/
│       │       └── index.blade.php
│       └── pdf/
│           └── receipt.blade.php
└── routes/
    └── web.php
```

---

## Phase 1 — Laravel Project Setup & Environment Config

**Goal:** Clean, runnable Laravel installation configured for MySQL, ready for migrations.

**Stop condition:** `php artisan serve` runs, `.env` is correct, `php artisan migrate` succeeds on empty schema.

### Files to create / modify

| File | Purpose |
|------|---------|
| `.env` | Set `DB_CONNECTION=mysql`, `DB_DATABASE=fcats`, `DB_USERNAME`, `DB_PASSWORD`, `APP_NAME="FCATS"`, `SESSION_LIFETIME=10` (NFR-005) |
| `composer.json` | Add `barryvdh/laravel-dompdf` (PDF receipts, FR-0023) |
| `package.json` | Add Tailwind CSS v3, Chart.js, Alpine.js |
| `tailwind.config.js` | Configure content paths; add CMU brand color palette from `theme.css` (`--c-green-500: #27a05a`, etc.) |
| `vite.config.js` | Ensure `resources/css/app.css` and `resources/js/app.js` are entry points |
| `resources/css/app.css` | Import Tailwind directives; copy font import (Outfit) from `fonts.css` |
| `config/session.php` | Confirm `lifetime` reads from `SESSION_LIFETIME` env |
| `config/app.php` | Set locale, timezone to match institution |

### Checklist
- [ ] `composer require barryvdh/laravel-dompdf`
- [ ] `npm install -D tailwindcss @tailwindcss/forms chart.js alpinejs`
- [ ] MySQL database `fcats` created
- [ ] `php artisan key:generate` run
- [ ] `php artisan migrate` succeeds (default Laravel tables only at this point)
- [ ] `npm run dev` compiles without error

---

## Phase 2 — Database Migrations

**Goal:** All 16 FCATS tables created in MySQL in dependency order. No orphan FKs.

**Stop condition:** `php artisan migrate:fresh` succeeds and all 16 tables exist in MySQL.

**Migration naming convention:** `YYYY_MM_DD_HHMMSS_create_{table}_table.php`

### Migration order (dependency-safe)

| # | File | Table | Notes |
|---|------|-------|-------|
| 1 | `..._create_academic_years_table.php` | `academic_years` | No FKs; `is_active` boolean with unique partial index |
| 2 | `..._create_colleges_table.php` | `colleges` | No FKs |
| 3 | `..._create_departments_table.php` | `departments` | FK → `colleges` (`ON DELETE RESTRICT`) |
| 4 | `..._create_programs_table.php` | `programs` | FK → `departments` (`ON DELETE RESTRICT`) |
| 5 | `..._create_organizations_table.php` | `organizations` | FK → `colleges` or `departments` depending on org type |
| 6 | `..._create_permissions_table.php` | `permissions` | No FKs; slugs seeded as immutable |
| 7 | `..._create_users_table.php` | `users` | FK → `organizations`; adds `failed_login_count`, `locked_until` (FR-0004) |
| 8 | `..._create_user_permissions_table.php` | `user_permissions` | FK → `users`, `permissions`; composite unique |
| 9 | `..._create_students_table.php` | `students` | Permanent identity; unique `student_number` |
| 10 | `..._create_student_enrollments_table.php` | `student_enrollments` | FK → `students`, `programs`, `academic_years`; unique `(student_id, academic_year_id)` |
| 11 | `..._create_fee_profiles_table.php` | `fee_profiles` | FK → `organizations`, `academic_years`; `DECIMAL(10,2)` amounts |
| 12 | `..._create_or_sequences_table.php` | `or_sequences` | FK → `organizations`; row-level lock target (FR-0018) |
| 13 | `..._create_remittances_table.php` | `remittances` | FK → `organizations`, `academic_years`, `users`; status ENUM |
| 14 | `..._create_transactions_table.php` | `transactions` | FK → `students`, `organizations`, `fee_profiles` (nullable), `academic_years`, `users`, `remittances` (nullable); unique `(organization_id, or_number)`; `DECIMAL(10,2)` amount; ENUM `transaction_type` |
| 15 | `..._create_void_requests_table.php` | `void_requests` | FK → `transactions`, `users`; unique `transaction_id`; status ENUM |
| 16 | `..._create_audit_logs_table.php` | `audit_logs` | FK → `users`; polymorphic `entity_type`/`entity_id`; no soft deletes (immutable) |

### Seeders to create

| File | Purpose |
|------|---------|
| `database/seeders/PermissionSeeder.php` | Insert the 7 permission slugs from FR-0005: `pos:create`, `remit:create`, `remit:verify`, `remit:accept`, `void:request`, `void:approve`, `reports:view` |
| `database/seeders/DatabaseSeeder.php` | Call `PermissionSeeder`; optionally seed one SSC admin user for dev |

### Checklist
- [ ] All 16 migration files created in dependency order
- [ ] `DECIMAL(10,2)` used for all monetary columns (NFR-009)
- [ ] No `FLOAT` used anywhere
- [ ] `php artisan migrate:fresh --seed` succeeds
- [ ] 7 permission slugs visible in `permissions` table

---

## Phase 3 — Eloquent Models & Relationships

**Goal:** All 16 models with correct `$fillable`, `$casts`, and relationship methods. No business logic yet.

**Stop condition:** `php artisan tinker` can traverse full relationship chain (e.g., `Student → enrollments → program → department → college`).

### Files to create

| File | Key relationships / notes |
|------|--------------------------|
| `app/Models/AcademicYear.php` | `hasMany` Enrollments, FeeProfiles, Transactions, Remittances; scope `active()` |
| `app/Models/College.php` | `hasMany` Departments, Organizations |
| `app/Models/Department.php` | `belongsTo` College; `hasMany` Programs |
| `app/Models/Program.php` | `belongsTo` Department; `hasMany` StudentEnrollments |
| `app/Models/Organization.php` | `belongsTo` College/Department (nullable FKs per schema); `hasMany` Users, FeeProfiles, Transactions, Remittances, OrSequences |
| `app/Models/User.php` | `belongsTo` Organization; `belongsToMany` Permissions via `user_permissions`; cast `failed_login_count` int, `locked_until` datetime |
| `app/Models/Permission.php` | `belongsToMany` Users via `user_permissions` |
| `app/Models/UserPermission.php` | Pivot model; `belongsTo` User, Permission |
| `app/Models/Student.php` | `hasMany` StudentEnrollments, Transactions; hidden internal `id`; `getRouteKeyName()` returns `student_number` (FR-0007) |
| `app/Models/StudentEnrollment.php` | `belongsTo` Student, Program, AcademicYear; unique composite guard |
| `app/Models/FeeProfile.php` | `belongsTo` Organization, AcademicYear; `hasMany` Transactions; cast amount as `string` (DECIMAL precision) |
| `app/Models/Transaction.php` | `belongsTo` Student, Organization, FeeProfile (nullable), AcademicYear, User, Remittance (nullable); `hasOne` VoidRequest; cast amount as `string` |
| `app/Models/VoidRequest.php` | `belongsTo` Transaction; two `belongsTo` User (requester / approver via separate FK columns) |
| `app/Models/OrSequence.php` | `belongsTo` Organization; never touched directly — only via `OrSequenceService` |
| `app/Models/Remittance.php` | `belongsTo` Organization, AcademicYear, User; `hasMany` Transactions |
| `app/Models/AuditLog.php` | `belongsTo` User; `morphTo` entity; no `SoftDeletes` trait |

### Checklist
- [ ] All 16 model files created
- [ ] Monetary `$casts` use `'decimal:2'` or `'string'` — never `float`
- [ ] `Student::getRouteKeyName()` returns `'student_number'`
- [ ] `AuditLog` has no `SoftDeletes` trait (immutable per FR-0025)

---

## Phase 4 — Authentication

**Goal:** Login, session management, lockout. Exactly what FR-0004 defines — nothing more.

**Stop condition:** A user can log in, is redirected to the correct panel (admin vs. org), is locked out after 5 failures for 15 minutes, and is logged out after 10 minutes of inactivity.

### Files to create

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Auth/LoginController.php` | Handle login POST; delegate lockout logic to `AuthLockoutService`; redirect admin vs. org users to separate panels |
| `app/Http/Requests/Auth/LoginRequest.php` | Validate `username` and `password` present |
| `app/Services/AuthLockoutService.php` | Increment `failed_login_count`; set `locked_until = now() + 15 min` after 5 failures; reset on success (FR-0004) |
| `app/Http/Middleware/AuthenticateSession.php` | Check `last_activity` in session; force logout if > 10 min idle (NFR-005) |
| `app/Http/Middleware/CheckPermission.php` | Resolve permission slug from route; check `user_permissions` table; abort 403 on miss (FR-0005) |
| `app/Http/Middleware/EnforceOrgScope.php` | Compare `auth()->user()->organization_id` to accessed resource org; abort 403 on mismatch (FR-0006) |
| `resources/views/auth/login.blade.php` | Extracted from `index.html` login screen; username + password fields; lockout error state |
| `resources/views/layouts/auth.blade.php` | Minimal wrapper for login page (no sidebar) |

### Routes to add in `routes/web.php`

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/login` | `LoginController@showForm` |
| POST | `/login` | `LoginController@authenticate` |
| POST | `/logout` | `LoginController@logout` |

### Checklist
- [ ] Failed login increments `failed_login_count` per user row in DB
- [ ] Counter resets to 0 on successful login
- [ ] `locked_until` checked before password verification
- [ ] Admin users redirect to `/admin/dashboard`; org users to `/org/dashboard`
- [ ] ⚠️ No MFA in this phase — out of scope per FR-0004

---

## Phase 5 — Blade Layout Shell & Mockup Conversion

**Goal:** Master layout matching the mockup's sidebar/header structure. All 17 mockup screens converted to individual Blade views (static HTML only — no controller data yet).

**Source files:** React components within `FACS 2 UI Prototype/src/`
Translate JSX layout and component files → corresponding Blade file listed below.

**Stop condition:** Every Blade view renders in browser via `php artisan serve` without errors (static/fake data acceptable).

### Mockup page → Blade view mapping

| React UI Route/Component | Blade file | Panel |
|---|---|---|
| `/admin/colleges` | `resources/views/admin/colleges/index.blade.php` | Admin |
| `/admin/departments` | `resources/views/admin/departments/index.blade.php` | Admin |
| `/admin/programs` | `resources/views/admin/programs/index.blade.php` | Admin |
| `/admin/academic-years` | `resources/views/admin/academic-years/index.blade.php` | Admin |
| `/admin/organizations` | `resources/views/admin/organizations/index.blade.php` | Admin |
| `/admin/students` | `resources/views/admin/students/index.blade.php` | Admin |
| `/admin/users` | `resources/views/admin/users/index.blade.php` | Admin |
| `/admin/audit-logs` | `resources/views/admin/audit-logs/index.blade.php` | Admin |
| `/org/dashboard` | `resources/views/org/dashboard.blade.php` | Org |
| `/org/students` | `resources/views/org/students/index.blade.php` | Org |
| `/org/pos` | `resources/views/org/transactions/create.blade.php` | Org |
| `/org/void-requests` | `resources/views/org/void-requests/index.blade.php` | Org |
| `/org/remittances` | `resources/views/org/remittances/index.blade.php` | Org |
| `/org/fee-profiles` | `resources/views/org/fee-profiles/index.blade.php` | Org |
| `/org/users` | `resources/views/org/users/index.blade.php` | Org |
| `/org/reports` | `resources/views/org/reports/index.blade.php` | Org ⚠️ export panel only |
| `/org/audit-logs` | `resources/views/org/audit-logs/index.blade.php` | Org |

### Layout & partial files to create

| File | Purpose |
|------|---------|
| `resources/views/layouts/app.blade.php` | Master shell: top header (58px), left sidebar (234px), `@yield('content')` slot; conditionally includes admin or org sidebar partial based on user type |
| `resources/views/layouts/pdf.blade.php` | Minimal no-sidebar layout for DomPDF |
| `resources/views/partials/sidebar-admin.blade.php` | 8-item admin navigation extracted from mockup |
| `resources/views/partials/sidebar-org.blade.php` | 9-item org navigation extracted from mockup |
| `resources/views/partials/header.blade.php` | Top bar with user name, logout button |
| `resources/views/partials/breadcrumb.blade.php` | Breadcrumb component |
| `resources/css/app.css` | Tailwind directives + CMU brand CSS variables (`--c-green-500`, etc.) |

### Checklist
- [ ] All 17 Blade views render without PHP errors
- [ ] CMU primary green `#27a05a` defined in `tailwind.config.js`
- [ ] Button styles match mockup (`.btn-green`, `.btn-outline`, `.btn-danger-soft`)
- [ ] Sidebar shows admin vs. org nav based on authenticated user's type

---

## Phase 6 — Core CRUD Routes & Controllers

**Goal:** All CRUD operations wired to live database data. Each module is independently functional after this phase.

**Stop condition:** A logged-in admin can create/edit/delete colleges, departments, programs, orgs, academic years, and users. A logged-in org officer can manage students and fee profiles.

### Route groups in `routes/web.php`

```
Route::prefix('admin')->middleware(['auth', 'session.timeout', 'admin.role'])->group(...)
Route::prefix('org')->middleware(['auth', 'session.timeout', 'org.scope'])->group(...)
```

### Admin controllers

| File | Route prefix | Actions |
|------|-------------|---------|
| `app/Http/Controllers/Admin/CollegeController.php` | `/admin/colleges` | index, create, store, edit, update, destroy |
| `app/Http/Controllers/Admin/DepartmentController.php` | `/admin/departments` | index, create, store, edit, update, destroy |
| `app/Http/Controllers/Admin/ProgramController.php` | `/admin/programs` | index, create, store, edit, update, destroy |
| `app/Http/Controllers/Admin/AcademicYearController.php` | `/admin/academic-years` | index, create, store, edit, update, setActive |
| `app/Http/Controllers/Admin/OrganizationController.php` | `/admin/organizations` | index, create, store, edit, update, destroy |
| `app/Http/Controllers/Admin/UserController.php` | `/admin/users` | index, create, store, edit, update, destroy |
| `app/Http/Controllers/Admin/StudentController.php` | `/admin/students` | index (read-only) |
| `app/Http/Controllers/Admin/AuditLogController.php` | `/admin/audit-logs` | index |

### Org controllers (this phase — data access only; financial logic in Phase 7)

| File | Route prefix | Actions |
|------|-------------|---------|
| `app/Http/Controllers/Org/DashboardController.php` | `/org/dashboard` | index (stat cards, no charts yet) |
| `app/Http/Controllers/Org/StudentController.php` | `/org/students` | index, create, store, edit, update, import (POST) |
| `app/Http/Controllers/Org/FeeProfileController.php` | `/org/fee-profiles` | index, create, store, edit, update |
| `app/Http/Controllers/Org/UserController.php` | `/org/users` | index, create, store, edit, update (chairperson permission required) |
| `app/Http/Controllers/Org/AuditLogController.php` | `/org/audit-logs` | index |

### Form Requests to create

| File | Key validation rules |
|------|---------------------|
| `app/Http/Requests/Admin/StoreCollegeRequest.php` | `code` unique in colleges, `name` required |
| `app/Http/Requests/Admin/StoreDepartmentRequest.php` | `name` required, `college_id` exists |
| `app/Http/Requests/Admin/StoreProgramRequest.php` | `name` required, `department_id` exists |
| `app/Http/Requests/Admin/StoreAcademicYearRequest.php` | year + semester combination unique; enforce only one `is_active = true` |
| `app/Http/Requests/Admin/StoreOrganizationRequest.php` | `name` required, org type ENUM, scope FK exists |
| `app/Http/Requests/Admin/StoreUserRequest.php` | `username` unique, `password` min:8, `organization_id` exists |
| `app/Http/Requests/Org/StoreStudentRequest.php` | `student_number` unique, `program_id` within org's college/dept scope (FR-0009) |
| `app/Http/Requests/Org/ImportStudentsRequest.php` | file is `csv` or `xlsx`, max 5MB |
| `app/Http/Requests/Org/StoreFeeProfileRequest.php` | `amount` numeric, `category` ENUM, `organization_id` matches authenticated user |

### Checklist
- [ ] Deleting a College with Departments returns a user-friendly error (not a 500)
- [ ] Only one academic year can have `is_active = true` (DB unique + app guard)
- [ ] All org controllers filter queries by `auth()->user()->organization_id`
- [ ] Student routes use `student_number` as URL key, never internal `id` (FR-0007)
- [ ] CSV student import implemented in `StudentController@import` (FR-0008)

---

## Phase 7 — Business Logic Services

**Goal:** All financial workflows implemented via service classes. Controllers remain thin.

**Stop condition:** Complete payment cycle works end-to-end: find student → select fee → pay → OR-numbered receipt → void request → void approval → remittance creation → verify → accept.

### Services to create

| File | Logic implemented |
|------|------------------|
| `app/Services/OrSequenceService.php` | Acquire row-level lock (`lockForUpdate()`) on `or_sequences` for the org inside a DB transaction; increment `last_number`; return formatted OR string (e.g., `ENG-001`) — FR-0018 |
| `app/Services/FeeCalculationService.php` | Resolve amount from `fee_profiles` by student category (Regular/Irregular/Extendee/Exempted); handle manual fine amount for FINE-type transactions — FR-0015, FR-0013 |
| `app/Services/VoidRequestService.php` | Create request (status=PENDING); approve (status=APPROVED, mark transaction voided); reject (status=REJECTED) — FR-0019 |
| `app/Services/RemittanceService.php` | Fetch all `remittance_id IS NULL` transactions for org + active semester; batch into new Remittance; advance status PENDING→VERIFIED→ACCEPTED — FR-0020, FR-0021 |
| `app/Services/AuditLogService.php` | Write immutable row to `audit_logs` after every state-changing controller action — FR-0025 |
| `app/Services/PdfReceiptService.php` | Render `pdf/receipt.blade.php` via DomPDF; return PDF stream; support 80mm thermal and A4 half-sheet modes — FR-0023 |
| `app/Services/ReportService.php` | Aggregate transactions by fee type and payment mode for org + semester filter; return structured array for view and export — FR-0024 |

### Financial workflow controllers (added this phase)

| File | Route prefix | Actions |
|------|-------------|---------|
| `app/Http/Controllers/Org/TransactionController.php` | `/org/transactions` | create (POS form + AJAX student search), store (calls `OrSequenceService` + `FeeCalculationService`), show (receipt view) |
| `app/Http/Controllers/Org/VoidRequestController.php` | `/org/void-requests` | index, store (create request), approve, reject |
| `app/Http/Controllers/Org/RemittanceController.php` | `/org/remittances` | index, store (create batch), show, verify, accept |
| `app/Http/Controllers/Org/ReportController.php` | `/org/reports` | index (filter form), exportPdf, exportCsv |

### Additional Form Requests

| File | Key validation rules |
|------|---------------------|
| `app/Http/Requests/Org/StoreTransactionRequest.php` | Student enrolled in active semester; `fee_profile_id` required if `transaction_type = FEE`; `reference_number` required if `payment_method = GCASH` (FR-0016); `amount` numeric positive |
| `app/Http/Requests/Org/StoreVoidRequest.php` | `reason` required; transaction `organization_id` matches user's org |
| `app/Http/Requests/Org/StoreRemittanceRequest.php` | At least one unremitted transaction exists for org in active semester |

### Checklist
- [ ] OR number generation wrapped in `DB::transaction()` + `lockForUpdate()` — gap-free under concurrency
- [ ] GCash payments blocked at Form Request level without `reference_number`
- [ ] Fine transactions stored with `fee_profile_id = NULL` (validated in Form Request + DB check constraint)
- [ ] `AuditLogService::log()` called after every create/update/status-change in controllers
- [ ] Remittance batch auto-captures all `remittance_id IS NULL` transactions for org + semester
- [ ] PDF receipt renders for both 80mm and A4 paper sizes

---

## Phase 8 — Reports & PDF

**Goal:** Org-scoped financial reports with PDF and CSV export.

**Stop condition:** A user with `reports:view` permission can filter and download a collection report by semester, fee type, and payment mode.

### Files to create

| File | Purpose |
|------|---------|
| `resources/views/org/reports/index.blade.php` | Filter form (Report Type dropdown, Semester, Date From/To); PDF and CSV export buttons — from mockup `page-o-documentation` right panel |
| `resources/views/pdf/receipt.blade.php` | DomPDF receipt template: OR number, student name/ID, amount, payment method, officer, semester, org name — FR-0023 |
| `resources/views/pdf/report.blade.php` | DomPDF report template: collection summary tables by fee type and payment mode — FR-0024 |

### Report types (FR-0024 only)
- Collection summary by fee type
- Collection summary by payment mode (Cash vs GCash)

⚠️ "Outstanding Balances" report is **not** defined in FR-0024 — do not implement unless a FR is confirmed.

### Checklist
- [ ] `reports:view` permission enforced via `CheckPermission` middleware on all report routes
- [ ] All report queries scoped to `auth()->user()->organization_id`
- [ ] PDF export uses `barryvdh/laravel-dompdf` returning a streamed response
- [ ] CSV export uses Laravel `StreamedResponse` with `text/csv` content type
- [ ] Date range filter applied to `transactions.created_at`

---

## Phase 9 — Security Hardening & Validation

**Goal:** All NFR security requirements enforced at the framework level.

**Stop condition:** OWASP Top 10 mitigations confirmed; rate limiting active on all public endpoints; all inputs validated server-side.

### Files to create / modify

| File | Purpose |
|------|---------|
| `app/Http/Middleware/CheckPermission.php` | Final review — every protected route must resolve a slug; 403 on miss |
| `app/Http/Middleware/EnforceOrgScope.php` | Final review — every resource access verified against authenticated user's org |
| `bootstrap/app.php` | Register `AuthenticateSession`, `CheckPermission`, `EnforceOrgScope` in middleware aliases |
| `config/session.php` | Set `secure = true`, `http_only = true`, `same_site = 'lax'` (NFR-004) |
| All `*Request.php` files | Audit all form requests for missing sanitization; add `strip_tags` rule where free-text fields accept user input |
| `routes/web.php` | Add `throttle:5,1` to login POST (brute-force); `throttle:100,1` to API/AJAX endpoints (NFR-002) |

### Security checklist (NFR-004, NFR-007, NFR-008)
- [ ] All DB queries use Eloquent ORM or `DB::select()` with bound params — no string interpolation
- [ ] CSRF token on every POST/PATCH/DELETE form — no `@csrf` omissions
- [ ] `Content-Security-Policy` header set (via middleware or package)
- [ ] `X-Frame-Options: DENY` header set
- [ ] Passwords stored with `Hash::make()` (bcrypt) — never MD5/SHA1
- [ ] `DECIMAL(10,2)` confirmed in all monetary migration columns (NFR-009)
- [ ] Session `lifetime = 10` enforced end-to-end (middleware + `.env`)
- [ ] Cross-org access returns 403, not 404 (prevent information disclosure)
- [ ] `audit_logs` table has no `deleted_at` column — enforced at schema level (FR-0025)

---

## Phase 10 — Testing & Deployment Checklist

**Goal:** Functional confidence before handoff. Covers local verification and deployment readiness.

**Stop condition:** All feature test suites pass; manual QA checklist cleared.

### Feature test files to create

| File | What it tests |
|------|--------------|
| `tests/Feature/Auth/LoginTest.php` | Successful login, wrong password, lockout after 5 attempts, locked account error message |
| `tests/Feature/Admin/CollegeCrudTest.php` | Create / edit / delete college; cascade restriction when departments exist |
| `tests/Feature/Org/TransactionFlowTest.php` | Enroll student → create FEE transaction → verify OR number assigned → request void → approve void |
| `tests/Feature/Org/RemittanceFlowTest.php` | Create remittance batch → verify → accept → confirm transactions now have `remittance_id` |
| `tests/Feature/Org/OrSequenceTest.php` | Concurrent OR number requests produce no gaps (test with DB transactions) |
| `tests/Feature/Security/OrgScopeTest.php` | User from Org A receives 403 when accessing Org B's resources |

### Manual QA checklist

**Authentication**
- [ ] Correct credentials → redirect to correct panel
- [ ] 5 wrong passwords → account locked for 15 min
- [ ] 10 min idle → auto-logout

**Admin panel**
- [ ] Create College → Create Department → Create Program (cascade works)
- [ ] Delete College with Departments → blocked with error message, no 500
- [ ] Activate new Academic Year → previous year becomes inactive automatically

**POS (must complete in ≤4 steps per FR-0010)**
- [ ] Search student (1) → select fee type (2) → enter payment method (3) → confirm (4) → receipt displayed
- [ ] GCash without `reference_number` → blocked
- [ ] Fine entry with manual amount → `fee_profile_id` is NULL in DB

**Void workflow**
- [ ] Request void → `void_requests.status = PENDING`
- [ ] Approve void → transaction flagged, OR number cannot be reused
- [ ] Reject void → transaction remains valid

**Remittance workflow**
- [ ] Create remittance → all unremitted org transactions attached
- [ ] Verify → status = VERIFIED
- [ ] Accept → status = ACCEPTED; all attached transactions have `remittance_id` set

**Reports**
- [ ] PDF collection report downloads and is readable (fee type breakdown)
- [ ] CSV collection report downloads with correct columns

**Security**
- [ ] Access `/org/{id}/...` of another org while authenticated → 403 (not 404)
- [ ] POST form without CSRF token → 419 Page Expired

### Deployment readiness checklist
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache` run
- [ ] MySQL production user has DML-only permissions (no DROP/CREATE)
- [ ] `storage/` and `bootstrap/cache/` writable by web server user
- [ ] `php artisan storage:link` run
- [ ] `npm run build` produces compiled assets in `public/build/`

---

## Mockup-to-Controller Data Flow Reference

| Mockup element | Data source | Controller method |
|---|---|---|
| Dashboard stat cards (4 totals) | `Transaction` aggregates, org-scoped | `DashboardController@index` |
| Dashboard bar chart (monthly collection) | `Transaction` grouped by month | `DashboardController@chartData` (JSON for Chart.js) |
| Dashboard donut chart (Cash vs GCash) | `Transaction` grouped by `payment_method` | `DashboardController@chartData` |
| Recent transactions table (bottom of dashboard) | Latest 10 `Transaction` rows, org-scoped | `DashboardController@index` |
| POS student search field | `Student` joined with active `StudentEnrollment` | `TransactionController@search` (AJAX JSON) |
| POS fee selection grid (Regular/Irregular/etc.) | `FeeProfile` for org + active semester | `TransactionController@create` |
| OR number on receipt | `OrSequenceService::next($org)` | `TransactionController@store` |
| Void request badge count in sidebar | `VoidRequest` where `status = PENDING`, org-scoped | Shared view composer on `layouts/app.blade.php` |
| Remittance 3-stage progress indicator | `Remittance.status` ENUM value | `RemittanceController@index` |
