<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductCategoryRequest;
use App\Http\Requests\Inventory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $categories) {}

    public function index(Request $request)
    {
        return ProductCategoryResource::collection($this->categories->paginate($request));
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $category = $this->categories->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new ProductCategoryResource($category), 201);
    }

    public function show(ProductCategory $productCategory)
    {
        return $this->ok(new ProductCategoryResource($productCategory));
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory)
    {
        return $this->ok(new ProductCategoryResource($this->categories->update($productCategory, $request->validated())));
    }

    public function destroy(ProductCategory $productCategory)
    {
        if ($productCategory->products()->exists()) {
            return response()->json(['error' => 'conflict', 'message' => 'This category still has products assigned — reassign them first.', 'details' => (object) []], 409);
        }
        $this->categories->delete($productCategory);
        return response()->json(null, 204);
    }
}
