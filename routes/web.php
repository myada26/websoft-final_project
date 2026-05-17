<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Org\DashboardController; // [AI Narrator]
use App\Http\Controllers\Org\ReportController; // [AI Narrator]
use App\Http\Controllers\PublicAccountabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/',                  [NotificationController::class, 'index'])->name('index');
    Route::post('{id}/read',         [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('read-all',          [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('{id}',            [NotificationController::class, 'destroy'])->name('destroy');
});

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Student accountability portal — unauthenticated, rate-limited (FR-0030)
Route::get('/check-fees', [PublicAccountabilityController::class, 'index'])
    ->middleware('throttle:20,1')
    ->name('public.check-fees');

// ── Admin (SSC) ───────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'session.timeout', 'role:SSC_ADMIN'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');

        // Academic Structure
        Route::resource('colleges',       \App\Http\Controllers\Admin\CollegeController::class)->except(['show']);
        Route::resource('departments',    \App\Http\Controllers\Admin\DepartmentController::class)->except(['show']);
        Route::resource('programs',       \App\Http\Controllers\Admin\ProgramController::class)->except(['show']);
        Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class)
            ->except(['show', 'destroy'])
            ->parameters(['academic-years' => 'academicYear']);
        // Set-active action for academic years
        Route::patch('academic-years/{academicYear}/set-active', [\App\Http\Controllers\Admin\AcademicYearController::class, 'setActive'])
            ->name('academic-years.set-active');

        // Organizations
        Route::resource('organizations',  \App\Http\Controllers\Admin\OrganizationController::class)->except(['show']);

        // Fee Profiles (SSC Admin only)
        Route::patch('fee-profiles/grid-row', [\App\Http\Controllers\Admin\FeeProfileController::class, 'updateGridRow'])
            ->name('fee-profiles.grid-row.update');
        Route::resource('fee-profiles', \App\Http\Controllers\Admin\FeeProfileController::class)->except(['show']);

        // Students (read-only) + import
        Route::get('students',                     [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');
        Route::post('students',                    [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('students/import',              [\App\Http\Controllers\Admin\StudentController::class, 'importForm'])->name('students.import');
        Route::post('students/import',             [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('students.import.store');
        Route::get('students/import/template',     [\App\Http\Controllers\Admin\StudentController::class, 'downloadTemplate'])->name('students.template');
        // Cascading dropdown JSON feeds (FR-0014)
        Route::get('students/departments-by-college', [\App\Http\Controllers\Admin\StudentController::class, 'departmentsByCollege'])->name('students.departments-by-college');
        Route::get('students/programs-by-department', [\App\Http\Controllers\Admin\StudentController::class, 'programsByDepartment'])->name('students.programs-by-department');

        // Import logs (A4 — queued Excel import via StudentImportController)
        Route::get('imports',                                    [\App\Http\Controllers\Admin\StudentImportController::class, 'index'])->name('imports.index');
        Route::post('imports',                                   [\App\Http\Controllers\Admin\StudentImportController::class, 'store'])->name('imports.store');
        Route::get('imports/template',                           [\App\Http\Controllers\Admin\StudentImportController::class, 'downloadTemplate'])->name('imports.template');
        Route::get('imports/{importLog}/failure-report',         [\App\Http\Controllers\Admin\StudentImportController::class, 'downloadFailureReport'])->name('imports.failure-report');
        Route::get('imports/{importLog}/status',                 [\App\Http\Controllers\Admin\StudentImportController::class, 'status'])->name('imports.status');

        // Users
        Route::resource('users',          \App\Http\Controllers\Admin\UserController::class)->except(['show']);

        // Audit logs (read-only)
        Route::get('audit-logs',              [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}',   [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');

        // Backup trigger (B5)
        Route::post('backup/trigger', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'triggerBackup'])->name('backup.trigger');
    });

// ── Org (COLLEGE_COUNCIL / CLASS_ORG) ────────────────────────────────────────
Route::prefix('org')
    ->middleware(['auth', 'session.timeout', 'org.scope'])
    ->name('org.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Org\DashboardController::class, 'index'])
            ->name('dashboard');

        // Students
        Route::get('students', [\App\Http\Controllers\Org\StudentController::class, 'index'])
            ->middleware('role:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR')
            ->name('students.index');
        Route::resource('students', \App\Http\Controllers\Org\StudentController::class)
            ->except(['index', 'show', 'destroy'])
            ->middleware('role:CHAIRPERSON')
            ->parameters(['students' => 'student']);
        Route::get('students/import',   [\App\Http\Controllers\Org\StudentController::class, 'importForm'])->middleware('role:CHAIRPERSON')->name('students.import');
        Route::post('students/import',  [\App\Http\Controllers\Org\StudentController::class, 'import'])->middleware('role:CHAIRPERSON')->name('students.import.store');

        // Transactions (POS)
        Route::get('transactions',                   [\App\Http\Controllers\Org\TransactionController::class, 'index'])->middleware('role:TREASURER,AUDITOR')->name('transactions.index');
        Route::get('transactions/create',            [\App\Http\Controllers\Org\TransactionController::class, 'create'])->middleware('role:TREASURER,COLLECTOR')->name('transactions.create');
        Route::post('transactions/search',           [\App\Http\Controllers\Org\TransactionController::class, 'search'])->middleware(['role:TREASURER,COLLECTOR', 'throttle:pos'])->name('transactions.search');
        Route::post('transactions',                  [\App\Http\Controllers\Org\TransactionController::class, 'store'])->middleware(['role:TREASURER,COLLECTOR', 'throttle:pos'])->name('transactions.store');
        Route::get('transactions/{transaction}',         [\App\Http\Controllers\Org\TransactionController::class, 'show'])->middleware('role:TREASURER,COLLECTOR,AUDITOR')->name('transactions.show');
        Route::get('transactions/{transaction}/receipt', [\App\Http\Controllers\Org\TransactionController::class, 'receipt'])->middleware('role:TREASURER,COLLECTOR,AUDITOR')->name('transactions.receipt');
        // Fine payment transaction (separate from FEE flow to avoid validation mismatch)
        Route::post('transactions/fine',                 [\App\Http\Controllers\Org\TransactionController::class, 'storeFine'])->middleware(['role:TREASURER,COLLECTOR', 'throttle:pos'])->name('transactions.fine');

        // Fine Collection Window
        Route::get('fine-collection', [\App\Http\Controllers\Org\FineCollectionWindowController::class, 'index'])
            ->middleware('role:TREASURER,AUDITOR,CHAIRPERSON')
            ->name('fine-windows.index');
        Route::patch('fine-collection/open', [\App\Http\Controllers\Org\FineCollectionWindowController::class, 'open'])
            ->middleware('role:TREASURER')
            ->name('fine-windows.open');
        Route::patch('fine-collection/close', [\App\Http\Controllers\Org\FineCollectionWindowController::class, 'close'])
            ->middleware('role:TREASURER')
            ->name('fine-windows.close');

        // Void Requests
        Route::get('void-requests',                              [\App\Http\Controllers\Org\VoidRequestController::class, 'index'])->middleware('role:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR')->name('void-requests.index');
        Route::post('void-requests',                             [\App\Http\Controllers\Org\VoidRequestController::class, 'store'])->middleware('role:TREASURER,COLLECTOR')->name('void-requests.store');
        Route::patch('void-requests/{voidRequest}/approve',      [\App\Http\Controllers\Org\VoidRequestController::class, 'approve'])->middleware('role:CHAIRPERSON')->name('void-requests.approve');
        Route::patch('void-requests/{voidRequest}/reject',       [\App\Http\Controllers\Org\VoidRequestController::class, 'reject'])->middleware('role:CHAIRPERSON')->name('void-requests.reject');

        // Remittances
        Route::get('remittances',                                [\App\Http\Controllers\Org\RemittanceController::class, 'index'])->middleware('role:TREASURER,AUDITOR')->name('remittances.index');
        Route::post('remittances',                               [\App\Http\Controllers\Org\RemittanceController::class, 'store'])->middleware('role:TREASURER')->name('remittances.store');
        Route::get('remittances/{remittance}',                   [\App\Http\Controllers\Org\RemittanceController::class, 'show'])->middleware('role:TREASURER,AUDITOR')->name('remittances.show');
        Route::patch('remittances/{remittance}/verify',          [\App\Http\Controllers\Org\RemittanceController::class, 'verify'])->middleware('role:AUDITOR')->name('remittances.verify');
        Route::patch('remittances/{remittance}/accept',          [\App\Http\Controllers\Org\RemittanceController::class, 'accept'])->middleware('role:AUDITOR,CHAIRPERSON')->name('remittances.accept');

        // Reports
        Route::get('reports/sor', [\App\Http\Controllers\Org\ReportController::class, 'sor'])
            ->middleware('role:CHAIRPERSON')
            ->name('reports.sor');
        Route::get('reports/sor/pdf', [\App\Http\Controllers\Org\ReportController::class, 'sorPdf'])
            ->middleware('role:CHAIRPERSON')
            ->name('reports.sor.pdf');

        // ── Events (Module 8 — FR-0026) ───────────────────────────────────────────
        Route::get('events', [\App\Http\Controllers\Org\EventController::class, 'index'])
            ->middleware('role:CHAIRPERSON,AUDITOR,SECRETARY')
            ->name('events.index');
        Route::get('events/create', [\App\Http\Controllers\Org\EventController::class, 'create'])
            ->middleware('role:CHAIRPERSON')
            ->name('events.create');
        Route::post('events', [\App\Http\Controllers\Org\EventController::class, 'store'])
            ->middleware('role:CHAIRPERSON')
            ->name('events.store');

        // Static sub-paths under events/{event}/attendance must come BEFORE wildcard toggle route
        Route::get('events/{event}/attendance', [\App\Http\Controllers\Org\AttendanceController::class, 'sheet'])
            ->middleware('role:CHAIRPERSON,AUDITOR,SECRETARY')
            ->name('attendance.sheet');
        Route::post('events/{event}/attendance/save-draft', [\App\Http\Controllers\Org\AttendanceController::class, 'saveDraft'])
            ->middleware('role:SECRETARY')
            ->name('attendance.save-draft');
        Route::post('events/{event}/attendance/submit', [\App\Http\Controllers\Org\AttendanceController::class, 'submit'])
            ->middleware('role:SECRETARY')
            ->name('attendance.submit');
        Route::patch('events/{event}/attendance/auditor-approve', [\App\Http\Controllers\Org\AttendanceController::class, 'auditorApprove'])
            ->middleware('role:AUDITOR')
            ->name('attendance.auditor-approve');
        Route::patch('events/{event}/attendance/auditor-forward', [\App\Http\Controllers\Org\AttendanceController::class, 'auditorForward'])
            ->middleware('role:AUDITOR')
            ->name('attendance.auditor-forward');
        Route::patch('events/{event}/attendance/auditor-reject', [\App\Http\Controllers\Org\AttendanceController::class, 'auditorReject'])
            ->middleware('role:AUDITOR')
            ->name('attendance.auditor-reject');
        Route::get('events/{event}/attendance/diff', [\App\Http\Controllers\Org\AttendanceController::class, 'diff'])
            ->middleware('role:CHAIRPERSON')
            ->name('attendance.diff');
        Route::patch('events/{event}/attendance/chairperson-confirm', [\App\Http\Controllers\Org\AttendanceController::class, 'chairpersonConfirm'])
            ->middleware('role:CHAIRPERSON')
            ->name('attendance.chairperson-confirm');
        Route::patch('events/{event}/attendance/chairperson-reject', [\App\Http\Controllers\Org\AttendanceController::class, 'chairpersonReject'])
            ->middleware('role:CHAIRPERSON')
            ->name('attendance.chairperson-reject');

        // Slot toggle (uses /toggle/ prefix to avoid clashing with static sub-paths above)
        Route::patch('events/{event}/attendance/toggle/{student:id}/{slot}', [\App\Http\Controllers\Org\AttendanceController::class, 'toggleSlot'])
            ->middleware('role:SECRETARY,AUDITOR')
            ->name('attendance.toggle-slot');

        // Event show + re-sync (after all sub-resource routes to prevent route shadowing)
        Route::post('events/{event}/resync', [\App\Http\Controllers\Org\EventController::class, 'resync'])
            ->middleware('role:CHAIRPERSON')
            ->name('events.resync');
        Route::get('events/{event}', [\App\Http\Controllers\Org\EventController::class, 'show'])
            ->middleware('role:CHAIRPERSON,AUDITOR,SECRETARY')
            ->name('events.show');

        // Fee Profiles
        Route::resource('fee-profiles', \App\Http\Controllers\Org\FeeProfileController::class)->except(['show'])->middleware('role:CHAIRPERSON');

        // Users (org-scoped, chairperson permission)
        Route::resource('users', \App\Http\Controllers\Org\UserController::class)->except(['show'])->middleware('role:CHAIRPERSON');

        // Reports
        Route::get('reports',            [\App\Http\Controllers\Org\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/pdf',        [\App\Http\Controllers\Org\ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('reports/csv',        [\App\Http\Controllers\Org\ReportController::class, 'exportCsv'])->name('reports.csv');
        // [AI Narrator] AI Narrator export
        Route::post('reports/ai-report', [ReportController::class, 'exportAiReport'])
             ->name('reports.ai.export'); // [AI Narrator]

        // [AI Narrator] AI ask endpoint
        Route::post('ai/ask', [DashboardController::class, 'askAi'])
             ->name('ai.ask'); // [AI Narrator]
        Route::get('documentation',       fn() => view('org.documentation'))->name('documentation');

        // Audit logs (org-scoped, read-only)
        Route::get('audit-logs',         [\App\Http\Controllers\Org\AuditLogController::class, 'index'])->middleware('role:CHAIRPERSON,AUDITOR')->name('audit-logs.index');
    });
