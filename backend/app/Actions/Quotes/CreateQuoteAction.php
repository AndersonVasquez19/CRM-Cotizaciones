<?php

namespace App\Actions\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateQuoteAction
{
    public function __construct(
        private readonly CalculateQuoteTotalsAction $calculateTotals,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): Quote
    {
        return DB::transaction(function () use ($data, $user): Quote {
            $totals = $this->calculateTotals->execute(
                $data['items'] ?? [],
                (float) ($data['discount_amount'] ?? 0),
            );

            $quoteNumber = $this->generateQuoteNumber();

            $quote = Quote::create([
                'quote_number' => $quoteNumber,
                'public_token' => (string) Str::uuid(),
                'version' => 1,
                'parent_quote_id' => null,
                'lead_id' => $data['lead_id'] ?? null,
                'client_id' => $data['client_id'],
                'company_profile_id' => $data['company_profile_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'site_id' => $data['site_id'] ?? null,
                'created_by' => $user->id,
                'status' => QuoteStatus::Borrador,
                'currency' => $data['currency'] ?? 'PEN',
                'exchange_rate' => $data['exchange_rate'] ?? 1.0000,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(15)->toDateString(),
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'payment_terms' => $data['payment_terms'] ?? '50% adelanto, 50% contra entrega',
                'delivery_time' => $data['delivery_time'] ?? '7 días hábiles',
                'warranty_terms' => $data['warranty_terms'] ?? '12 meses por defectos de fabricación e instalación',
                'technical_notes' => $data['technical_notes'] ?? null,
            ]);

            foreach ($totals['items'] as $item) {
                unset($item['id']);
                $quote->items()->create($item);
            }

            $quote->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => QuoteStatus::Borrador->value,
                'comment' => 'Cotización creada',
                'created_at' => now(),
            ]);

            AuditService::log('quote.created', $quote, $user, [
                'quote_number' => $quote->quote_number,
                'version' => $quote->version,
                'total' => $quote->total,
            ]);

            return $quote->load(['client', 'contact', 'site', 'lead', 'creator', 'items', 'statusLogs.user']);
        });
    }

    private function generateQuoteNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "COT-{$year}-";

        $lastNumber = Quote::withTrashed()
            ->where('quote_number', 'like', "{$prefix}%")
            ->orderByDesc('quote_number')
            ->value('quote_number');

        $nextSequence = 1;
        if ($lastNumber !== null) {
            $parts = explode('-', $lastNumber);
            $nextSequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSequence);
    }
}
