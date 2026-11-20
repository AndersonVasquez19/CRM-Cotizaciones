<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class UpdateQuoteAction
{
    public function __construct(
        private readonly CalculateQuoteTotalsAction $calculateTotals,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data): Quote {
            $totals = $this->calculateTotals->execute(
                $data['items'] ?? [],
                (float) ($data['discount_amount'] ?? $quote->discount_amount),
            );

            $quote->update([
                'lead_id' => array_key_exists('lead_id', $data) ? $data['lead_id'] : $quote->lead_id,
                'client_id' => $data['client_id'] ?? $quote->client_id,
                'company_profile_id' => array_key_exists('company_profile_id', $data) ? $data['company_profile_id'] : $quote->company_profile_id,
                'contact_id' => array_key_exists('contact_id', $data) ? $data['contact_id'] : $quote->contact_id,
                'site_id' => array_key_exists('site_id', $data) ? $data['site_id'] : $quote->site_id,
                'currency' => $data['currency'] ?? $quote->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $quote->exchange_rate,
                'issue_date' => $data['issue_date'] ?? $quote->issue_date,
                'valid_until' => $data['valid_until'] ?? $quote->valid_until,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'payment_terms' => array_key_exists('payment_terms', $data) ? $data['payment_terms'] : $quote->payment_terms,
                'delivery_time' => array_key_exists('delivery_time', $data) ? $data['delivery_time'] : $quote->delivery_time,
                'warranty_terms' => array_key_exists('warranty_terms', $data) ? $data['warranty_terms'] : $quote->warranty_terms,
                'technical_notes' => array_key_exists('technical_notes', $data) ? $data['technical_notes'] : $quote->technical_notes,
            ]);

            $keptIds = [];
            foreach ($totals['items'] as $itemData) {
                $itemId = $itemData['id'] ?? null;
                unset($itemData['id']);

                if ($itemId) {
                    $existing = $quote->items()->where('id', $itemId)->first();
                    if ($existing) {
                        $existing->update($itemData);
                        $keptIds[] = $existing->id;
                        continue;
                    }
                }

                $created = $quote->items()->create($itemData);
                $keptIds[] = $created->id;
            }

            $quote->items()->whereNotIn('id', $keptIds)->delete();

            return $quote->fresh(['client', 'contact', 'site', 'lead', 'creator', 'items', 'statusLogs.user']);
        });
    }
}
