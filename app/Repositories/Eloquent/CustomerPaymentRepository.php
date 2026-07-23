<?php
namespace App\Repositories\Eloquent;
use App\Models\CustomerPayment;
use App\Repositories\Contracts\CustomerPaymentRepositoryInterface;
class CustomerPaymentRepository extends BaseRepository implements CustomerPaymentRepositoryInterface
{
    protected string $modelClass = CustomerPayment::class;
    protected array $allowedFilters = ['customer_id', 'payment_method'];
    protected array $allowedSorts = ['created_at', 'payment_date', 'amount'];
    protected array $searchableFields = ['payment_number', 'reference'];
    protected string $defaultSort = '-created_at';
}
