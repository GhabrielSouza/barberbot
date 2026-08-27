<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * List all products for a company.
     *
     * The tenant schema does not currently include a company_id column in
     * products, so the CRUD is scoped by the route context but not filtered
     * by a company foreign key.
     */
    public function index(Company $company): AnonymousResourceCollection
    {
        $products = Product::query()
            ->orderBy('name')
            ->paginate(15);

        return ProductResource::collection($products);
    }

    /**
     * Get active products only.
     */
    public function active(Company $company): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * Create a new product.
     */
    public function store(CreateProductRequest $request, Company $company): ProductResource
    {
        $product = Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock ?? 0,
            'category' => $request->category,
            'active' => $request->active ?? true,
        ]);

        return new ProductResource($product);
    }

    /**
     * Get product details.
     */
    public function show(Company $company, Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Update a product.
     */
    public function update(UpdateProductRequest $request, Company $company, Product $product): ProductResource
    {
        $product->update($request->only(['name', 'price', 'stock', 'category', 'active']));

        return new ProductResource($product->fresh());
    }

    /**
     * Toggle product active status.
     */
    public function toggleActive(Company $company, Product $product): JsonResponse
    {
        $product->update(['active' => !$product->active]);

        return response()->json([
            'success' => true,
            'message' => 'Product status updated',
            'active' => $product->active,
        ]);
    }

    /**
     * Delete product.
     */
    public function destroy(Company $company, Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
