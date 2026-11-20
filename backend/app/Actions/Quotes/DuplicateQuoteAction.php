<?php

namespace App\Actions\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateQuoteAction
{
    public function execute(Quote $sourceQuote, User $user): Quote
    {
        return DB::transaction(function () use ($sourceQuote, $user): Quote {
            $maxVersion = (int) Quote::withTrashed()
                ->where('quote_number', $sourceQuote->quote_number)
                ->max('version');

            $newQuote = $sourceQuote->replicate([
                'public_token',
                'version',
                'parent_quote_id',
                'status',
                'created_by',
                'approved_by',
                'approved_at',
                'approval_requested_at',
                'approval_notes',
                'issue_date',
                'valid_until',
            ]);

            $newQuote->public_token = (string) Str::uuid();
            $newQuote->version = $maxVersion + 1;
            $newQuote->parent_quote_id = $sourceQuote->id;
            $newQuote->status = QuoteStatus::Borrador;
            $newQuote->created_by = $user->id;
            $newQuote->issue_date = now()->toDateString();
            $newQuote->valid_until = now()->addDays(15)->toDateString();
            $newQuote->save();

            foreach ($sourceQuote->items as $item) {
                $newQuote->items()->create([
                    'catalog_item_id' => $item->catalog_item_id,
                    'section_name' => $item->section_name,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent,
                    'line_total' => $item->line_total,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $newQuote->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => QuoteStatus::Borrador->value,
                'comment' => "Nueva versión v{$newQuote->version} duplicada desde v{$sourceQuote->version}",
                'created_at' => now(),
            ]);

            AuditService::log('quote.duplicated', $newQuote, $user, [
                'source_id' => $sourceQuote->id,
                'version' => $newQuote->version,
            ]);

            return $newQuote->load(['client', 'contact', 'site', 'lead', 'creator', 'items', 'statusLogs.user']);
        });
    }
}
