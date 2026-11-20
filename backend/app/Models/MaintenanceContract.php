<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceContract extends Model
{
    protected $fillable = [
        'contract_number',
        'client_id',
        'site_id',
        'project_id',
        'title',
        'frequency',
        'monthly_fee',
        'currency',
        'sla_hours',
        'start_date',
        'end_date',
        'next_visit_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'sla_hours' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_visit_date' => 'date',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SlaTicket::class, 'contract_id');
    }
}
