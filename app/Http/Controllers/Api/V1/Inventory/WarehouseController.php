<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreWarehouseRequest;
use App\Http\Requests\Inventory\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouses) {}

    public function index(Request $request)
    {
        return WarehouseResource::collection($this->warehouses->paginate($request));
    }

    public function store(StoreWarehouseRequest $request)
    {
        $warehouse = $this->warehouses->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new WarehouseResource($warehouse), 201);
    }

    public function show(Warehouse $warehouse)
    {
        return $this->ok(new WarehouseResource($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        return $this->ok(new WarehouseResource($this->warehouses->update($warehouse, $request->validated())));
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return response()->json(['error' => 'conflict', 'message' => 'The default warehouse cannot be deleted — set another warehouse as default first.', 'details' => (object) []], 409);
        }
        if ($warehouse->stockLevels()->where('quantity', '>', 0)->exists()) {
            return response()->json(['error' => 'conflict', 'message' => 'This warehouse still holds stock — transfer it out first.', 'details' => (object) []], 409);
        }
        $this->warehouses->delete($warehouse);
        return response()->json(null, 204);
    }
}
