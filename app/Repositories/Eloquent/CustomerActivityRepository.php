<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Repositories\Contracts\CustomerActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CustomerActivityRepository implements CustomerActivityRepositoryInterface
{
    public function timelineFor(Customer $customer, Request $request): LengthAwarePaginator
    {
        return CustomerActivity::where('customer_id', $customer->id)
            ->with('user')
            ->latest('created_at')
            ->paginate((int) $request->integer('page_size', 25));
    }
}
