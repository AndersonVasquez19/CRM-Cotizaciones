<?php

namespace App\Http\Controllers\Api\Public;

use App\Actions\Quotes\SubmitPublicQuoteDecisionAction;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\QuotePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ClientQuotePortalController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $quote = Quote::where('public_token', $token)
            ->with([
                'client:id,business_name,document_type,document_number,fiscal_address',
                'contact:id,full_name,position,email',
                'site:id,site_name,address,district_city',
                'creator:id,name,email,phone',
                'items' => fn ($q) => $q->select([
                    'id',
                    'quote_id',
                    'section_name',
                    'description',
                    'unit',
                    'quantity',
                    'unit_price',
                    'discount_percent',
                    'line_total',
                    'sort_order',
                ]),
                'latestAcceptance',
            ])
            ->firstOrFail();

        return response()->json(['data' => $quote]);
    }

    public function pdf(string $token, QuotePdfService $pdfService): Response
    {
        $quote = Quote::where('public_token', $token)->firstOrFail();

        return $pdfService->download($quote);
    }

    public function decision(Request $request, string $token, SubmitPublicQuoteDecisionAction $action): JsonResponse
    {
        $quote = Quote::where('public_token', $token)->firstOrFail();

        if (in_array($quote->status, [QuoteStatus::Aprobada, QuoteStatus::Rechazada], true)) {
            return response()->json([
                'message' => 'Esta cotización ya cuenta con una decisión registrada.',
            ], 422);
        }

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['aprobada', 'rechazada'])],
            'signer_name' => ['required', 'string', 'max:150'],
            'signer_document' => ['nullable', 'string', 'max:30'],
            'signer_email' => ['nullable', 'email', 'max:150'],
            'signature_data' => ['nullable', 'string'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $acceptance = $action->execute(
            quote: $quote,
            data: $validated,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'data' => $acceptance,
            'quote_status' => $quote->fresh()->status->value,
        ], 201);
    }
}
