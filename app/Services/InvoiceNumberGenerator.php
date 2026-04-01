<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceNumberGenerator
{
    /**
     * Generate the next invoice number (INV-YYYYMM-XXXX format).
     */
    public function generate(): string
    {
        return Invoice::generateInvoiceNumber();
    }
}
