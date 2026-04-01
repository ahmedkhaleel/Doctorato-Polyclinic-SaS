<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('payments.view');
    }

    /**
     * Admin/secretary can view any payment.
     * Patient can view own payments.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->role?->hasPermission('payments.view')) {
            return true;
        }

        return $user->patient?->id === $payment->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->role?->hasPermission('payments.create');
    }

    /**
     * Only super admin can delete payments (financial audit trail).
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->role?->hasPermission('payments.delete');
    }
}
