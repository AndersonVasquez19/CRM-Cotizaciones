<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadStage;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(): JsonResponse
    {
        $startOfMonth = now()->startOfMonth();

        $monthlyQuotedTotal = (float) Quote::where('created_at', '>=', $startOfMonth)
            ->sum('total');

        $monthlyApprovedTotal = (float) Quote::where('status', QuoteStatus::Aprobada)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');

        $totalLeads = Lead::count();
        $wonLeads = Lead::where('stage', LeadStage::Ganado)->count();
        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0.0;

        $activeClients = Client::where('is_active', true)->count();

        $rawStages = Lead::select('stage', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(estimated_amount), 0) as total_amount'))
            ->groupBy('stage')
            ->get()
            ->keyBy(fn ($row) => $row->stage instanceof LeadStage ? $row->stage->value : (string) $row->stage);

        $pipelineByStage = [];
        foreach (LeadStage::cases() as $stage) {
            $row = $rawStages->get($stage->value);
            $pipelineByStage[] = [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'count' => (int) ($row->count ?? 0),
                'total_amount' => round((float) ($row->total_amount ?? 0), 2),
            ];
        }

        $recentQuotes = Quote::with(['client:id,business_name', 'creator:id,name'])
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Quote $q) => [
                'id' => $q->id,
                'quote_number' => $q->quote_number,
                'version' => $q->version,
                'status' => $q->status->value,
                'status_label' => $q->status->label(),
                'currency' => $q->currency,
                'total' => (float) $q->total,
                'issue_date' => $q->issue_date?->toDateString(),
                'client' => $q->client ? ['id' => $q->client->id, 'business_name' => $q->client->business_name] : null,
                'creator' => $q->creator ? ['id' => $q->creator->id, 'name' => $q->creator->name] : null,
            ]);

        return response()->json([
            'kpis' => [
                'monthly_quoted_total' => round($monthlyQuotedTotal, 2),
                'monthly_approved_total' => round($monthlyApprovedTotal, 2),
                'lead_conversion_rate' => $conversionRate,
                'active_clients' => $activeClients,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
            ],
            'pipeline_by_stage' => $pipelineByStage,
            'recent_quotes' => $recentQuotes,
        ]);
    }
}
