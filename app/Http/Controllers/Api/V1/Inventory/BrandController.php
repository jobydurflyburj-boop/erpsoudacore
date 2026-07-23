<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreBrandRequest;
use App\Http\Requests\Inventory\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    public function index(Request $request)
    {
        return BrandResource::collection($this->brands->paginate($request));
    }

    public function store(StoreBrandRequest $request)
    {
        $brand = $this->brands->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new BrandResource($brand), 201);
    }

    public function show(Brand $brand)
    {
        return $this->ok(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        return $this->ok(new BrandResource($this->brands->update($brand, $request->validated())));
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return response()->json(['error' => 'conflict', 'message' => 'This brand is still used by products — reassign them first.', 'details' => (object) []], 409);
        }
        $this->brands->delete($brand);
        return response()->json(null, 204);
    }
}
