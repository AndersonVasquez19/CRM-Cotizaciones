<?php

namespace App\Actions\Quotes;

use App\Actions\Crm\MoveLeadStageAction;
use App\Actions\Projects\ConvertQuoteToProjectAction;
use App\Enums\LeadStage;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteAcceptance;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class SubmitPublicQuoteDecisionAction
{
    public function __construct(
        private readonly MoveLeadStageAction $moveLeadStage,
        private readonly ConvertQuoteToProjectAction $convertQuoteToProject,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Quote $quote, array $data, ?string $ip = null, ?string $userAgent = null): QuoteAcceptance
    {
        return DB::transaction(function () use ($quote, $data, $ip, $userAgent): QuoteAcceptance {
            $decision = $data['decision'];
            $newStatus = $decision === 'aprobada' ? QuoteStatus::Aprobada : QuoteStatus::Rechazada;
            $previousStatus = $quote->status;

            $acceptance = $quote->acceptances()->create([
                'decision' => $decision,
                'signer_name' => $data['signer_name'],
                'signer_document' => $data['signer_document'] ?? null,
                'signer_email' => $data['signer_email'] ?? null,
                'signature_data' => $data['signature_data'] ?? null,
                'comment' => $data['comment'] ?? null,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
                'accepted_at' => now(),
            ]);

            $quote->update(['status' => $newStatus]);

            $actingUser = $quote->creator;
            if ($actingUser) {
                $quote->statusLogs()->create([
                    'user_id' => $actingUser->id,
                    'from_status' => $previousStatus->value,
                    'to_status' => $newStatus->value,
                    'comment' => sprintf(
                        'Decisión desde Portal Cliente por %s (%s). %s',
                        $data['signer_name'],
                        $ip ?? 'IP desconocida',
                        $data['comment'] ?? ''
                    ),
                    'created_at' => now(),
                ]);

                if ($newStatus === QuoteStatus::Aprobada) {
                    if ($quote->lead_id !== null && $quote->lead && $quote->lead->stage !== LeadStage::Ganado) {
                        $this->moveLeadStage->execute(
                            lead: $quote->lead,
                            newStage: LeadStage::Ganado,
                            user: $actingUser,
                            notes: "Aprobada en Portal Cliente por {$data['signer_name']}.",
                        );
                    }
                    $this->convertQuoteToProject->execute($quote, $actingUser);
                }
            }

            AuditService::log("quote.portal_{$decision}", $quote, null, [
                'signer_name' => $data['signer_name'],
                'ip' => $ip,
            ], $ip);

            return $acceptance;
        });
    }
}
