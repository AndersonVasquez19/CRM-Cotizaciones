<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Quotes\ChangeQuoteStatusAction;
use App\Actions\Quotes\CreateQuoteAction;
use App\Actions\Quotes\DuplicateQuoteAction;
use App\Actions\Quotes\UpdateQuoteAction;
use App\Enums\MeasurementUnit;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Mail\QuoteSentMail;
use App\Models\Quote;
use App\Services\AuditService;
use App\Services\QuotePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Quote::with([
            'client:id,business_name,document_number',
            'contact:id,full_name',
            'site:id,site_name',
            'lead:id,code,title',
            'creator:id,name',
            'approver:id,name',
        ])->withCount('items');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($cq) => $cq->where('business_name', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function store(Request $request, CreateQuoteAction $action): JsonResponse
    {
        abort_unless($request->user()->can('create', Quote::class), 403, 'No tienes permiso para crear cotizaciones.');

        $validated = $this->validateQuotePayload($request);
        $quote = $action->execute($validated, $request->user());

        return response()->json(['data' => $quote], 201);
    }

    public function show(Request $request, Quote $quote): JsonResponse
    {
        $quote->load([
            'client.contacts',
            'client.sites',
            'contact',
            'site',
            'lead',
            'creator:id,name',
            'approver:id,name',
            'items',
            'statusLogs.user:id,name',
            'latestAcceptance',
        ]);

        if (! $request->user()->can('viewInternalCosts', $quote)) {
            $quote->items->each->makeHidden(['unit_cost']);
        }

        return response()->json(['data' => $quote]);
    }

    public function update(Request $request, Quote $quote, UpdateQuoteAction $action): JsonResponse
    {
        abort_unless($request->user()->can('update', $quote), 403, 'No tienes permiso para editar esta cotización.');

        $validated = $this->validateQuotePayload($request, isUpdate: true);
        $updated = $action->execute($quote, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Quote $quote): JsonResponse
    {
        abort_unless($request->user()->can('delete', $quote), 403, 'No tienes permiso para eliminar esta cotización.');

        $quote->delete();

        return response()->json(['message' => 'Cotización eliminada correctamente.']);
    }

    public function duplicate(Request $request, Quote $quote, DuplicateQuoteAction $action): JsonResponse
    {
        abort_unless($request->user()->can('create', Quote::class), 403, 'No tienes permiso para duplicar cotizaciones.');

        $duplicated = $action->execute($quote, $request->user());

        return response()->json(['data' => $duplicated], 201);
    }

    public function changeStatus(Request $request, Quote $quote, ChangeQuoteStatusAction $action): JsonResponse
    {
        abort_unless($request->user()->can('update', $quote) || $request->user()->can('approveMargin', $quote), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(QuoteStatus::values())],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $action->execute(
            quote: $quote,
            newStatus: QuoteStatus::from($validated['status']),
            user: $request->user(),
            comment: $validated['comment'] ?? null,
        );

        return response()->json(['data' => $updated]);
    }

    public function approveMargin(Request $request, Quote $quote, ChangeQuoteStatusAction $action): JsonResponse
    {
        abort_unless($request->user()->can('approveMargin', $quote), 403, 'Solo supervisores o administradores pueden aprobar márgenes.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'target_status' => ['sometimes', Rule::in([QuoteStatus::Enviada->value, QuoteStatus::Borrador->value])],
        ]);

        $updated = $action->approveMargin(
            quote: $quote,
            approver: $request->user(),
            notes: $validated['notes'] ?? null,
            targetStatus: QuoteStatus::from($validated['target_status'] ?? QuoteStatus::Enviada->value),
        );

        return response()->json(['data' => $updated]);
    }

    public function sendEmail(Request $request, Quote $quote, ChangeQuoteStatusAction $statusAction): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        $quote->loadMissing(['client', 'contact', 'creator']);
        $recipient = $validated['email'] ?? $quote->contact?->email ?? $quote->client?->email;

        if (! $recipient) {
            return response()->json([
                'message' => 'El cliente/contacto no tiene un correo electrónico registrado.',
            ], 422);
        }

        if ($quote->status === QuoteStatus::Borrador) {
            $quote = $statusAction->execute($quote, QuoteStatus::Enviada, $request->user(), "Enviada por correo a {$recipient}");
            if ($quote->status === QuoteStatus::PendienteAprobacion) {
                return response()->json([
                    'message' => 'La cotización requiere aprobación gerencial de margen antes de enviarse al cliente.',
                    'data' => $quote,
                ], 422);
            }
        }

        Mail::to($recipient)->send(new QuoteSentMail($quote));

        AuditService::log('quote.emailed', $quote, $request->user(), ['recipient' => $recipient]);

        return response()->json([
            'message' => "Cotización enviada correctamente a {$recipient}.",
            'data' => $quote,
        ]);
    }

    public function pdf(Quote $quote, QuotePdfService $pdfService): Response
    {
        return $pdfService->download($quote);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuotePayload(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'client_id' => [$required, 'exists:clients,id'],
            'company_profile_id' => ['nullable', 'exists:company_profiles,id'],
            'contact_id' => ['nullable', 'exists:client_contacts,id'],
            'site_id' => ['nullable', 'exists:client_sites,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'currency' => ['sometimes', Rule::in(['PEN', 'USD'])],
            'exchange_rate' => ['sometimes', 'numeric', 'min:0.0001'],
            'issue_date' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'date'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'warranty_terms' => ['nullable', 'string', 'max:255'],
            'technical_notes' => ['nullable', 'string'],
            'items' => [$required, 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.catalog_item_id' => ['nullable', 'exists:catalog_items,id'],
            'items.*.section_name' => ['required', 'string', 'max:120'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.unit' => ['required', Rule::in(MeasurementUnit::values())],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'items.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
