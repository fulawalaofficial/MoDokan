<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    use ResolvesShop;

    public function index(Request $request)
    {
        $shopId = $this->shopId($request);

        $perPage = min(
            max((int) $request->get('per_page', 100), 1),
            100
        );

        $categories = ProductCategory::query()
            ->where('shop_id', $shopId)
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = trim((string) $request->search);

                    $query->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );
                }
            )
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $query->where(
                        'status',
                        $request->status
                    );
                }
            )
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $shopId = $this->shopId($request);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'product_categories',
                    'name'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'shop_id',
                            $shopId
                        )
                ),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'name.unique' =>
                'A product category with this name already exists in your shop.',
        ]);

        $data['shop_id'] = $shopId;
        $data['status'] =
            $data['status'] ?? 'active';

        $category =
            ProductCategory::create($data);

        return response()->json(
            $category,
            201
        );
    }

    public function show(
        Request $request,
        ProductCategory $productCategory
    ) {
        $this->ensureBelongsToShop(
            $request,
            $productCategory
        );

        return response()->json(
            $productCategory
        );
    }

    public function update(
        Request $request,
        ProductCategory $productCategory
    ) {
        $shopId = $this->shopId($request);

        $this->ensureBelongsToShop(
            $request,
            $productCategory
        );

        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'product_categories',
                    'name'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'shop_id',
                                $shopId
                            )
                    )
                    ->ignore(
                        $productCategory->id
                    ),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'name.unique' =>
                'A product category with this name already exists in your shop.',
        ]);

        $productCategory->update($data);

        return response()->json(
            $productCategory->fresh()
        );
    }

    public function destroy(
        Request $request,
        ProductCategory $productCategory
    ) {
        $shopId = $this->shopId($request);

        $this->ensureBelongsToShop(
            $request,
            $productCategory
        );

        $hasProducts = Product::query()
            ->where('shop_id', $shopId)
            ->where(
                'product_category_id',
                $productCategory->id
            )
            ->exists();

        if ($hasProducts) {
            return response()->json([
                'message' =>
                    'This category cannot be deleted because products are already using it.',
            ], 422);
        }

        $productCategory->delete();

        return response()->json([
            'message' => 'Category deleted.',
        ]);
    }

    private function ensureBelongsToShop(
        Request $request,
        ProductCategory $productCategory
    ): void {
        abort_unless(
            (int) $productCategory->shop_id ===
                (int) $this->shopId($request),
            403,
            'This category does not belong to your shop.'
        );
    }
}
