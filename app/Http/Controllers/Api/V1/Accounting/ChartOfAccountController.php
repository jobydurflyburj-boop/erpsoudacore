<?php
namespace App\Http\Controllers\Api\V1\Accounting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreChartOfAccountRequest;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Repositories\Contracts\ChartOfAccountRepositoryInterface;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function __construct(private readonly ChartOfAccountRepositoryInterface $accounts) {}

    public function index(Request $request)
    {
        return ChartOfAccountResource::collection($this->accounts->paginate($request));
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        $account = $this->accounts->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id, 'is_active' => true]));
        return $this->ok(new ChartOfAccountResource($account), 201);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        $data = $request->validate([
            'name_en' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        return $this->ok(new ChartOfAccountResource($this->accounts->update($chartOfAccount, $data)));
    }
}
