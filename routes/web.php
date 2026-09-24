<?php

use App\Http\Controllers\AttentionRuleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeEvaluationController;
use App\Http\Controllers\EmployeeIncidentController;
use App\Http\Controllers\EvaluationConfigurationController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active-user', 'impersonation-valid'])
    ->name('dashboard');

Route::middleware(['auth', 'active-user', 'impersonation-valid'])->group(function () {
    Route::middleware('not-impersonating')->group(function () {
        Route::get('/pengaturan/pengguna', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/pengaturan/pengguna', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/pengaturan/pengguna/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('/pengaturan/pengguna/{user}/toggle', [UserManagementController::class, 'toggle'])->name('users.toggle');
        Route::post('/pengaturan/pengguna/{user}/impersonasi', [ImpersonationController::class, 'start'])->name('impersonation.start');
        Route::get('/pengaturan/organisasi', [OrganizationController::class, 'index'])->name('organization.index');
        Route::post('/pengaturan/organisasi/{type}', [OrganizationController::class, 'store'])->whereIn('type', ['cabang', 'divisi', 'sub-divisi', 'jabatan'])->name('organization.store');
        Route::put('/pengaturan/organisasi/{type}/{unit}', [OrganizationController::class, 'update'])->whereIn('type', ['cabang', 'divisi', 'sub-divisi', 'jabatan'])->name('organization.update');
        Route::post('/penilaian/konfigurasi/periode', [EvaluationConfigurationController::class, 'storePeriod'])->name('evaluation-periods.store');
        Route::put('/penilaian/konfigurasi/periode/{period}', [EvaluationConfigurationController::class, 'updatePeriod'])->name('evaluation-periods.update');
        Route::post('/penilaian/konfigurasi/komponen', [EvaluationConfigurationController::class, 'storeComponent'])->name('evaluation-components.store');
        Route::put('/penilaian/konfigurasi/komponen/{component}', [EvaluationConfigurationController::class, 'updateComponent'])->name('evaluation-components.update');
        Route::post('/penilaian/konfigurasi/kriteria', [EvaluationConfigurationController::class, 'storeCriterion'])->name('evaluation-criteria.store');
        Route::put('/penilaian/konfigurasi/kriteria/{criterion}', [EvaluationConfigurationController::class, 'updateCriterion'])->name('evaluation-criteria.update');
        Route::post('/penilaian/konfigurasi/periode/{period}/status', [EvaluationConfigurationController::class, 'transitionPeriod'])->name('evaluation-periods.transition');
        Route::post('/penilaian/konfigurasi/aturan-perhatian', [AttentionRuleController::class, 'store'])->name('attention-rules.store');
        Route::put('/penilaian/konfigurasi/aturan-perhatian/{rule}', [AttentionRuleController::class, 'update'])->name('attention-rules.update');
    });
    Route::get('/penilaian/konfigurasi', [EvaluationConfigurationController::class, 'index'])->name('evaluations.configuration');
    Route::get('/penilaian/hasil/{evaluation}', [EmployeeEvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('/penilaian/hasil/{evaluation}/status', [EmployeeEvaluationController::class, 'transition'])->name('evaluations.transition');
    Route::get('/penilaian/{employee}/{period}', [EmployeeEvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/penilaian/{employee}/{period}', [EmployeeEvaluationController::class, 'store'])->name('evaluations.store');
    Route::resource('karyawan', EmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])->parameters(['karyawan' => 'employee'])->names('employees');
    Route::post('/karyawan/{employee}/mutasi', [EmployeeController::class, 'transfer'])->name('employees.transfer');
    Route::post('/karyawan/{employee}/status', [EmployeeController::class, 'changeStatus'])->name('employees.status');
    Route::post('/karyawan/{employee}/aktifkan-kembali', [EmployeeController::class, 'rehire'])->name('employees.rehire');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/laporan/ekspor/{export}', [ReportController::class, 'download'])->name('reports.download');
    Route::get('/audit-aktivitas', AuditLogController::class)->name('audit.index');
    Route::get('/karyawan/{employee}/masalah', [EmployeeIncidentController::class, 'index'])->name('employees.incidents.index');
    Route::post('/karyawan/{employee}/masalah', [EmployeeIncidentController::class, 'store'])->name('employees.incidents.store');
    Route::get('/karyawan-bermasalah', [EmployeeIncidentController::class, 'overview'])->name('employees.incidents.overview');
    Route::put('/masalah-karyawan/{incident}', [EmployeeIncidentController::class, 'update'])->name('employees.incidents.update');
    Route::post('/masalah-karyawan/{incident}/status', [EmployeeIncidentController::class, 'transition'])->name('employees.incidents.transition');
    Route::post('/masalah-karyawan/{incident}/selesaikan', [EmployeeIncidentController::class, 'resolve'])->name('employees.incidents.resolve');
    Route::post('/karyawan/{employee}/dokumen', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('/dokumen-karyawan/{document}', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');
    Route::delete('/dokumen-karyawan/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'active-user'])->group(function () {
    Route::post('/impersonasi/selesai', [ImpersonationController::class, 'end'])->name('impersonation.end');
    Route::middleware(['impersonation-valid', 'not-impersonating'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
