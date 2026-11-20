<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeasurementUnit;
use App\Http\Controllers\Controller;
use App\Models\QuoteTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuoteTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QuoteTemplate::with('items')->withCount('items');

        if ($serviceLine = $request->query('service_line')) {
            $query->where('service_line', $serviceLine);
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'service_line' => ['required', Rule::in(['cctv', 'cableado_estructurado', 'redes_wifi', 'mantenimiento', 'integral'])],
            'description' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['nullable', 'exists:catalog_items,id'],
            'items.*.section_name' => ['required', 'string', 'max:120'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.unit' => ['required', Rule::in(MeasurementUnit::values())],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $template = DB::transaction(function () use ($validated, $request): QuoteTemplate {
            $tpl = QuoteTemplate::create([
                'name' => $validated['name'],
                'service_line' => $validated['service_line'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $idx => $item) {
                $tpl->items()->create([
                    ...$item,
                    'sort_order' => $idx,
                ]);
            }

            return $tpl->load('items');
        });

        return response()->json(['data' => $template], 201);
    }

    public function show(QuoteTemplate $quoteTemplate): JsonResponse
    {
        return response()->json(['data' => $quoteTemplate->load('items')]);
    }

    public function destroy(QuoteTemplate $quoteTemplate): JsonResponse
    {
        $quoteTemplate->delete();

        return response()->json(['message' => 'Plantilla BOM eliminada.']);
    }
}
