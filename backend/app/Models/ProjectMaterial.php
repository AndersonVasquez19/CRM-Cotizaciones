<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id',
        'catalog_item_id',
        'description',
        'unit',
        'planned_qty',
        'used_qty',
        'unit_cost',
        'serial_numbers',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:2',
            'used_qty' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
