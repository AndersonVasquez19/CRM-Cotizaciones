<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InvoiceMilestone;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function milestones(Request $request): JsonResponse
    {
        $query = InvoiceMilestone::with([
            'client:id,business_name,document_number',
            'quote:id,quote_number,version',
            'project:id,code,title',
            'payments.recorder:id,name',
        ]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $milestones = $query->latest('id')->get();

        $summary = [
            'total_receivable' => round((float) $milestones->sum('amount'), 2),
            'total_collected' => round((float) $milestones->sum('paid_amount'), 2),
            'total_pending' => round((float) $milestones->sum(fn ($m) => max(0, (float) $m->amount - (float) $m->paid_amount)), 2),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $milestones,
        ]);
    }

    public function recordPayment(Request $request, InvoiceMilestone $milestone): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['transferencia', 'cheque', 'tarjeta', 'efectivo', 'deposito'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = DB::transaction(function () use ($milestone, $validated, $request): InvoiceMilestone {
            $milestone->payments()->create([
                'recorded_by' => $request->user()->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $newPaid = round((float) $milestone->paid_amount + (float) $validated['amount'], 2);
            $target = (float) $milestone->amount;
            $status = $newPaid >= $target ? 'pagado' : ($newPaid > 0 ? 'parcial' : 'pendiente');

            $milestone->update([
                'paid_amount' => $newPaid,
                'status' => $status,
            ]);

            AuditService::log('finance.payment_recorded', $milestone, $request->user(), [
                'amount' => $validated['amount'],
                'status' => $status,
            ]);

            return $milestone->fresh(['client:id,business_name', 'quote:id,quote_number', 'project:id,code', 'payments.recorder:id,name']);
        });

        return response()->json(['data' => $updated], 201);
    }
}
