<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface CustomerActivityRepositoryInterface
{
    public function timelineFor(Customer $customer, Request $request): LengthAwarePaginator;
}
