<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ResolvesShop;

    public function index(Request $request)
    {
        $shopId = $this->shopId($request);

        $perPage = min(
            max((int) $request->get('per_page', 30), 1),
            100
        );

        return Product::with([
            'category',
            'supplier',
        ])
            ->where('shop_id', $shopId)
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = trim(
                        (string) $request->search
                    );

                    $query->where(
                        function ($q) use ($search) {
                            $q->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'barcode',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $request->boolean('low_stock'),
                fn ($query) =>
                    $query->whereColumn(
                        'quantity',
                        '<=',
                        'low_stock_alert'
                    )
            )
            ->latest()
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $shopId = $this->shopId($request);

        $data = $this->validated(
            $request,
            false,
            $shopId
        );

        $data['shop_id'] = $shopId;
        $data['opening_stock'] =
            $data['quantity'] ?? 0;

        $product = Product::create($data);

        return response()->json(
            $product->load([
                'category',
                'supplier',
            ]),
            201
        );
    }

    public function show(
        Request $request,
        Product $product
    ) {
        $this->ensureBelongsToShop(
            $request,
            $product
        );

        return response()->json(
            $product->load([
                'category',
                'supplier',
                'stockHistories',
            ])
        );
    }

    public function update(
        Request $request,
        Product $product
    ) {
        $this->ensureBelongsToShop(
            $request,
            $product
        );

        $shopId = $this->shopId($request);

        $data = $this->validated(
            $request,
            true,
            $shopId
        );

        $product->update($data);

        return response()->json(
            $product->fresh([
                'category',
                'supplier',
            ])
        );
    }

    public function destroy(
        Request $request,
        Product $product
    ) {
        $this->ensureBelongsToShop(
            $request,
            $product
        );

        $product->delete();

        return response()->json([
            'message' => 'Product deleted.',
        ]);
    }

    private function ensureBelongsToShop(
        Request $request,
        Product $product
    ): void {
        abort_unless(
            (int) $product->shop_id ===
                (int) $this->shopId($request),
            403,
            'This product does not belong to your shop.'
        );
    }

    private function validated(
        Request $request,
        bool $update,
        int $shopId
    ): array {
        $requiredOrSometimes =
            $update ? 'sometimes' : 'required';

        return $request->validate([
            'product_category_id' => [
                $requiredOrSometimes,
                'integer',
                Rule::exists(
                    'product_categories',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'shop_id',
                            $shopId
                        )
                ),
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'suppliers',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'shop_id',
                            $shopId
                        )
                ),
            ],

            'name' => [
                $requiredOrSometimes,
                'string',
                'max:255',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:100',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
            ],

            'image' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'purchase_price' => [
                $requiredOrSometimes,
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                $requiredOrSometimes,
                'numeric',
                'min:0',
            ],

            'quantity' => [
                $requiredOrSometimes,
                'numeric',
                'min:0',
            ],

            'unit_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'low_stock_alert' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'status' => [
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'product_category_id.required' =>
                'Please select a product category.',

            'product_category_id.exists' =>
                'The selected product category is invalid or does not belong to your shop.',

            'supplier_id.exists' =>
                'The selected supplier is invalid or does not belong to your shop.',
        ]);
    }
}
