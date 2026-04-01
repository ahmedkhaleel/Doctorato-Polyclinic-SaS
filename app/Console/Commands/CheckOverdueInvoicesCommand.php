<?php

namespace App\Console\Commands;

use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use Illuminate\Console\Command;

class CheckOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:check-overdue {--days=7 : Days past due threshold}';
    protected $description = 'Find overdue invoices and dispatch InvoiceOverdue events';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $overdueInvoices = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('invoice_date', '<=', $cutoff)
            ->with('patient:id,full_name,phone')
            ->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            $daysPastDue = (int) now()->diffInDays($invoice->invoice_date);
            InvoiceOverdue::dispatch($invoice, $daysPastDue);
            $count++;
        }

        $this->info("Dispatched {$count} overdue invoice reminder(s) (threshold: {$days} days).");

        return self::SUCCESS;
    }
}
