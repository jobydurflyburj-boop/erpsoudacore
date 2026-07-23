<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreSalaryComponentRequest;
use App\Http\Resources\SalaryComponentResource;
use App\Repositories\Contracts\SalaryComponentRepositoryInterface;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function __construct(private readonly SalaryComponentRepositoryInterface $components) {}

    public function index(Request $request) { return SalaryComponentResource::collection($this->components->paginate($request)); }

    public function store(StoreSalaryComponentRequest $request)
    {
        $component = $this->components->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new SalaryComponentResource($component), 201);
    }
}
