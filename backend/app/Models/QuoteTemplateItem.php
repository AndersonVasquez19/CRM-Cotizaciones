<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTemplateItem extends Model
{
    protected $fillable = [
        'quote_template_id',
        'catalog_item_id',
        'section_name',
        'description',
        'unit',
        'quantity',
        'unit_cost',
        'unit_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit' => MeasurementUnit::class,
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'quote_template_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
