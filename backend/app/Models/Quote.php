<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quote_number',
        'public_token',
        'version',
        'parent_quote_id',
        'lead_id',
        'client_id',
        'company_profile_id',
        'contact_id',
        'site_id',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_requested_at',
        'approval_notes',
        'status',
        'currency',
        'exchange_rate',
        'issue_date',
        'valid_until',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'payment_terms',
        'delivery_time',
        'warranty_terms',
        'technical_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'version' => 'integer',
            'exchange_rate' => 'decimal:4',
            'issue_date' => 'date',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'approval_requested_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'parent_quote_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(InvoiceMilestone::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ClientContact::class, 'contact_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(QuoteStatusLog::class)->latest('id');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(QuoteAcceptance::class)->latest('id');
    }

    public function latestAcceptance(): HasOne
    {
        return $this->hasOne(QuoteAcceptance::class)->latestOfMany('id');
    }
}
