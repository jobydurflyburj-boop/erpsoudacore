<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanySettingsRequest;
use App\Repositories\Contracts\CompanySettingRepositoryInterface;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    public function __construct(private readonly CompanySettingRepositoryInterface $settings) {}

    public function index(Request $request)
    {
        $company = $request->user()->tenant->defaultCompany();

        abort_if(! $company, 404, 'No company profile has been set up for this account yet.');

        return $this->ok($this->settings->allFor($company));
    }

    public function update(UpdateCompanySettingsRequest $request)
    {
        $company = $request->user()->tenant->defaultCompany();

        abort_if(! $company, 404, 'No company profile has been set up for this account yet.');

        foreach ($request->validated('settings') as $key => $value) {
            $this->settings->set($company, $key, $value);
        }

        return $this->ok($this->settings->allFor($company));
    }
}
