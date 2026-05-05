<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Admin (SSC) ───────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'session.timeout', 'role:SSC_ADMIN'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', fn() => redirect()->route('admin.colleges.index'))->name('dashboard');

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

        // Students (read-only) + import
        Route::get('students',            [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');
        Route::post('students',           [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('students/import',     [\App\Http\Controllers\Admin\StudentController::class, 'importForm'])->name('students.import');

        // Users
        Route::resource('users',          \App\Http\Controllers\Admin\UserController::class)->except(['show']);

        // Audit logs (read-only)
        Route::get('audit-logs',          [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    });

// ── Org (COLLEGE_COUNCIL / DEPT_SOCIETY) ─────────────────────────────────────
Route::prefix('org')
    ->middleware(['auth', 'session.timeout', 'org.scope'])
    ->name('org.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Org\DashboardController::class, 'index'])->name('dashboard');

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
        Route::post('transactions/search',           [\App\Http\Controllers\Org\TransactionController::class, 'search'])->middleware('role:TREASURER,COLLECTOR')->name('transactions.search');
        Route::post('transactions',                  [\App\Http\Controllers\Org\TransactionController::class, 'store'])->middleware('role:TREASURER,COLLECTOR')->name('transactions.store');
        Route::get('transactions/{transaction}',     [\App\Http\Controllers\Org\TransactionController::class, 'show'])->middleware('role:TREASURER,COLLECTOR,AUDITOR')->name('transactions.show');

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
        Route::patch('remittances/{remittance}/accept',          [\App\Http\Controllers\Org\RemittanceController::class, 'accept'])->middleware('role:AUDITOR')->name('remittances.accept');

        // Fee Profiles
        Route::resource('fee-profiles', \App\Http\Controllers\Org\FeeProfileController::class)->except(['show'])->middleware('role:CHAIRPERSON');

        // Users (org-scoped, chairperson permission)
        Route::resource('users', \App\Http\Controllers\Org\UserController::class)->except(['show'])->middleware('role:CHAIRPERSON');

        // Reports
        Route::get('reports',            [\App\Http\Controllers\Org\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/pdf',        [\App\Http\Controllers\Org\ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('reports/csv',        [\App\Http\Controllers\Org\ReportController::class, 'exportCsv'])->name('reports.csv');
        Route::get('documentation',       fn() => view('org.documentation'))->name('documentation');

        // Audit logs (org-scoped, read-only)
        Route::get('audit-logs',         [\App\Http\Controllers\Org\AuditLogController::class, 'index'])->middleware('role:CHAIRPERSON,AUDITOR')->name('audit-logs.index');
    });
