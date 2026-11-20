<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfYear()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $quotes = Quote::with(['items', 'creator:id,name'])
            ->whereBetween('issue_date', [$from, $to])
            ->get();

        $totalQuoted = round((float) $quotes->sum('total'), 2);
        $approvedQuotes = $quotes->where('status', QuoteStatus::Aprobada);
        $totalApproved = round((float) $approvedQuotes->sum('total'), 2);

        $quotedNetRevenue = 0.0;
        $quotedCost = 0.0;
        foreach ($approvedQuotes as $q) {
            $quotedNetRevenue += max(0, (float) $q->subtotal - (float) $q->discount_amount);
            $quotedCost += (float) $q->items->sum(fn ($it) => (float) $it->quantity * (float) $it->unit_cost);
        }

        $avgMarginPercent = $quotedNetRevenue > 0
            ? round((($quotedNetRevenue - $quotedCost) / $quotedNetRevenue) * 100, 2)
            : 0.0;

        $byServiceLine = Lead::select('service_line', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(estimated_amount), 0) as amount'))
            ->groupBy('service_line')
            ->get();

        $bySeller = $quotes->groupBy('created_by')->map(function ($group) {
            $first = $group->first();
            $approved = $group->where('status', QuoteStatus::Aprobada);

            return [
                'seller' => $first->creator?->name ?? 'Sin asignar',
                'quotes_count' => $group->count(),
                'approved_count' => $approved->count(),
                'approved_total' => round((float) $approved->sum('total'), 2),
            ];
        })->values();

        $projectsCompleted = Project::where('status', 'completado')->count();
        $projectsActive = Project::whereIn('status', ['planificado', 'en_ejecucion'])->count();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'kpis' => [
                'total_quoted' => $totalQuoted,
                'total_approved' => $totalApproved,
                'avg_margin_percent' => $avgMarginPercent,
                'projects_active' => $projectsActive,
                'projects_completed' => $projectsCompleted,
            ],
            'by_service_line' => $byServiceLine,
            'by_seller' => $bySeller,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $quotes = Quote::with(['client:id,business_name,document_number', 'creator:id,name'])
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($quotes) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Numero', 'Version', 'Estado', 'Cliente', 'RUC/Doc', 'Moneda', 'Subtotal', 'IGV', 'Total', 'Emision', 'Ejecutivo']);
            foreach ($quotes as $q) {
                fputcsv($handle, [
                    $q->quote_number,
                    $q->version,
                    $q->status->value,
                    $q->client?->business_name,
                    $q->client?->document_number,
                    $q->currency,
                    $q->subtotal,
                    $q->tax_amount,
                    $q->total,
                    $q->issue_date?->toDateString(),
                    $q->creator?->name,
                ]);
            }
            fclose($handle);
        }, 'reporte-cotizaciones-techquote.csv', ['Content-Type' => 'text/csv']);
    }
}
