<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly SequenceService $sequences,
    ) {}

    public function create(User $creator, array $data): Customer
    {
        return DB::transaction(function () use ($creator, $data) {
            $customer = $this->customers->create(array_merge($data, [
                'tenant_id' => $creator->tenant_id,
                'customer_number' => $this->sequences->next($creator->tenant_id, 'customer_number', 'CU'),
                'created_by_user_id' => $creator->id,
                'updated_by_user_id' => $creator->id,
                'customer_type' => $data['customer_type'] ?? Customer::TYPE_COMPANY,
                'status' => Customer::STATUS_ACTIVE,
            ]));

            $this->logActivity($customer, $creator, CustomerActivity::TYPE_CREATED, "Customer {$customer->customer_number} created.");

            return $customer;
        });
    }

    public function update(User $actor, Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($actor, $customer, $data) {
            $previousManagerId = $customer->account_manager_user_id;

            $customer = $this->customers->update($customer, array_merge($data, ['updated_by_user_id' => $actor->id]));

            if (array_key_exists('account_manager_user_id', $data) && $data['account_manager_user_id'] !== $previousManagerId) {
                $this->logActivity(
                    $customer, $actor, CustomerActivity::TYPE_ACCOUNT_MANAGER_CHANGED,
                    'Account manager changed.',
                    ['from_user_id' => $previousManagerId, 'to_user_id' => $data['account_manager_user_id']]
                );
            }

            return $customer;
        });
    }

    public function logManualActivity(User $actor, Customer $customer, string $type, string $description): CustomerActivity
    {
        if (! in_array($type, CustomerActivity::MANUAL_TYPES, true)) {
            throw new \InvalidArgumentException("'{$type}' is not a manually loggable activity type.");
        }

        return $this->logActivity($customer, $actor, $type, $description);
    }

    public function logActivity(Customer $customer, ?User $actor, string $type, string $description, ?array $metadata = null): CustomerActivity
    {
        return CustomerActivity::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
