<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'item_type',
        'unit',
        'unit_cost',
        'sale_price',
        'tax_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => CatalogItemType::class,
            'unit' => MeasurementUnit::class,
            'unit_cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'category_id');
    }
}
