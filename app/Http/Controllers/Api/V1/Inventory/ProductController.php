<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->products->paginate($request);
        $paginated->getCollection()->load('stockLevels', 'categoryRef', 'unitRef', 'brand');
        return ProductResource::collection($paginated);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->products->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new ProductResource($product), 201);
    }

    public function show(Product $product)
    {
        return $this->ok(new ProductResource($product->load('stockLevels.warehouse', 'categoryRef', 'unitRef', 'brand')));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        return $this->ok(new ProductResource($this->products->update($product, $request->validated())));
    }

    public function destroy(Product $product)
    {
        $this->products->delete($product);
        return response()->json(null, 204);
    }

    /** Real Barcode Support: look a product up by its scanned code, e.g. for a POS-style workflow. */
    public function findByBarcode(Request $request)
    {
        $request->validate(['barcode' => ['required', 'string']]);

        $product = $this->inventory->findByBarcode($request->user()->tenant_id, $request->string('barcode'));

        abort_if(! $product, 404, 'No product found for that barcode.');

        return $this->ok(new ProductResource($product->load('stockLevels', 'categoryRef', 'unitRef', 'brand')));
    }
}
