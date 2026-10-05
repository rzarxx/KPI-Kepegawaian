<?php

use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\AttentionRuleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CalibrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeEvaluationController;
use App\Http\Controllers\EmployeeIncidentController;
use App\Http\Controllers\EvaluationConfigurationController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SelfAssessmentController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

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
        Route::delete('/pengaturan/pengguna/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/pengaturan/pengguna/{user}/impersonasi', [ImpersonationController::class, 'start'])->name('impersonation.start');
        Route::get('/pengaturan/organisasi', [OrganizationController::class, 'index'])->name('organization.index');
        Route::post('/pengaturan/organisasi/{type}', [OrganizationController::class, 'store'])->whereIn('type', ['cabang', 'divisi', 'sub-divisi', 'jabatan'])->name('organization.store');
        Route::put('/pengaturan/organisasi/{type}/{unit}', [OrganizationController::class, 'update'])->whereIn('type', ['cabang', 'divisi', 'sub-divisi', 'jabatan'])->name('organization.update');
        Route::delete('/pengaturan/organisasi/{type}/{unit}', [OrganizationController::class, 'destroy'])->whereIn('type', ['cabang', 'divisi', 'sub-divisi', 'jabatan'])->name('organization.destroy');
        Route::post('/penilaian/konfigurasi/periode', [EvaluationConfigurationController::class, 'storePeriod'])->name('evaluation-periods.store');
        Route::put('/penilaian/konfigurasi/periode/{period}', [EvaluationConfigurationController::class, 'updatePeriod'])->name('evaluation-periods.update');
        Route::delete('/penilaian/konfigurasi/periode/{period}', [EvaluationConfigurationController::class, 'destroyPeriod'])->name('evaluation-periods.destroy');
        Route::post('/penilaian/konfigurasi/komponen', [EvaluationConfigurationController::class, 'storeComponent'])->name('evaluation-components.store');
        Route::put('/penilaian/konfigurasi/komponen/{component}', [EvaluationConfigurationController::class, 'updateComponent'])->name('evaluation-components.update');
        Route::delete('/penilaian/konfigurasi/komponen/{component}', [EvaluationConfigurationController::class, 'destroyComponent'])->name('evaluation-components.destroy');
        Route::post('/penilaian/konfigurasi/kriteria', [EvaluationConfigurationController::class, 'storeCriterion'])->name('evaluation-criteria.store');
        Route::put('/penilaian/konfigurasi/kriteria/{criterion}', [EvaluationConfigurationController::class, 'updateCriterion'])->name('evaluation-criteria.update');
        Route::delete('/penilaian/konfigurasi/kriteria/{criterion}', [EvaluationConfigurationController::class, 'destroyCriterion'])->name('evaluation-criteria.destroy');
        Route::post('/penilaian/konfigurasi/periode/{period}/status', [EvaluationConfigurationController::class, 'transitionPeriod'])->name('evaluation-periods.transition');
        Route::post('/penilaian/konfigurasi/aturan-perhatian', [AttentionRuleController::class, 'store'])->name('attention-rules.store');
        Route::put('/penilaian/konfigurasi/aturan-perhatian/{rule}', [AttentionRuleController::class, 'update'])->name('attention-rules.update');
        Route::delete('/penilaian/konfigurasi/aturan-perhatian/{rule}', [AttentionRuleController::class, 'destroy'])->name('attention-rules.destroy');
        Route::get('/pengaturan/tampilan', [AppSettingsController::class, 'index'])->name('settings.appearance');
        Route::put('/pengaturan/tampilan', [AppSettingsController::class, 'update'])->name('settings.appearance.update');
        // Kalibrasi — operasi mutasi (tidak boleh saat impersonasi)
        Route::post('/kalibrasi', [CalibrationController::class, 'store'])->name('calibration.store');
        Route::post('/kalibrasi/{session}/penyesuaian', [CalibrationController::class, 'adjust'])->name('calibration.adjust');
        Route::post('/kalibrasi/{session}/status', [CalibrationController::class, 'transition'])->name('calibration.transition');
        Route::post('/kalibrasi/{session}/terapkan', [CalibrationController::class, 'apply'])->name('calibration.apply');
        Route::delete('/kalibrasi/{session}', [CalibrationController::class, 'destroy'])->name('calibration.destroy');
        // Target/Goal — operasi mutasi (tidak boleh saat impersonasi)
        Route::post('/target', [GoalController::class, 'store'])->name('goals.store');
        Route::put('/target/{goal}', [GoalController::class, 'update'])->name('goals.update');
        Route::post('/target/{goal}/status', [GoalController::class, 'transition'])->name('goals.transition');
        Route::delete('/target/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    });
    Route::get('/penilaian/konfigurasi', [EvaluationConfigurationController::class, 'index'])->name('evaluations.configuration');
    Route::get('/penilaian/hasil/{evaluation}', [EmployeeEvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('/penilaian/hasil/{evaluation}/status', [EmployeeEvaluationController::class, 'transition'])->name('evaluations.transition');
    Route::get('/penilaian/{employee}', [EmployeeEvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/penilaian/{employee}', [EmployeeEvaluationController::class, 'store'])->name('evaluations.store');
    // Self-Assessment
    Route::get('/penilaian-diri', [SelfAssessmentController::class, 'index'])->name('self-assessment.index');
    Route::get('/penilaian-diri/{period}', [SelfAssessmentController::class, 'create'])->name('self-assessment.create');
    Route::post('/penilaian-diri/{period}', [SelfAssessmentController::class, 'store'])->name('self-assessment.store');
    Route::post('/penilaian-diri/{evaluation}/ajukan', [SelfAssessmentController::class, 'submit'])->name('self-assessment.submit');
    Route::get('/penilaian-diri/hasil/{evaluation}', [SelfAssessmentController::class, 'show'])->name('self-assessment.show');
    // Kalibrasi — operasi baca (boleh saat impersonasi)
    Route::get('/kalibrasi', [CalibrationController::class, 'index'])->name('calibration.index');
    Route::get('/kalibrasi/{session}', [CalibrationController::class, 'show'])->name('calibration.show');
    // Target / Goal — operasi baca (boleh saat impersonasi)
    Route::get('/target', [GoalController::class, 'index'])->name('goals.index');
    Route::resource('pejuang', EmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])->parameters(['pejuang' => 'employee'])->names('employees');
    Route::post('/pejuang/{employee}/mutasi', [EmployeeController::class, 'transfer'])->name('employees.transfer');
    Route::post('/pejuang/{employee}/status', [EmployeeController::class, 'changeStatus'])->name('employees.status');
    Route::post('/pejuang/{employee}/aktifkan-kembali', [EmployeeController::class, 'rehire'])->name('employees.rehire');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/laporan/ekspor/{export}', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('/laporan/ekspor/{export}', [ReportController::class, 'destroyExport'])->name('reports.export.destroy');
    Route::get('/audit-aktivitas', AuditLogController::class)->name('audit.index');
    Route::get('/pejuang/{employee}/masalah', [EmployeeIncidentController::class, 'index'])->name('employees.incidents.index');
    Route::post('/pejuang/{employee}/masalah', [EmployeeIncidentController::class, 'store'])->name('employees.incidents.store');
    Route::get('/pejuang-bermasalah', [EmployeeIncidentController::class, 'overview'])->name('employees.incidents.overview');
    Route::put('/masalah-pejuang/{incident}', [EmployeeIncidentController::class, 'update'])->name('employees.incidents.update');
    Route::delete('/masalah-pejuang/{incident}', [EmployeeIncidentController::class, 'destroy'])->name('employees.incidents.destroy');
    Route::post('/masalah-pejuang/{incident}/status', [EmployeeIncidentController::class, 'transition'])->name('employees.incidents.transition');
    Route::post('/masalah-pejuang/{incident}/selesaikan', [EmployeeIncidentController::class, 'resolve'])->name('employees.incidents.resolve');
    Route::post('/pejuang/{employee}/dokumen', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('/dokumen-pejuang/{document}', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');
    Route::get('/dokumen-pejuang/{document}/lihat', [EmployeeDocumentController::class, 'preview'])->name('employees.documents.preview');
    Route::delete('/dokumen-pejuang/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'active-user'])->group(function () {
    Route::post('/impersonasi/selesai', [ImpersonationController::class, 'end'])->name('impersonation.end');
    Route::middleware(['impersonation-valid', 'not-impersonating'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__.'/auth.php';
