<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SiteSurveyPhoto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'site_survey_id',
        'file_path',
        'caption',
        'created_at',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SiteSurvey::class, 'site_survey_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
