<?php

namespace App\Actions\Crm;

use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoveLeadStageAction
{
    public function execute(
        Lead $lead,
        LeadStage $newStage,
        User $user,
        ?string $lostReason = null,
        ?string $notes = null,
    ): Lead {
        return DB::transaction(function () use ($lead, $newStage, $user, $lostReason, $notes): Lead {
            $previousStage = $lead->stage;

            $lead->update([
                'stage' => $newStage,
                'lost_reason' => $newStage === LeadStage::Perdido ? ($lostReason ?? $lead->lost_reason) : $lead->lost_reason,
            ]);

            $lead->activities()->create([
                'user_id' => $user->id,
                'activity_type' => 'nota',
                'subject' => "Cambio de etapa: {$previousStage->label()} -> {$newStage->label()}",
                'description' => $notes ?? ($lostReason ? "Motivo de pérdida: {$lostReason}" : null),
                'completed_at' => now(),
            ]);

            return $lead->fresh(['client', 'site', 'assignedUser', 'technician', 'activities.user']);
        });
    }
}
