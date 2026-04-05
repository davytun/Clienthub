<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->business_id === $invoice->business_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'staff']);
    }

    /**
     * Only draft invoices can be edited. Once sent, the record is locked.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $invoice->business_id
            && $invoice->status === 'draft';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $invoice->business_id
            && $invoice->status === 'draft';
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $invoice->business_id
            && $invoice->status === 'draft';
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'staff'])
            && $user->business_id === $invoice->business_id
            && $invoice->status === 'sent';
    }

    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        return $user->business_id === $invoice->business_id;
    }
}
