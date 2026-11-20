<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor, UserRole::Comercial], true);
    }

    public function update(User $user, Quote $quote): bool
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true)) {
            return true;
        }

        return $user->role === UserRole::Comercial
            && $quote->created_by === $user->id
            && in_array($quote->status, [QuoteStatus::Borrador, QuoteStatus::PendienteAprobacion], true);
    }

    public function delete(User $user, Quote $quote): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $quote->created_by === $user->id && $quote->status === QuoteStatus::Borrador;
    }

    public function viewInternalCosts(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor, UserRole::Comercial], true);
    }

    public function approveMargin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }
}
