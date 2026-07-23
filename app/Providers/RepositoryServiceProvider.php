<?php

namespace App\Providers;

use App\Repositories\Contracts\BranchRepositoryInterface;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\CompanySettingRepositoryInterface;
use App\Repositories\Contracts\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeadActivityRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\LeadSourceRepositoryInterface;
use App\Repositories\Contracts\LeadStatusRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\OpportunityActivityRepositoryInterface;
use App\Repositories\Contracts\OpportunityRepositoryInterface;
use App\Repositories\Contracts\OpportunityStageRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use App\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Repositories\Contracts\ChartOfAccountRepositoryInterface;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use App\Repositories\Contracts\GoodsIssueRepositoryInterface;
use App\Repositories\Contracts\SupplierBillRepositoryInterface;
use App\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use App\Repositories\Contracts\DebitNoteRepositoryInterface;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Repositories\Contracts\CustomerPaymentRepositoryInterface;
use App\Repositories\Contracts\CreditNoteRepositoryInterface;
use App\Repositories\Contracts\SalesReturnRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\DesignationRepositoryInterface;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\SalaryComponentRepositoryInterface;
use App\Repositories\Contracts\OvertimeRecordRepositoryInterface;
use App\Repositories\Contracts\PayrollRunRepositoryInterface;
use App\Repositories\Contracts\PayslipRepositoryInterface;
use App\Repositories\Contracts\JobOpeningRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\JobApplicationRepositoryInterface;
use App\Repositories\Contracts\PerformanceReviewCycleRepositoryInterface;
use App\Repositories\Contracts\PerformanceReviewRepositoryInterface;
use App\Repositories\Contracts\CustomReportRepositoryInterface;
use App\Repositories\Contracts\ScheduledReportRepositoryInterface;
use App\Repositories\Contracts\AiSettingRepositoryInterface;
use App\Repositories\Contracts\AiPromptTemplateRepositoryInterface;
use App\Repositories\Contracts\AiActivityLogRepositoryInterface;
use App\Repositories\Contracts\AiSuggestionRepositoryInterface;
use App\Repositories\Eloquent\BranchRepository;
use App\Repositories\Eloquent\CompanyRepository;
use App\Repositories\Eloquent\CompanySettingRepository;
use App\Repositories\Eloquent\CustomerActivityRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\DepartmentRepository;
use App\Repositories\Eloquent\LeadActivityRepository;
use App\Repositories\Eloquent\LeadRepository;
use App\Repositories\Eloquent\LeadSourceRepository;
use App\Repositories\Eloquent\LeadStatusRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\Eloquent\OpportunityActivityRepository;
use App\Repositories\Eloquent\OpportunityRepository;
use App\Repositories\Eloquent\OpportunityStageRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Eloquent\PurchaseOrderRepository;
use App\Repositories\Eloquent\QuotationRepository;
use App\Repositories\Eloquent\SalesOrderRepository;
use App\Repositories\Eloquent\SalesInvoiceRepository;
use App\Repositories\Eloquent\ChartOfAccountRepository;
use App\Repositories\Eloquent\JournalEntryRepository;
use App\Repositories\Eloquent\ProductCategoryRepository;
use App\Repositories\Eloquent\UnitRepository;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\StockTransferRepository;
use App\Repositories\Eloquent\StockAdjustmentRepository;
use App\Repositories\Eloquent\GoodsReceiptRepository;
use App\Repositories\Eloquent\GoodsIssueRepository;
use App\Repositories\Eloquent\SupplierBillRepository;
use App\Repositories\Eloquent\SupplierPaymentRepository;
use App\Repositories\Eloquent\DebitNoteRepository;
use App\Repositories\Eloquent\PurchaseReturnRepository;
use App\Repositories\Eloquent\DeliveryNoteRepository;
use App\Repositories\Eloquent\CustomerPaymentRepository;
use App\Repositories\Eloquent\CreditNoteRepository;
use App\Repositories\Eloquent\SalesReturnRepository;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\TaskRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\DesignationRepository;
use App\Repositories\Eloquent\ShiftRepository;
use App\Repositories\Eloquent\HolidayRepository;
use App\Repositories\Eloquent\LeaveTypeRepository;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Repositories\Eloquent\AttendanceRepository;
use App\Repositories\Eloquent\LeaveRequestRepository;
use App\Repositories\Eloquent\SalaryComponentRepository;
use App\Repositories\Eloquent\OvertimeRecordRepository;
use App\Repositories\Eloquent\PayrollRunRepository;
use App\Repositories\Eloquent\PayslipRepository;
use App\Repositories\Eloquent\JobOpeningRepository;
use App\Repositories\Eloquent\CandidateRepository;
use App\Repositories\Eloquent\JobApplicationRepository;
use App\Repositories\Eloquent\PerformanceReviewCycleRepository;
use App\Repositories\Eloquent\PerformanceReviewRepository;
use App\Repositories\Eloquent\CustomReportRepository;
use App\Repositories\Eloquent\ScheduledReportRepository;
use App\Repositories\Eloquent\AiSettingRepository;
use App\Repositories\Eloquent\AiPromptTemplateRepository;
use App\Repositories\Eloquent\AiActivityLogRepository;
use App\Repositories\Eloquent\AiSuggestionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Every repository interface -> Eloquent implementation binding lives
 * here, and ONLY here — this is what makes swapping an implementation
 * (e.g. a caching decorator around UserRepository) a one-line change with
 * zero call-site edits anywhere in Services/Controllers.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        TenantRepositoryInterface::class => TenantRepository::class,
        CompanyRepositoryInterface::class => CompanyRepository::class,
        CompanySettingRepositoryInterface::class => CompanySettingRepository::class,
        BranchRepositoryInterface::class => BranchRepository::class,
        DepartmentRepositoryInterface::class => DepartmentRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        PermissionRepositoryInterface::class => PermissionRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        TaskRepositoryInterface::class => TaskRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
        LeadRepositoryInterface::class => LeadRepository::class,
        LeadSourceRepositoryInterface::class => LeadSourceRepository::class,
        LeadStatusRepositoryInterface::class => LeadStatusRepository::class,
        LeadActivityRepositoryInterface::class => LeadActivityRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        CustomerActivityRepositoryInterface::class => CustomerActivityRepository::class,
        OpportunityRepositoryInterface::class => OpportunityRepository::class,
        OpportunityStageRepositoryInterface::class => OpportunityStageRepository::class,
        OpportunityActivityRepositoryInterface::class => OpportunityActivityRepository::class,
        WarehouseRepositoryInterface::class => WarehouseRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        SupplierRepositoryInterface::class => SupplierRepository::class,
        PurchaseOrderRepositoryInterface::class => PurchaseOrderRepository::class,
        QuotationRepositoryInterface::class => QuotationRepository::class,
        SalesOrderRepositoryInterface::class => SalesOrderRepository::class,
        SalesInvoiceRepositoryInterface::class => SalesInvoiceRepository::class,
        ChartOfAccountRepositoryInterface::class => ChartOfAccountRepository::class,
        JournalEntryRepositoryInterface::class => JournalEntryRepository::class,
        ProductCategoryRepositoryInterface::class => ProductCategoryRepository::class,
        UnitRepositoryInterface::class => UnitRepository::class,
        BrandRepositoryInterface::class => BrandRepository::class,
        StockTransferRepositoryInterface::class => StockTransferRepository::class,
        StockAdjustmentRepositoryInterface::class => StockAdjustmentRepository::class,
        GoodsReceiptRepositoryInterface::class => GoodsReceiptRepository::class,
        GoodsIssueRepositoryInterface::class => GoodsIssueRepository::class,
        SupplierBillRepositoryInterface::class => SupplierBillRepository::class,
        SupplierPaymentRepositoryInterface::class => SupplierPaymentRepository::class,
        DebitNoteRepositoryInterface::class => DebitNoteRepository::class,
        PurchaseReturnRepositoryInterface::class => PurchaseReturnRepository::class,
        DeliveryNoteRepositoryInterface::class => DeliveryNoteRepository::class,
        CustomerPaymentRepositoryInterface::class => CustomerPaymentRepository::class,
        CreditNoteRepositoryInterface::class => CreditNoteRepository::class,
        SalesReturnRepositoryInterface::class => SalesReturnRepository::class,
        DesignationRepositoryInterface::class => DesignationRepository::class,
        ShiftRepositoryInterface::class => ShiftRepository::class,
        HolidayRepositoryInterface::class => HolidayRepository::class,
        LeaveTypeRepositoryInterface::class => LeaveTypeRepository::class,
        EmployeeRepositoryInterface::class => EmployeeRepository::class,
        AttendanceRepositoryInterface::class => AttendanceRepository::class,
        LeaveRequestRepositoryInterface::class => LeaveRequestRepository::class,
        SalaryComponentRepositoryInterface::class => SalaryComponentRepository::class,
        OvertimeRecordRepositoryInterface::class => OvertimeRecordRepository::class,
        PayrollRunRepositoryInterface::class => PayrollRunRepository::class,
        PayslipRepositoryInterface::class => PayslipRepository::class,
        JobOpeningRepositoryInterface::class => JobOpeningRepository::class,
        CandidateRepositoryInterface::class => CandidateRepository::class,
        JobApplicationRepositoryInterface::class => JobApplicationRepository::class,
        PerformanceReviewCycleRepositoryInterface::class => PerformanceReviewCycleRepository::class,
        PerformanceReviewRepositoryInterface::class => PerformanceReviewRepository::class,
        CustomReportRepositoryInterface::class => CustomReportRepository::class,
        ScheduledReportRepositoryInterface::class => ScheduledReportRepository::class,
        AiSettingRepositoryInterface::class => AiSettingRepository::class,
        AiPromptTemplateRepositoryInterface::class => AiPromptTemplateRepository::class,
        AiActivityLogRepositoryInterface::class => AiActivityLogRepository::class,
        AiSuggestionRepositoryInterface::class => AiSuggestionRepository::class,
    ];
}
