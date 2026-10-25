<?php

namespace App\Domain\Sales\Pdfs;

use App\Domain\Sales\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesInvoicePdf
{
    public function __construct(private readonly SalesInvoice $invoice) {}

    public function download(): StreamedResponse
    {
        $pdf = Pdf::loadView('sales.invoices.pdf', [
            'invoice' => $this->invoice->load(['customer', 'items.product']),
        ]);

        $filename = 'invoice-'.$this->invoice->invoice_number.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
