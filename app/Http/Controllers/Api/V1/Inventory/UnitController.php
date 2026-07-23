<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreUnitRequest;
use App\Http\Requests\Inventory\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private readonly UnitRepositoryInterface $units) {}

    public function index(Request $request)
    {
        return UnitResource::collection($this->units->paginate($request));
    }

    public function store(StoreUnitRequest $request)
    {
        $unit = $this->units->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new UnitResource($unit), 201);
    }

    public function show(Unit $unit)
    {
        return $this->ok(new UnitResource($unit));
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        return $this->ok(new UnitResource($this->units->update($unit, $request->validated())));
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->exists()) {
            return response()->json(['error' => 'conflict', 'message' => 'This unit is still used by products — reassign them first.', 'details' => (object) []], 409);
        }
        $this->units->delete($unit);
        return response()->json(null, 204);
    }
}
