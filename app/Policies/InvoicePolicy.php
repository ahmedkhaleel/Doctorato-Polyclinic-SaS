<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('invoices.view');
    }

    /**
     * Admin/secretary can view any invoice.
     * Patient can view own invoices.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role?->hasPermission('invoices.view')) {
            return true;
        }

        return $user->patient?->id === $invoice->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->role?->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if (! $user->role?->hasPermission('invoices.update')) {
            return false;
        }

        // Cannot update paid invoices
        if ($invoice->status === 'paid') {
            return false;
        }

        return true;
    }

    /**
     * Invoices should generally not be deleted (audit trail).
     * Only super admin with wildcard permission can.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->role?->hasPermission('*');
    }
}
