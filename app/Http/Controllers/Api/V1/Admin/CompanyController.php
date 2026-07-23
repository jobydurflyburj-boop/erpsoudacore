<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyRepositoryInterface $companies) {}

    public function index(Request $request)
    {
        return CompanyResource::collection($this->companies->paginate($request));
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = $this->companies->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
            'is_default' => false,
        ]));

        return $this->ok(new CompanyResource($company), 201);
    }

    public function show(Company $company)
    {
        return $this->ok(new CompanyResource($company));
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        return $this->ok(new CompanyResource($this->companies->update($company, $request->validated())));
    }

    /** The Company Profile screen operates on the tenant's default company — a convenience alias over the generic multi-company CRUD above, since most tenants only ever have one. */
    public function profile(Request $request)
    {
        $company = $request->user()->tenant->defaultCompany();

        abort_if(! $company, 404, 'No company profile has been set up for this account yet.');

        return $this->ok(new CompanyResource($company));
    }

    public function updateProfile(UpdateCompanyRequest $request)
    {
        $company = $request->user()->tenant->defaultCompany();

        abort_if(! $company, 404, 'No company profile has been set up for this account yet.');

        return $this->ok(new CompanyResource($this->companies->update($company, $request->validated())));
    }

    public function uploadLogo(Request $request, Company $company)
    {
        $request->validate(['logo' => ['required', 'image', 'max:2048']]);

        $path = $request->file('logo')->store("tenants/{$company->tenant_id}/logos", 'public');

        $company = $this->companies->update($company, ['logo_path' => $path]);

        return $this->ok(new CompanyResource($company));
    }
}
