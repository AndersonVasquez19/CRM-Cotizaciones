<?php

namespace App\Actions\Quotes;

use App\Actions\Crm\MoveLeadStageAction;
use App\Actions\Projects\ConvertQuoteToProjectAction;
use App\Enums\LeadStage;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Mail\MarginApprovalRequestedMail;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChangeQuoteStatusAction
{
    public function __construct(
        private readonly MoveLeadStageAction $moveLeadStage,
        private readonly CalculateQuoteTotalsAction $calculateTotals,
        private readonly ConvertQuoteToProjectAction $convertQuoteToProject,
    ) {}

    public function execute(Quote $quote, QuoteStatus $newStatus, User $user, ?string $comment = null): Quote
    {
        return DB::transaction(function () use ($quote, $newStatus, $user, $comment): Quote {
            $previousStatus = $quote->status;
            $effectiveStatus = $newStatus;
            $effectiveComment = $comment;

            if (
                in_array($newStatus, [QuoteStatus::Enviada, QuoteStatus::Aprobada], true)
                && ! in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true)
                && $quote->approved_at === null
            ) {
                $marginCheck = $this->checkMarginPolicy($quote);
                if ($marginCheck['requires_approval']) {
                    $effectiveStatus = QuoteStatus::PendienteAprobacion;
                    $effectiveComment = sprintf(
                        'Requiere aprobación gerencial: margen %.2f%% (mínimo %.2f%%) / descuento %.2f%% (máximo %.2f%%).',
                        $marginCheck['margin_percent'],
                        $marginCheck['min_margin_percent'],
                        $marginCheck['discount_percent'],
                        $marginCheck['max_discount_percent'],
                    );
                    $quote->approval_requested_at = now();
                    $quote->approval_notes = $effectiveComment;

                    $this->notifySupervisors($quote, $marginCheck['margin_percent'], $marginCheck['min_margin_percent']);
                }
            }

            $quote->status = $effectiveStatus;
            $quote->save();

            $quote->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => $previousStatus->value,
                'to_status' => $effectiveStatus->value,
                'comment' => $effectiveComment ?? "Estado cambiado a {$effectiveStatus->label()}",
                'created_at' => now(),
            ]);

            AuditService::log('quote.status_changed', $quote, $user, [
                'from' => $previousStatus->value,
                'to' => $effectiveStatus->value,
            ]);

            if ($effectiveStatus === QuoteStatus::Aprobada) {
                if ($quote->lead_id !== null) {
                    $lead = $quote->lead;
                    if ($lead && $lead->stage !== LeadStage::Ganado) {
                        $this->moveLeadStage->execute(
                            lead: $lead,
                            newStage: LeadStage::Ganado,
                            user: $user,
                            notes: "Cotización {$quote->quote_number} v{$quote->version} aprobada.",
                        );
                    }
                }
                $this->convertQuoteToProject->execute($quote, $user);
            }

            return $quote->fresh(['client', 'contact', 'site', 'lead', 'creator', 'approver', 'items', 'statusLogs.user']);
        });
    }

    public function approveMargin(Quote $quote, User $approver, ?string $notes = null, QuoteStatus $targetStatus = QuoteStatus::Enviada): Quote
    {
        return DB::transaction(function () use ($quote, $approver, $notes, $targetStatus): Quote {
            $previousStatus = $quote->status;

            $quote->update([
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $notes ?? $quote->approval_notes,
                'status' => $targetStatus,
            ]);

            $quote->statusLogs()->create([
                'user_id' => $approver->id,
                'from_status' => $previousStatus->value,
                'to_status' => $targetStatus->value,
                'comment' => $notes ? "Margen aprobado por gerencia: {$notes}" : 'Margen aprobado por gerencia.',
                'created_at' => now(),
            ]);

            AuditService::log('quote.margin_approved', $quote, $approver, [
                'target_status' => $targetStatus->value,
            ]);

            return $quote->fresh(['client', 'contact', 'site', 'lead', 'creator', 'approver', 'items', 'statusLogs.user']);
        });
    }

    /**
     * @return array{requires_approval: bool, margin_percent: float, min_margin_percent: float, discount_percent: float, max_discount_percent: float}
     */
    private function checkMarginPolicy(Quote $quote): array
    {
        $quote->loadMissing('items');
        $itemsPayload = $quote->items->map(fn ($it) => [
            'description' => $it->description,
            'quantity' => (float) $it->quantity,
            'unit_cost' => (float) $it->unit_cost,
            'unit_price' => (float) $it->unit_price,
            'discount_percent' => (float) $it->discount_percent,
        ])->all();

        $totals = $this->calculateTotals->execute($itemsPayload, (float) $quote->discount_amount);

        $minMargin = (float) Setting::getValue('min_margin_percent', 20);
        $maxDiscount = (float) Setting::getValue('max_discount_percent', 10);

        $globalDiscountPercent = $totals['subtotal'] > 0
            ? round(($totals['discount_amount'] / $totals['subtotal']) * 100, 2)
            : 0.0;

        $requiresApproval = $totals['gross_margin_percent'] < $minMargin || $globalDiscountPercent > $maxDiscount;

        return [
            'requires_approval' => $requiresApproval,
            'margin_percent' => $totals['gross_margin_percent'],
            'min_margin_percent' => $minMargin,
            'discount_percent' => $globalDiscountPercent,
            'max_discount_percent' => $maxDiscount,
        ];
    }

    private function notifySupervisors(Quote $quote, float $marginPercent, float $minMarginPercent): void
    {
        $recipients = User::whereIn('role', [UserRole::Admin->value, UserRole::Supervisor->value])
            ->where('is_active', true)
            ->pluck('email')
            ->filter()
            ->all();

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new MarginApprovalRequestedMail($quote, $marginPercent, $minMarginPercent));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de aprobación de margen: '.$e->getMessage());
        }
    }
}
