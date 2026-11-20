<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSite extends Model
{
    protected $fillable = [
        'client_id',
        'site_name',
        'address',
        'district_city',
        'reference',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
