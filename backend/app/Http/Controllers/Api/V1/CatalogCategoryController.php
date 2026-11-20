<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CatalogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = CatalogCategory::withCount('items')->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:catalog_categories,slug'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $category = CatalogCategory::create($validated);

        return response()->json(['data' => $category], 201);
    }

    public function show(CatalogCategory $catalogCategory): JsonResponse
    {
        return response()->json(['data' => $catalogCategory->loadCount('items')]);
    }

    public function update(Request $request, CatalogCategory $catalogCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:140', Rule::unique('catalog_categories', 'slug')->ignore($catalogCategory->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['name']) && ! isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $catalogCategory->update($validated);

        return response()->json(['data' => $catalogCategory]);
    }

    public function destroy(CatalogCategory $catalogCategory): JsonResponse
    {
        if ($catalogCategory->items()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría que tiene ítems asociados.',
            ], 422);
        }

        $catalogCategory->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente.']);
    }
}
