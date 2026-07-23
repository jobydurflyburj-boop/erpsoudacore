<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The one piece of business logic this sprint exists for: turning a won
 * Lead into a Customer, closing the "a won lead should become something"
 * gap named in docs/ROADMAP.md. Deliberately its own service rather than
 * a method bolted onto LeadService or CustomerService — conversion reads
 * and writes both entities and belongs to neither one alone.
 */
class LeadConversionService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
        private readonly CustomerRepositoryInterface $customers,
        private readonly SequenceService $sequences,
        private readonly CustomerService $customerService,
    ) {}

    public function convert(User $actor, Lead $lead, array $overrides = []): Customer
    {
        if ($lead->isConverted()) {
            throw new InvalidArgumentException("Lead {$lead->lead_number} has already been converted to customer {$lead->convertedCustomer?->customer_number}.");
        }

        $status = $lead->status()->first();

        if (! $status?->is_won) {
            throw new InvalidArgumentException('Only a lead whose status is marked as Won can be converted to a customer.');
        }

        return DB::transaction(function () use ($actor, $lead, $overrides) {
            $customer = $this->customers->create(array_merge([
                'tenant_id' => $lead->tenant_id,
                'customer_number' => $this->sequences->next($lead->tenant_id, 'customer_number', 'CU'),
                'customer_type' => Customer::TYPE_COMPANY,
                'company_name' => $lead->company_name,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'arabic_name' => $lead->arabic_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'whatsapp' => $lead->whatsapp,
                'country' => $lead->country,
                'city' => $lead->city,
                'account_manager_user_id' => $lead->assigned_to_user_id,
                'status' => Customer::STATUS_ACTIVE,
                'source_lead_id' => $lead->id,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ], $overrides));

            $this->leads->update($lead, [
                'converted_to_customer_id' => $customer->id,
                'converted_at' => now(),
                'updated_by_user_id' => $actor->id,
            ]);

            LeadActivity::create([
                'tenant_id' => $lead->tenant_id,
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'type' => LeadActivity::TYPE_CONVERTED,
                'description' => "Converted to customer {$customer->customer_number}.",
                'metadata' => ['customer_id' => $customer->id],
                'created_at' => now(),
            ]);

            $this->customerService->logActivity(
                $customer, $actor, CustomerActivity::TYPE_CONVERTED_FROM_LEAD,
                "Converted from lead {$lead->lead_number}.",
                ['lead_id' => $lead->id]
            );

            return $customer;
        });
    }
}
