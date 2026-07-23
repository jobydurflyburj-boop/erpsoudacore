<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    protected string $modelClass = Customer::class;

    protected array $allowedFilters = ['status', 'customer_type', 'account_manager_user_id', 'country', 'city'];

    protected array $allowedSorts = ['created_at', 'customer_number', 'credit_limit'];

    protected array $searchableFields = [
        'customer_number', 'company_name', 'first_name', 'last_name',
        'arabic_name', 'email', 'phone', 'whatsapp', 'vat_number',
    ];

    protected string $defaultSort = '-created_at';
}
