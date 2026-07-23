<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeBankDetailController;
use App\Http\Controllers\BenefitFundTypeController;
use App\Http\Controllers\BenefitReportController;
use App\Http\Controllers\EmployeeBenefitController;
use App\Http\Controllers\EmployeeBonusController;
use App\Http\Controllers\EmployeeFamilyMemberController;
use App\Http\Controllers\CompOffController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\OnDutyController;
use App\Http\Controllers\HolidayTypeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\CompanyAssetController;
use App\Http\Controllers\ConfirmationLetterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\IncrementController;
use App\Http\Controllers\IncrementLetterController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveStatusController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MobileAccessController;
use App\Http\Controllers\OtSettingController;
use App\Http\Controllers\GraceSettingController;
use App\Http\Controllers\OfferLetterController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\SalarySlipController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth ───────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── SSO ────────────────────────────────────────────────────────────────────
Route::get('/sso/redirect',  [SSOController::class, 'redirect'])->name('sso.redirect');
Route::get('/sso/callback',  [SSOController::class, 'callback'])->name('sso.callback');

// ── PWA API (public) ───────────────────────────────────────────────────────
Route::get('/api/mobile-modules', [MobileAccessController::class, 'api'])->name('mobile-access.api');

// ── Protected (all authenticated users) ───────────────────────────────────
Route::middleware('admin.auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Employees ─────────────────────────────────────────────────────────
    Route::post('employees/import',         [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('employees/import/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.import.template');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/salary-status', [EmployeeController::class, 'salaryStatus'])->name('employees.salary-status');

    // ── Increment & Promotion ─────────────────────────────────────────────
    Route::resource('increments', IncrementController::class)->except(['show']);
    Route::get('increments/employee-salary', [IncrementController::class, 'currentSalary'])->name('increments.current-salary');
    Route::resource('promotions', PromotionController::class)->except(['show']);

    // ── Loans & Advances ──────────────────────────────────────────────────
    Route::resource('loans', LoanController::class);
    Route::post('loans/{loan}/repayments', [LoanController::class, 'storeRepayment'])->name('loans.repayments.store');

    // ── Employee Documents ────────────────────────────────────────────────
    Route::get('employee-documents', [EmployeeDocumentController::class, 'index'])->name('employee-documents.index');
    Route::post('employee-documents', [EmployeeDocumentController::class, 'store'])->name('employee-documents.store');
    Route::get('employee-documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('employee-documents.download');
    Route::get('employee-documents/{document}/preview',  [EmployeeDocumentController::class, 'preview'])->name('employee-documents.preview');
    Route::delete('employee-documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employee-documents.destroy');

    // ── Company Assets ────────────────────────────────────────────────────
    // URL prefix is 'company-assets' (avoids conflict with public/assets static folder)
    // Route names stay as 'assets.*' so no views need changing.
    Route::resource('company-assets', CompanyAssetController::class)
        ->except(['show'])
        ->parameters(['company-assets' => 'asset'])
        ->names([
            'index'   => 'assets.index',
            'create'  => 'assets.create',
            'store'   => 'assets.store',
            'edit'    => 'assets.edit',
            'update'  => 'assets.update',
            'destroy' => 'assets.destroy',
        ]);
    Route::get('company-assets/{asset}/assign',  [CompanyAssetController::class, 'assign'])->name('assets.assign');
    Route::post('company-assets/{asset}/assign', [CompanyAssetController::class, 'storeAssignment'])->name('assets.assign.store');
    Route::get('company-assets/{asset}/return',  [CompanyAssetController::class, 'returnForm'])->name('assets.return-form');
    Route::post('company-assets/{asset}/return', [CompanyAssetController::class, 'processReturn'])->name('assets.return');

    // ── No Due Certificates ───────────────────────────────────────────────
    Route::get('no-due',                         [CompanyAssetController::class, 'noDueIndex'])->name('no-due.index');
    Route::get('no-due/create',                  [CompanyAssetController::class, 'noDueCreate'])->name('no-due.create');
    Route::post('no-due',                        [CompanyAssetController::class, 'noDueStore'])->name('no-due.store');
    Route::get('no-due/{certificate}',           [CompanyAssetController::class, 'noDueShow'])->name('no-due.show');
    Route::post('no-due/{certificate}/approve',  [CompanyAssetController::class, 'noDueApprove'])->name('no-due.approve');

    // ── Payroll ───────────────────────────────────────────────────────────
    Route::get('salary-slips/calculate', [SalarySlipController::class, 'calculate'])->name('salary-slips.calculate');
    Route::resource('salary-slips', SalarySlipController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('salary-slips/{salarySlip}/pdf', [SalarySlipController::class, 'pdf'])->name('salary-slips.pdf');

    // ── Attendance ────────────────────────────────────────────────────────
    Route::get('attendance',         [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance',        [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance/report',  [AttendanceController::class, 'report'])->name('attendance.report');
    Route::post('attendance/import',         [AttendanceController::class, 'import'])->name('attendance.import');
    Route::post('attendance/import-monthly', [AttendanceController::class, 'importMonthly'])->name('attendance.import-monthly');

    // ── Holidays ──────────────────────────────────────────────────────────
    Route::get('holidays',                               [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('holidays/import',                       [HolidayController::class, 'import'])->name('holidays.import');
    Route::post('holidays',                              [HolidayController::class, 'store'])->name('holidays.store');
    Route::post('holidays/{holiday}/toggle-working-day', [HolidayController::class, 'toggleWorkingDay'])->name('holidays.toggle-working-day');
    Route::post('holidays/toggle-date-working-day',     [HolidayController::class, 'toggleDateWorkingDay'])->name('holidays.toggle-date-working-day');
    Route::get('holidays/{holiday}/circular',            [HolidayController::class, 'circular'])->name('holidays.circular');
    Route::delete('holidays/{holiday}',                  [HolidayController::class, 'destroy'])->name('holidays.destroy');

    // ── On Duty ───────────────────────────────────────────────────────────
    Route::get('on-duties',             [OnDutyController::class, 'index'])->name('on-duties.index');
    Route::post('on-duties',            [OnDutyController::class, 'store'])->name('on-duties.store');
    Route::delete('on-duties/{onDuty}', [OnDutyController::class, 'destroy'])->name('on-duties.destroy');

    // ── Comp Off ──────────────────────────────────────────────────────────
    Route::get('comp-offs',                      [CompOffController::class, 'index'])->name('comp-offs.index');
    Route::post('comp-offs/bulk',                [CompOffController::class, 'bulkStore'])->name('comp-offs.bulk');
    Route::post('comp-offs/bulk-avail',          [CompOffController::class, 'bulkAvail'])->name('comp-offs.bulk-avail');
    Route::post('comp-offs/bulk-remove',         [CompOffController::class, 'bulkRemove'])->name('comp-offs.bulk-remove');
    Route::delete('comp-offs/{compOff}',         [CompOffController::class, 'destroy'])->name('comp-offs.destroy');

    // ── Employee Bank Details ─────────────────────────────────────────────
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('employees/{employee}/bank-details/edit', [EmployeeBankDetailController::class, 'edit'])->name('employee-bank-details.edit');
        Route::put('employees/{employee}/bank-details',      [EmployeeBankDetailController::class, 'upsert'])->name('employee-bank-details.upsert');
    });

    // ── Leave Requests ────────────────────────────────────────────────────
    Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leaveRequest}/reject',  [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::get('leave-history', [LeaveRequestController::class, 'history'])->name('leave-requests.history');

    // ── Leave Status (yearly balance grid) ────────────────────────────────
    Route::get('leave-status',        [LeaveStatusController::class, 'index'])->name('leave-status.index');
    Route::get('leave-status/export', [LeaveStatusController::class, 'export'])->name('leave-status.export');

    // ── Offer & Confirmation Letters ──────────────────────────────────────
    Route::resource('offer-letters', OfferLetterController::class)->except(['edit', 'update']);
    Route::get('offer-letters/{offerLetter}/pdf', [OfferLetterController::class, 'pdf'])->name('offer-letters.pdf');

    Route::resource('confirmation-letters', ConfirmationLetterController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('confirmation-letters/{confirmationLetter}/pdf', [ConfirmationLetterController::class, 'pdf'])->name('confirmation-letters.pdf');

    Route::resource('increment-letters', IncrementLetterController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('increment-letters/{incrementLetter}/pdf', [IncrementLetterController::class, 'pdf'])->name('increment-letters.pdf');

    // ── Family Members ────────────────────────────────────────────────────
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('employees/{employee}/family-members', [EmployeeFamilyMemberController::class, 'store'])->name('family-members.store');
        Route::get('family-members/{familyMember}',         [EmployeeFamilyMemberController::class, 'show'])->name('family-members.show');
        Route::put('family-members/{familyMember}',         [EmployeeFamilyMemberController::class, 'update'])->name('family-members.update');
        Route::delete('family-members/{familyMember}',      [EmployeeFamilyMemberController::class, 'destroy'])->name('family-members.destroy');
    });

    // ── Employee Benefits ──────────────────────────────────────────────────
    Route::resource('employee-benefits', EmployeeBenefitController::class);

    // ── Bonuses & Incentives ───────────────────────────────────────────────
    Route::resource('employee-bonuses', EmployeeBonusController::class);
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('employee-bonuses/{employeeBonus}/approve', [EmployeeBonusController::class, 'approve'])->name('employee-bonuses.approve');
        Route::post('employee-bonuses/{employeeBonus}/reject',  [EmployeeBonusController::class, 'reject'])->name('employee-bonuses.reject');
    });

    // ── Benefit Reports ────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('benefits',         [BenefitReportController::class, 'index'])->name('benefits-index');
        Route::get('monthly-benefits', [BenefitReportController::class, 'monthlyBenefits'])->name('monthly-benefits');
        Route::get('bonuses',          [BenefitReportController::class, 'bonuses'])->name('bonuses');
        Route::get('employee-history', [BenefitReportController::class, 'employeeHistory'])->name('employee-history');
        Route::get('payroll-impact',   [BenefitReportController::class, 'payrollImpact'])->name('payroll-impact');
    });

    // ── Training ──────────────────────────────────────────────────────────
    Route::get('training',                                              [TrainingController::class, 'index'])->name('training.index');
    Route::get('training/{trainingModule}',                             [TrainingController::class, 'show'])->name('training.show');
    Route::get('training/{trainingModule}/lessons/{lesson}',            [TrainingController::class, 'lesson'])->name('training.lesson');
    Route::post('training/{trainingModule}/lessons/{lesson}/complete',  [TrainingController::class, 'markComplete'])->name('training.lesson.complete');

    Route::middleware('role:admin')->group(function () {
        Route::get('training/module/create',                                        [TrainingController::class, 'createModule'])->name('training.module.create');
        Route::post('training/module',                                              [TrainingController::class, 'storeModule'])->name('training.module.store');
        Route::get('training/{trainingModule}/edit',                                [TrainingController::class, 'editModule'])->name('training.module.edit');
        Route::put('training/{trainingModule}',                                     [TrainingController::class, 'updateModule'])->name('training.module.update');
        Route::delete('training/{trainingModule}',                                  [TrainingController::class, 'destroyModule'])->name('training.module.destroy');
        Route::get('training/{trainingModule}/manage-lessons',                      [TrainingController::class, 'moduleLessons'])->name('training.module.lessons');
        Route::post('training/{trainingModule}/lessons',                            [TrainingController::class, 'storeLesson'])->name('training.lesson.store');
        Route::put('training/{trainingModule}/lessons/{lesson}',                    [TrainingController::class, 'updateLesson'])->name('training.lesson.update');
        Route::delete('training/{trainingModule}/lessons/{lesson}',                 [TrainingController::class, 'destroyLesson'])->name('training.lesson.destroy');
    });

    // ── Activity Log (Admin only) ─────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // ── AJAX helpers ──────────────────────────────────────────────────────
    Route::get('get-designations/{department}', [DesignationController::class, 'byDepartment'])->name('designations.by-department');

    // ── Settings (Admin only) ─────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('entities',          EntityController::class)->except(['show']);
        Route::resource('departments',       DepartmentController::class)->except(['show']);
        Route::resource('designations',      DesignationController::class)->except(['show']);
        Route::resource('salary-components', SalaryComponentController::class)->except(['show']);
        Route::resource('leave-types',       LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('holiday-types',     HolidayTypeController::class)->except(['create', 'show']);
        Route::resource('benefit-fund-types', BenefitFundTypeController::class)->except(['create', 'show']);

        // Roles & Permissions
        Route::resource('roles', RoleController::class)->except(['show']);

        // OT Settings
        Route::get('settings/ot',    [OtSettingController::class,    'show'])->name('settings.ot.show');
        Route::put('settings/ot',    [OtSettingController::class,    'update'])->name('settings.ot.update');

        // Grace & Late Permission Settings
        Route::get('settings/grace', [GraceSettingController::class, 'show'])->name('settings.grace.show');
        Route::put('settings/grace', [GraceSettingController::class, 'update'])->name('settings.grace.update');

        // Mobile / PWA access
        Route::get('mobile-access',  [MobileAccessController::class, 'index'])->name('mobile-access.index');
        Route::put('mobile-access',  [MobileAccessController::class, 'update'])->name('mobile-access.update');
    });

    // ── User Management (Admin only) ──────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
