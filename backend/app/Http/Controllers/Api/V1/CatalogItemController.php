<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CatalogItemType;
use App\Enums\MeasurementUnit;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CatalogItem::with('category:id,name,slug');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($itemType = $request->query('item_type')) {
            $query->where('item_type', $itemType);
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min((int) $request->query('per_page', 30), 100);

        return response()->json($query->orderBy('name')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:catalog_categories,id'],
            'sku' => ['required', 'string', 'max:60', 'unique:catalog_items,sku'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'item_type' => ['required', Rule::in(CatalogItemType::values())],
            'unit' => ['required', Rule::in(MeasurementUnit::values())],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $item = CatalogItem::create($validated);

        return response()->json(['data' => $item->load('category:id,name,slug')], 201);
    }

    public function show(CatalogItem $catalogItem): JsonResponse
    {
        return response()->json(['data' => $catalogItem->load('category:id,name,slug')]);
    }

    public function update(Request $request, CatalogItem $catalogItem): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:catalog_categories,id'],
            'sku' => ['sometimes', 'string', 'max:60', Rule::unique('catalog_items', 'sku')->ignore($catalogItem->id)],
            'name' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'item_type' => ['sometimes', Rule::in(CatalogItemType::values())],
            'unit' => ['sometimes', Rule::in(MeasurementUnit::values())],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $catalogItem->update($validated);

        return response()->json(['data' => $catalogItem->fresh('category:id,name,slug')]);
    }

    public function destroy(CatalogItem $catalogItem): JsonResponse
    {
        $catalogItem->delete();

        return response()->json(['message' => 'Ítem eliminado correctamente.']);
    }
}
