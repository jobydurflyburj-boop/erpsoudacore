<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreShiftRequest;
use App\Http\Requests\Hr\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftRepositoryInterface $shifts) {}

    public function index(Request $request) { return ShiftResource::collection($this->shifts->paginate($request)); }

    public function store(StoreShiftRequest $request)
    {
        $shift = $this->shifts->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new ShiftResource($shift), 201);
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        return $this->ok(new ShiftResource($this->shifts->update($shift, $request->validated())));
    }
}
