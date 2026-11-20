<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAcceptance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'quote_id',
        'decision',
        'signer_name',
        'signer_document',
        'signer_email',
        'signature_data',
        'comment',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
