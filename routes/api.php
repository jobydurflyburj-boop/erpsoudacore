<?php

use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\BranchController;
use App\Http\Controllers\Api\V1\Admin\CompanyController;
use App\Http\Controllers\Api\V1\Admin\CompanySettingsController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DepartmentController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Platform\NotificationController;
use App\Http\Controllers\Api\V1\Platform\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\Platform\PushDeviceTokenController;
use App\Http\Controllers\Api\V1\Platform\TaskController;
use App\Http\Controllers\Api\V1\Crm\CrmDashboardController;
use App\Http\Controllers\Api\V1\Crm\CrmNavigationController;
use App\Http\Controllers\Api\V1\Crm\CustomerActivityController;
use App\Http\Controllers\Api\V1\Crm\CustomerController;
use App\Http\Controllers\Api\V1\Crm\LeadActivityController;
use App\Http\Controllers\Api\V1\Crm\LeadAttachmentController;
use App\Http\Controllers\Api\V1\Crm\LeadController;
use App\Http\Controllers\Api\V1\Crm\LeadSourceController;
use App\Http\Controllers\Api\V1\Crm\LeadStatusController;
use App\Http\Controllers\Api\V1\Crm\OpportunityActivityController;
use App\Http\Controllers\Api\V1\Crm\OpportunityController;
use App\Http\Controllers\Api\V1\Crm\OpportunityStageController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Inventory\StockController;
use App\Http\Controllers\Api\V1\Inventory\WarehouseController;
use App\Http\Controllers\Api\V1\Inventory\ProductCategoryController;
use App\Http\Controllers\Api\V1\Inventory\UnitController;
use App\Http\Controllers\Api\V1\Inventory\BrandController;
use App\Http\Controllers\Api\V1\Inventory\StockTransferController;
use App\Http\Controllers\Api\V1\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\V1\Inventory\GoodsReceiptController;
use App\Http\Controllers\Api\V1\Inventory\GoodsIssueController;
use App\Http\Controllers\Api\V1\Purchase\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Purchase\SupplierController;
use App\Http\Controllers\Api\V1\Purchase\SupplierBillController;
use App\Http\Controllers\Api\V1\Purchase\SupplierPaymentController;
use App\Http\Controllers\Api\V1\Purchase\DebitNoteController;
use App\Http\Controllers\Api\V1\Purchase\PurchaseReturnController;
use App\Http\Controllers\Api\V1\Purchase\PurchaseDashboardController;
use App\Http\Controllers\Api\V1\Sales\QuotationController;
use App\Http\Controllers\Api\V1\Sales\SalesInvoiceController;
use App\Http\Controllers\Api\V1\Sales\SalesOrderController;
use App\Http\Controllers\Api\V1\Sales\DeliveryNoteController;
use App\Http\Controllers\Api\V1\Sales\CustomerPaymentController;
use App\Http\Controllers\Api\V1\Sales\CreditNoteController;
use App\Http\Controllers\Api\V1\Sales\SalesReturnController;
use App\Http\Controllers\Api\V1\Sales\SalesDashboardController;
use App\Http\Controllers\Api\V1\Accounting\ChartOfAccountController;
use App\Http\Controllers\Api\V1\Accounting\JournalEntryController;
use App\Http\Controllers\Api\V1\Hr\AttendanceController;
use App\Http\Controllers\Api\V1\Hr\CandidateController;
use App\Http\Controllers\Api\V1\Hr\DesignationController;
use App\Http\Controllers\Api\V1\Hr\EmployeeController;
use App\Http\Controllers\Api\V1\Hr\EmployeeSelfServiceController;
use App\Http\Controllers\Api\V1\Hr\HolidayController;
use App\Http\Controllers\Api\V1\Hr\HrDashboardController;
use App\Http\Controllers\Api\V1\Hr\JobApplicationController;
use App\Http\Controllers\Api\V1\Hr\JobOpeningController;
use App\Http\Controllers\Api\V1\Hr\LeaveRequestController;
use App\Http\Controllers\Api\V1\Hr\LeaveTypeController;
use App\Http\Controllers\Api\V1\Hr\OvertimeController;
use App\Http\Controllers\Api\V1\Hr\PayrollController;
use App\Http\Controllers\Api\V1\Hr\PayslipController;
use App\Http\Controllers\Api\V1\Hr\PerformanceReviewController;
use App\Http\Controllers\Api\V1\Hr\PerformanceReviewCycleController;
use App\Http\Controllers\Api\V1\Hr\SalaryComponentController;
use App\Http\Controllers\Api\V1\Hr\ShiftController;
use App\Http\Controllers\Api\V1\Reports\AnalyticsController;
use App\Http\Controllers\Api\V1\Reports\CustomReportController;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Http\Controllers\Api\V1\Reports\ReportExportController;
use App\Http\Controllers\Api\V1\Reports\ScheduledReportController;
use App\Http\Controllers\Api\V1\Ai\AiActivityLogController;
use App\Http\Controllers\Api\V1\System\HealthCheckController;
use App\Http\Controllers\Api\V1\Ai\AiAssistantController;
use App\Http\Controllers\Api\V1\Ai\AiInsightController;
use App\Http\Controllers\Api\V1\Ai\AiPromptTemplateController;
use App\Http\Controllers\Api\V1\Ai\AiSettingsController;
use App\Http\Controllers\Api\V1\Ai\AiSuggestionController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutAllDevicesController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\Auth\RegisterCompanyController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\SuperAdminLoginController;
use App\Http\Controllers\Api\V1\Auth\TenantLookupController;
use App\Http\Controllers\Api\V1\SuperAdmin\PlatformMetricsController;
use App\Http\Controllers\Api\V1\SuperAdmin\PlatformTenantController;
use App\Http\Controllers\Api\V1\Auth\VerifyLoginOtpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Tenancy + Auth + RBAC foundation
|--------------------------------------------------------------------------
| Every route below is versioned under /api/v1 (see the "v1" prefix
| group). ResolveTenant middleware already ran globally (bootstrap/app.php)
| by the time any of these handlers run — it's what makes $request->user()
| always belong to the resolved tenant, and what makes 'tenant.active'
| meaningful to check.
|
| No CRM/Sales/Inventory/etc. routes exist here — deliberately out of
| scope for this foundation. A future module registers its own route
| file the same shape as this one and is included the same way.
*/

Route::prefix('v1')->group(function () {

    // Production Readiness — a real, deep health check for load
    // balancers/uptime monitors — deliberately outside every tenant/
    // auth middleware group below, since it's checked by
    // infrastructure that can't authenticate and needs to work even
    // when tenant resolution would otherwise fail. See
    // App\Http\Controllers\Api\V1\System\HealthCheckController for
    // what it actually verifies.
    Route::get('/health', [HealthCheckController::class, 'index']);

    // ---- Public (no auth, tenant resolved from subdomain/header) ----
    Route::post('/public/tenants/register', RegisterCompanyController::class)->middleware('throttle:auth');
    Route::get('/public/tenants/lookup', [TenantLookupController::class, 'bySubdomain']);

    // Production Readiness — Security hardening (OWASP): a real, tighter
    // rate limit (10/min, keyed by IP + submitted identifier together —
    // see AppServiceProvider) on every classic brute-force/enumeration
    // target this API has. Before this sprint these shared only the
    // generic 60/min-per-user API-wide throttle.
    Route::post('/auth/login', LoginController::class)->middleware('throttle:auth');
    Route::post('/auth/otp/verify', VerifyLoginOtpController::class)->middleware('throttle:auth');
    Route::post('/auth/refresh', RefreshTokenController::class)->middleware('throttle:auth');
    Route::post('/auth/forgot-password', ForgotPasswordController::class)->middleware('throttle:auth');
    Route::post('/auth/reset-password', ResetPasswordController::class)->middleware('throttle:auth');
    Route::get('/auth/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    // ---- Super Admin (platform-level, separate identity from tenant users) ----
    Route::post('/admin/auth/login', SuperAdminLoginController::class)->middleware('throttle:auth');

    // ---- Authenticated, tenant-scoped ----
    // Order matters: auth:sanctum resolves WHO is calling, then
    // tenant.bind_authenticated confirms/binds WHICH tenant this token
    // belongs to (see that middleware's docblock for why this can't be
    // left implicit), THEN tenant.active checks that tenant's billing
    // status. A future Super Admin console route group should use
    // ['auth:sanctum', 'tenant.bind_authenticated'] WITHOUT 'tenant.active'
    // — Super Admin requests never resolve a tenant at all.
    Route::middleware(['auth:sanctum', 'tenant.bind_authenticated', 'tenant.active', 'track.activity'])->group(function () {

        Route::post('/auth/logout', LogoutController::class);
        Route::post('/auth/logout-all', LogoutAllDevicesController::class);
        Route::post('/auth/change-password', ChangePasswordController::class);
        Route::post('/auth/verify-email/resend', [EmailVerificationController::class, 'resend']);

        Route::get('/auth/sessions', [SessionController::class, 'index']);
        Route::delete('/auth/sessions/{id}', [SessionController::class, 'destroy']);

        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/me', [ProfileController::class, 'update']);
        Route::post('/me/avatar', [ProfileController::class, 'updateAvatar']);

        // ---- Dashboard ----
        Route::middleware('permission:dashboard.view')->get('/dashboard', [DashboardController::class, 'index']);

        // ---- Notification Center — personal inbox, no RBAC gate beyond auth ----
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);
        Route::post('/push-tokens', [PushDeviceTokenController::class, 'store']);
        Route::delete('/push-tokens/{token}', [PushDeviceTokenController::class, 'destroy']);

        // ---- Tasks — personal productivity list, no RBAC gate beyond auth ----
        Route::apiResource('tasks', TaskController::class);

        // ---- Admin module: user, role, permission, department, branch, company management ----
        Route::prefix('admin')->group(function () {

            Route::middleware('permission:admin.view')->group(function () {
                Route::get('/users', [UserController::class, 'index']);
                Route::get('/users/{user}', [UserController::class, 'show']);
                Route::get('/roles', [RoleController::class, 'index']);
                Route::get('/roles/{role}', [RoleController::class, 'show']);
                Route::get('/permissions', [PermissionController::class, 'index']);
                Route::get('/departments', [DepartmentController::class, 'index']);
                Route::get('/departments/{department}', [DepartmentController::class, 'show']);
                Route::get('/branches', [BranchController::class, 'index']);
                Route::get('/branches/{branch}', [BranchController::class, 'show']);
                Route::get('/companies', [CompanyController::class, 'index']);
                Route::get('/companies/{company}', [CompanyController::class, 'show']);
                Route::get('/company-profile', [CompanyController::class, 'profile']);
                Route::get('/company-settings', [CompanySettingsController::class, 'index']);
            });

            Route::middleware('permission:admin.create')->group(function () {
                Route::post('/users', [UserController::class, 'store']);
                Route::post('/roles', [RoleController::class, 'store']);
                Route::post('/departments', [DepartmentController::class, 'store']);
                Route::post('/branches', [BranchController::class, 'store']);
                Route::post('/companies', [CompanyController::class, 'store']);
            });

            Route::middleware('permission:admin.edit')->group(function () {
                Route::patch('/users/{user}', [UserController::class, 'update']);
                Route::patch('/users/{user}/role', [UserController::class, 'changeRole']);
                Route::patch('/users/{user}/status', [UserController::class, 'setStatus']);
                Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
                Route::put('/users/{user}/branches', [UserController::class, 'assignBranches']);
                Route::patch('/roles/{role}', [RoleController::class, 'update']);
                Route::put('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
                Route::patch('/departments/{department}', [DepartmentController::class, 'update']);
                Route::patch('/branches/{branch}', [BranchController::class, 'update']);
                Route::patch('/companies/{company}', [CompanyController::class, 'update']);
                Route::post('/companies/{company}/logo', [CompanyController::class, 'uploadLogo']);
                Route::patch('/company-profile', [CompanyController::class, 'updateProfile']);
                Route::put('/company-settings', [CompanySettingsController::class, 'update']);
            });

            Route::middleware('permission:admin.delete')->group(function () {
                Route::delete('/users/{user}', [UserController::class, 'destroy']);
                Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
                Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
                Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);
            });
        });

        // ---- Platform/core: audit + activity logs ----
        Route::middleware('permission:core.view')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        });
        Route::middleware('permission:core.export')->get('/activity-logs/export', [ActivityLogController::class, 'export']);

        // ---- CRM Sprint 1: Lead Management foundation ----
        // Record-level scoping (Sales sees/edits only leads assigned to
        // them) is enforced by LeadPolicy + LeadController's own query
        // scoping, layered on top of these module-level permission gates
        // — see docs/CRM_MODULE.md "Record-level scoping".
        Route::prefix('crm')->group(function () {
            Route::middleware('permission:crm.view')->group(function () {
                Route::get('/navigation', [CrmNavigationController::class, 'index']);
                Route::get('/dashboard', [CrmDashboardController::class, 'index']);
                Route::get('/leads', [LeadController::class, 'index']);
                Route::get('/leads/{lead}', [LeadController::class, 'show']);
                Route::get('/leads/{lead}/activities', [LeadActivityController::class, 'index']);
                Route::get('/leads/{lead}/attachments', [LeadAttachmentController::class, 'index']);
                Route::get('/lead-sources', [LeadSourceController::class, 'index']);
                Route::get('/lead-sources/{leadSource}', [LeadSourceController::class, 'show']);
                Route::get('/lead-statuses', [LeadStatusController::class, 'index']);
                Route::get('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'show']);
                Route::get('/customers', [CustomerController::class, 'index']);
                Route::get('/customers/{customer}', [CustomerController::class, 'show']);
                Route::get('/customers/{customer}/activities', [CustomerActivityController::class, 'index']);
                Route::get('/opportunities', [OpportunityController::class, 'index']);
                Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show']);
                Route::get('/opportunities/{opportunity}/activities', [OpportunityActivityController::class, 'index']);
                Route::get('/opportunity-stages', [OpportunityStageController::class, 'index']);
                Route::get('/opportunity-stages/{opportunityStage}', [OpportunityStageController::class, 'show']);
            });

            Route::middleware('permission:crm.create')->group(function () {
                Route::post('/leads', [LeadController::class, 'store']);
                Route::post('/lead-sources', [LeadSourceController::class, 'store']);
                Route::post('/lead-statuses', [LeadStatusController::class, 'store']);
                Route::post('/customers', [CustomerController::class, 'store']);
                Route::post('/opportunities', [OpportunityController::class, 'store']);
                Route::post('/opportunity-stages', [OpportunityStageController::class, 'store']);
            });

            Route::middleware('permission:crm.edit')->group(function () {
                Route::patch('/leads/{lead}', [LeadController::class, 'update']);
                Route::put('/leads/{lead}/assign', [LeadController::class, 'assign']);
                Route::post('/leads/{lead}/convert-to-customer', [LeadController::class, 'convertToCustomer']);
                Route::post('/leads/{lead}/activities', [LeadActivityController::class, 'store']);
                Route::post('/leads/{lead}/attachments', [LeadAttachmentController::class, 'store']);
                Route::delete('/leads/{lead}/attachments/{attachment}', [LeadAttachmentController::class, 'destroy']);
                Route::patch('/lead-sources/{leadSource}', [LeadSourceController::class, 'update']);
                Route::patch('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'update']);
                Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
                Route::post('/customers/{customer}/activities', [CustomerActivityController::class, 'store']);
                Route::patch('/opportunities/{opportunity}', [OpportunityController::class, 'update']);
                Route::put('/opportunities/{opportunity}/assign', [OpportunityController::class, 'assign']);
                Route::post('/opportunities/{opportunity}/activities', [OpportunityActivityController::class, 'store']);
                Route::patch('/opportunity-stages/{opportunityStage}', [OpportunityStageController::class, 'update']);
            });

            Route::middleware('permission:crm.delete')->group(function () {
                Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);
                Route::delete('/lead-sources/{leadSource}', [LeadSourceController::class, 'destroy']);
                Route::delete('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'destroy']);
                Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
                Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy']);
                Route::delete('/opportunity-stages/{opportunityStage}', [OpportunityStageController::class, 'destroy']);
            });
        });

        // ---- Inventory ----
        Route::prefix('inventory')->group(function () {
            Route::middleware('permission:inventory.view')->group(function () {
                Route::get('/products', [ProductController::class, 'index']);
                Route::get('/products/{product}', [ProductController::class, 'show']);
                Route::get('/products-by-barcode', [ProductController::class, 'findByBarcode']);
                Route::get('/categories', [ProductCategoryController::class, 'index']);
                Route::get('/categories/{productCategory}', [ProductCategoryController::class, 'show']);
                Route::get('/units', [UnitController::class, 'index']);
                Route::get('/units/{unit}', [UnitController::class, 'show']);
                Route::get('/brands', [BrandController::class, 'index']);
                Route::get('/brands/{brand}', [BrandController::class, 'show']);
                Route::get('/warehouses', [WarehouseController::class, 'index']);
                Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show']);
                Route::get('/stock-levels', [StockController::class, 'levels']);
                Route::get('/stock-movements', [StockController::class, 'movements']);
                Route::get('/low-stock', [StockController::class, 'lowStock']);
                Route::get('/transfers', [StockTransferController::class, 'index']);
                Route::get('/transfers/{stockTransfer}', [StockTransferController::class, 'show']);
                Route::get('/adjustments', [StockAdjustmentController::class, 'index']);
                Route::get('/adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show']);
                Route::get('/goods-receipts', [GoodsReceiptController::class, 'index']);
                Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show']);
                Route::get('/goods-issues', [GoodsIssueController::class, 'index']);
                Route::get('/goods-issues/{goodsIssue}', [GoodsIssueController::class, 'show']);
            });
            Route::middleware('permission:inventory.create')->group(function () {
                Route::post('/products', [ProductController::class, 'store']);
                Route::post('/categories', [ProductCategoryController::class, 'store']);
                Route::post('/units', [UnitController::class, 'store']);
                Route::post('/brands', [BrandController::class, 'store']);
                Route::post('/warehouses', [WarehouseController::class, 'store']);
                Route::post('/transfers', [StockTransferController::class, 'store']);
                Route::post('/adjustments', [StockAdjustmentController::class, 'store']);
                Route::post('/goods-receipts', [GoodsReceiptController::class, 'store']);
                Route::post('/goods-issues', [GoodsIssueController::class, 'store']);
            });
            Route::middleware('permission:inventory.edit')->group(function () {
                Route::patch('/products/{product}', [ProductController::class, 'update']);
                Route::patch('/categories/{productCategory}', [ProductCategoryController::class, 'update']);
                Route::patch('/units/{unit}', [UnitController::class, 'update']);
                Route::patch('/brands/{brand}', [BrandController::class, 'update']);
                Route::patch('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
                Route::post('/stock-adjustments', [StockController::class, 'adjust']); // quick single-line adjust — distinct from the tracked /adjustments entity below
                Route::post('/transfers/{stockTransfer}/complete', [StockTransferController::class, 'complete']);
                Route::post('/adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve']);
                Route::post('/goods-receipts/{goodsReceipt}/receive', [GoodsReceiptController::class, 'receive']);
                Route::post('/goods-issues/{goodsIssue}/issue', [GoodsIssueController::class, 'issue']);
            });
            Route::middleware('permission:inventory.delete')->group(function () {
                Route::delete('/products/{product}', [ProductController::class, 'destroy']);
                Route::delete('/categories/{productCategory}', [ProductCategoryController::class, 'destroy']);
                Route::delete('/units/{unit}', [UnitController::class, 'destroy']);
                Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
                Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
                Route::delete('/transfers/{stockTransfer}', [StockTransferController::class, 'destroy']);
                Route::delete('/adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy']);
                Route::delete('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'destroy']);
                Route::delete('/goods-issues/{goodsIssue}', [GoodsIssueController::class, 'destroy']);
            });
        });

        // ---- Purchase ----
        Route::prefix('purchase')->group(function () {
            Route::middleware('permission:purchase.view')->group(function () {
                Route::get('/dashboard', [PurchaseDashboardController::class, 'index']);
                Route::get('/suppliers', [SupplierController::class, 'index']);
                Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
                Route::get('/orders', [PurchaseOrderController::class, 'index']);
                Route::get('/orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
                Route::get('/bills', [SupplierBillController::class, 'index']);
                Route::get('/bills/{supplierBill}', [SupplierBillController::class, 'show']);
                Route::get('/payments', [SupplierPaymentController::class, 'index']);
                Route::get('/payments/{payment}', [SupplierPaymentController::class, 'show']);
                Route::get('/debit-notes', [DebitNoteController::class, 'index']);
                Route::get('/debit-notes/{debitNote}', [DebitNoteController::class, 'show']);
                Route::get('/returns', [PurchaseReturnController::class, 'index']);
                Route::get('/returns/{purchaseReturn}', [PurchaseReturnController::class, 'show']);
            });
            Route::middleware('permission:purchase.create')->group(function () {
                Route::post('/suppliers', [SupplierController::class, 'store']);
                Route::post('/orders', [PurchaseOrderController::class, 'store']);
                Route::post('/bills', [SupplierBillController::class, 'store']);
                Route::post('/goods-receipts/{goodsReceipt}/bill', [SupplierBillController::class, 'fromGoodsReceipt']);
                Route::post('/payments', [SupplierPaymentController::class, 'store']);
                Route::post('/debit-notes', [DebitNoteController::class, 'store']);
                Route::post('/returns', [PurchaseReturnController::class, 'store']);
            });
            Route::middleware('permission:purchase.edit')->group(function () {
                Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
                Route::patch('/orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
                Route::patch('/bills/{supplierBill}', [SupplierBillController::class, 'update']);
                Route::post('/bills/{supplierBill}/record-payment', [SupplierBillController::class, 'recordPayment']);
                Route::post('/payments/{payment}/allocate', [SupplierPaymentController::class, 'allocate']);
                Route::post('/debit-notes/{debitNote}/issue', [DebitNoteController::class, 'issue']);
                Route::post('/returns/{purchaseReturn}/return', [PurchaseReturnController::class, 'returnGoods']);
            });
            Route::middleware('permission:purchase.approve')->group(function () {
                Route::post('/orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
                Route::post('/bills/{supplierBill}/approve', [SupplierBillController::class, 'approve']);
            });
            Route::middleware('permission:purchase.delete')->group(function () {
                Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
                Route::delete('/orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
                Route::delete('/bills/{supplierBill}', [SupplierBillController::class, 'destroy']);
            });
        });

        // ---- Sales ----
        Route::prefix('sales')->group(function () {
            Route::middleware('permission:sales.view')->group(function () {
                Route::get('/dashboard', [SalesDashboardController::class, 'index']);
                Route::get('/quotations', [QuotationController::class, 'index']);
                Route::get('/quotations/{quotation}', [QuotationController::class, 'show']);
                Route::get('/orders', [SalesOrderController::class, 'index']);
                Route::get('/orders/{salesOrder}', [SalesOrderController::class, 'show']);
                Route::get('/invoices', [SalesInvoiceController::class, 'index']);
                Route::get('/invoices/{salesInvoice}', [SalesInvoiceController::class, 'show']);
                Route::get('/delivery-notes', [DeliveryNoteController::class, 'index']);
                Route::get('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'show']);
                Route::get('/payments', [CustomerPaymentController::class, 'index']);
                Route::get('/payments/{payment}', [CustomerPaymentController::class, 'show']);
                Route::get('/credit-notes', [CreditNoteController::class, 'index']);
                Route::get('/credit-notes/{creditNote}', [CreditNoteController::class, 'show']);
                Route::get('/returns', [SalesReturnController::class, 'index']);
                Route::get('/returns/{salesReturn}', [SalesReturnController::class, 'show']);
            });
            Route::middleware('permission:sales.create')->group(function () {
                Route::post('/quotations', [QuotationController::class, 'store']);
                Route::post('/orders', [SalesOrderController::class, 'store']);
                Route::post('/invoices', [SalesInvoiceController::class, 'store']);
                Route::post('/delivery-notes', [DeliveryNoteController::class, 'store']);
                Route::post('/orders/{salesOrder}/deliver', [DeliveryNoteController::class, 'fromSalesOrder']);
                Route::post('/payments', [CustomerPaymentController::class, 'store']);
                Route::post('/credit-notes', [CreditNoteController::class, 'store']);
                Route::post('/returns', [SalesReturnController::class, 'store']);
            });
            Route::middleware('permission:sales.edit')->group(function () {
                Route::patch('/quotations/{quotation}', [QuotationController::class, 'update']);
                Route::patch('/orders/{salesOrder}', [SalesOrderController::class, 'update']);
                Route::patch('/invoices/{salesInvoice}', [SalesInvoiceController::class, 'update']);
                Route::post('/quotations/{quotation}/convert-to-order', [QuotationController::class, 'convertToSalesOrder']);
                Route::post('/orders/{salesOrder}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice']);
                Route::post('/invoices/{salesInvoice}/issue', [SalesInvoiceController::class, 'issue']);
                Route::post('/invoices/{salesInvoice}/record-payment', [SalesInvoiceController::class, 'recordPayment']);
                Route::post('/delivery-notes/{deliveryNote}/deliver', [DeliveryNoteController::class, 'deliver']);
                Route::post('/payments/{payment}/allocate', [CustomerPaymentController::class, 'allocate']);
                Route::post('/credit-notes/{creditNote}/issue', [CreditNoteController::class, 'issue']);
                Route::post('/returns/{salesReturn}/receive', [SalesReturnController::class, 'receive']);
            });
            Route::middleware('permission:sales.delete')->group(function () {
                Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);
                Route::delete('/orders/{salesOrder}', [SalesOrderController::class, 'destroy']);
                Route::delete('/invoices/{salesInvoice}', [SalesInvoiceController::class, 'destroy']);
                Route::delete('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'destroy']);
            });
        });

        // ---- Accounting ----
        Route::prefix('accounting')->group(function () {
            Route::middleware('permission:accounting.view')->group(function () {
                Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index']);
                Route::get('/journal-entries', [JournalEntryController::class, 'index']);
                Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);
            });
            Route::middleware('permission:accounting.create')->group(function () {
                Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store']);
                Route::post('/journal-entries', [JournalEntryController::class, 'store']);
            });
            Route::middleware('permission:accounting.edit')->group(function () {
                Route::patch('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'update']);
                Route::post('/journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse']);
            });
        });

        // ---- HR & Payroll ----
        Route::prefix('hr')->group(function () {
            Route::middleware('permission:hr_payroll.view')->group(function () {
                Route::get('/dashboard', [HrDashboardController::class, 'summary']);
                Route::get('/designations', [DesignationController::class, 'index']);
                Route::get('/shifts', [ShiftController::class, 'index']);
                Route::get('/holidays', [HolidayController::class, 'index']);
                Route::get('/employees', [EmployeeController::class, 'index']);
                Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
                Route::get('/leave-types', [LeaveTypeController::class, 'index']);
                Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
                Route::get('/attendance', [AttendanceController::class, 'index']);
                Route::get('/salary-components', [SalaryComponentController::class, 'index']);
                Route::get('/overtime', [OvertimeController::class, 'index']);
                Route::get('/payroll-runs', [PayrollController::class, 'index']);
                Route::get('/payroll-runs/{payrollRun}', [PayrollController::class, 'show']);
                Route::get('/payslips', [PayslipController::class, 'index']);
                Route::get('/payslips/{payslip}', [PayslipController::class, 'show']);
                Route::get('/job-openings', [JobOpeningController::class, 'index']);
                Route::get('/candidates', [CandidateController::class, 'index']);
                Route::get('/job-applications', [JobApplicationController::class, 'index']);
                Route::get('/performance-review-cycles', [PerformanceReviewCycleController::class, 'index']);
                Route::get('/performance-reviews', [PerformanceReviewController::class, 'index']);
            });
            Route::middleware('permission:hr_payroll.create')->group(function () {
                Route::post('/designations', [DesignationController::class, 'store']);
                Route::post('/shifts', [ShiftController::class, 'store']);
                Route::post('/holidays', [HolidayController::class, 'store']);
                Route::post('/employees', [EmployeeController::class, 'store']);
                Route::post('/leave-types', [LeaveTypeController::class, 'store']);
                Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
                Route::post('/attendance/mark', [AttendanceController::class, 'mark']);
                Route::post('/salary-components', [SalaryComponentController::class, 'store']);
                Route::post('/overtime', [OvertimeController::class, 'store']);
                Route::post('/job-openings', [JobOpeningController::class, 'store']);
                Route::post('/candidates', [CandidateController::class, 'store']);
                Route::post('/job-applications', [JobApplicationController::class, 'store']);
                Route::post('/job-applications/{jobApplication}/hire', [JobApplicationController::class, 'hire']);
                Route::post('/performance-review-cycles', [PerformanceReviewCycleController::class, 'store']);
                Route::post('/performance-reviews', [PerformanceReviewController::class, 'store']);
                Route::post('/payroll-runs/process', [PayrollController::class, 'process']);
            });
            Route::middleware('permission:hr_payroll.edit')->group(function () {
                Route::patch('/designations/{designation}', [DesignationController::class, 'update']);
                Route::patch('/shifts/{shift}', [ShiftController::class, 'update']);
                Route::patch('/employees/{employee}', [EmployeeController::class, 'update']);
                Route::post('/employees/{employee}/terminate', [EmployeeController::class, 'terminate']);
                Route::post('/employees/{employee}/salary-components', [EmployeeController::class, 'assignSalaryComponents']);
                Route::patch('/job-applications/{jobApplication}/status', [JobApplicationController::class, 'updateStatus']);
                Route::post('/job-openings/{jobOpening}/close', [JobOpeningController::class, 'close']);
                Route::post('/performance-review-cycles/{performanceReviewCycle}/close', [PerformanceReviewCycleController::class, 'close']);
                Route::patch('/performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'update']);
                Route::post('/performance-reviews/{performanceReview}/submit', [PerformanceReviewController::class, 'submit']);
                Route::post('/performance-reviews/{performanceReview}/acknowledge', [PerformanceReviewController::class, 'acknowledge']);
                Route::post('/payroll-runs/{payrollRun}/mark-paid', [PayrollController::class, 'markPaid']);
            });
            Route::middleware('permission:hr_payroll.approve')->group(function () {
                Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
                Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
                Route::post('/overtime/{overtimeRecord}/approve', [OvertimeController::class, 'approve']);
                Route::post('/overtime/{overtimeRecord}/reject', [OvertimeController::class, 'reject']);
            });
            // Cancel is intentionally open to the same 'create' grant that lets someone submit a leave request in the first place — see LeaveService::cancel()'s pending-only restriction.
            Route::middleware('permission:hr_payroll.create')->post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);
        });

        // ---- Employee Self-Service ----
        Route::prefix('ess')->group(function () {
            Route::middleware('permission:ess.view')->group(function () {
                Route::get('/profile', [EmployeeSelfServiceController::class, 'profile']);
                Route::get('/attendance', [EmployeeSelfServiceController::class, 'attendance']);
                Route::get('/leave-requests', [EmployeeSelfServiceController::class, 'leaveRequests']);
                Route::get('/payslips', [EmployeeSelfServiceController::class, 'payslips']);
            });
            Route::middleware('permission:ess.create')->group(function () {
                Route::post('/attendance/check-in', [EmployeeSelfServiceController::class, 'checkIn']);
                Route::post('/attendance/check-out', [EmployeeSelfServiceController::class, 'checkOut']);
                Route::post('/leave-requests', [EmployeeSelfServiceController::class, 'requestLeave']);
            });
        });

        // ---- Reports ----
        Route::middleware('permission:reports.view')->prefix('reports')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales']);
            Route::get('/purchases', [ReportController::class, 'purchases']);
            Route::get('/inventory', [ReportController::class, 'inventory']);
            Route::get('/trial-balance', [ReportController::class, 'trialBalance']);
            Route::get('/sales-by-customer', [ReportController::class, 'salesByCustomer']);
            Route::get('/sales-by-product', [ReportController::class, 'salesByProduct']);
            Route::get('/aging-receivables', [ReportController::class, 'agingReceivables']);
            Route::get('/stock-by-warehouse', [ReportController::class, 'stockByWarehouse']);
            Route::get('/inventory-by-category', [ReportController::class, 'inventoryByCategory']);
            Route::get('/purchase-by-supplier', [ReportController::class, 'purchaseBySupplier']);
            Route::get('/aging-payables', [ReportController::class, 'agingPayables']);
            Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
            Route::get('/payroll-summary', [ReportController::class, 'payrollSummary']);
            Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
            Route::get('/vat-report', [ReportController::class, 'vatReport']);
            Route::get('/leads-by-source', [ReportController::class, 'leadsBySource']);
            Route::get('/leads-by-status', [ReportController::class, 'leadsByStatus']);
            Route::get('/opportunities-by-stage', [ReportController::class, 'opportunitiesByStage']);
            Route::get('/conversion-funnel', [ReportController::class, 'conversionFunnel']);
            Route::get('/executive-summary', [AnalyticsController::class, 'executiveSummary']);
            Route::get('/kpi-summary', [AnalyticsController::class, 'kpiSummary']);
            Route::get('/export/{reportKey}', [ReportExportController::class, 'export']);
            Route::get('/custom-reports', [CustomReportController::class, 'index']);
            Route::get('/custom-reports/sources', [CustomReportController::class, 'sources']);
            Route::get('/custom-reports/{customReport}', [CustomReportController::class, 'show']);
            Route::get('/custom-reports/{customReport}/run', [CustomReportController::class, 'run']);
            Route::get('/scheduled-reports', [ScheduledReportController::class, 'index']);
        });
        Route::middleware('permission:reports.create')->prefix('reports')->group(function () {
            Route::post('/custom-reports', [CustomReportController::class, 'store']);
            Route::post('/scheduled-reports', [ScheduledReportController::class, 'store']);
        });
        Route::middleware('permission:reports.edit')->prefix('reports')->group(function () {
            Route::patch('/custom-reports/{customReport}', [CustomReportController::class, 'update']);
            Route::patch('/scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'update']);
            Route::post('/scheduled-reports/{scheduledReport}/run-now', [ScheduledReportController::class, 'runNow']);
        });
        Route::middleware('permission:reports.delete')->prefix('reports')->group(function () {
            Route::delete('/custom-reports/{customReport}', [CustomReportController::class, 'destroy']);
            Route::delete('/scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'destroy']);
        });

        // ---- AI Assistant ----
        Route::middleware('permission:ai.view')->prefix('ai')->group(function () {
            Route::get('/status', [AiAssistantController::class, 'status']);
            Route::get('/conversations', [AiAssistantController::class, 'conversations']);
            Route::get('/conversations/{conversation}', [AiAssistantController::class, 'show']);
            Route::post('/ask', [AiAssistantController::class, 'ask']);
            Route::get('/insights/dashboard', [AiInsightController::class, 'dashboard']);
            Route::get('/insights/sales', [AiInsightController::class, 'sales']);
            Route::get('/insights/inventory', [AiInsightController::class, 'inventory']);
            Route::get('/insights/financial', [AiInsightController::class, 'financial']);
            Route::get('/insights/crm', [AiInsightController::class, 'crm']);
            Route::post('/report-summary', [AiInsightController::class, 'reportSummary']);
            Route::get('/suggestions', [AiSuggestionController::class, 'index']);
            Route::post('/suggestions/{aiSuggestion}/dismiss', [AiSuggestionController::class, 'dismiss']);
            Route::post('/suggestions/{aiSuggestion}/mark-actioned', [AiSuggestionController::class, 'markActioned']);
            Route::get('/settings', [AiSettingsController::class, 'show']);
            Route::get('/prompt-templates', [AiPromptTemplateController::class, 'index']);
            Route::get('/activity-logs', [AiActivityLogController::class, 'index']);
        });
        Route::middleware('permission:ai.edit')->prefix('ai')->group(function () {
            Route::patch('/settings', [AiSettingsController::class, 'update']);
            Route::put('/prompt-templates', [AiPromptTemplateController::class, 'upsert']);
            Route::post('/prompt-templates/{key}/reset', [AiPromptTemplateController::class, 'resetToDefault']);
        });
    });

    // ---- Super Admin Console — platform-level, no tenant ever resolved ----
    // Deliberately its own group, NOT nested in the tenant-scoped block
    // above: 'tenant.active' would reject every Super Admin request
    // outright (no tenant is ever bound for this identity — see
    // EnsureSuperAdmin's docblock and bootstrap/app.php's routing note).
    Route::middleware(['auth:sanctum', 'tenant.bind_authenticated', 'ensure.super_admin'])
        ->prefix('admin/platform')
        ->group(function () {
            Route::get('/metrics', [PlatformMetricsController::class, 'index']);
            Route::get('/tenants', [PlatformTenantController::class, 'index']);
            Route::get('/tenants/{tenant}', [PlatformTenantController::class, 'show']);
            Route::post('/tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend']);
            Route::post('/tenants/{tenant}/reactivate', [PlatformTenantController::class, 'reactivate']);
        });
});
