<?php
namespace App\Repositories\Eloquent;
use App\Models\SupplierPayment;
use App\Repositories\Contracts\SupplierPaymentRepositoryInterface;
class SupplierPaymentRepository extends BaseRepository implements SupplierPaymentRepositoryInterface
{
    protected string $modelClass = SupplierPayment::class;
    protected array $allowedFilters = ['supplier_id', 'payment_method'];
    protected array $allowedSorts = ['created_at', 'payment_date', 'amount'];
    protected array $searchableFields = ['payment_number', 'reference'];
    protected string $defaultSort = '-created_at';
}
