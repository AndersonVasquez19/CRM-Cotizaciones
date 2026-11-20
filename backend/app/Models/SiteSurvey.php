<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteSurvey extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'cable_meters',
        'conduit_type',
        'max_height_meters',
        'power_requirements',
        'observations',
        'surveyed_at',
    ];

    protected function casts(): array
    {
        return [
            'cable_meters' => 'decimal:2',
            'max_height_meters' => 'decimal:2',
            'surveyed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SiteSurveyPhoto::class);
    }
}
