<?php

namespace App\Models;

use App\Enums\LeadStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'client_id',
        'site_id',
        'assigned_user_id',
        'technician_id',
        'title',
        'service_line',
        'stage',
        'source',
        'estimated_amount',
        'expected_close_date',
        'lost_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => LeadStage::class,
            'estimated_amount' => 'decimal:2',
            'expected_close_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'site_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(SiteSurvey::class)->latest('id');
    }
}
