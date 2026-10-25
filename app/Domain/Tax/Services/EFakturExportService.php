<?php

namespace App\Domain\Tax\Services;

use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Money;
use Illuminate\Support\Collection;

class EFakturExportService
{
    /**
     * @return Collection<int, SalesInvoice>
     */
    public function invoices(string $from, string $to): Collection
    {
        return SalesInvoice::query()
            ->with('customer')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->whereDate('issued_at', '>=', $from)
            ->whereDate('issued_at', '<=', $to)
            ->orderBy('issued_at')
            ->get();
    }

    public function csv(string $from, string $to): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'FK',
            'KD_JENIS_TRANSAKSI',
            'FG_PENGGANTI',
            'NOMOR_FAKTUR',
            'MASA_PAJAK',
            'TAHUN_PAJAK',
            'TANGGAL_FAKTUR',
            'NPWP',
            'NAMA',
            'ALAMAT_LENGKAP',
            'JUMLAH_DPP',
            'JUMLAH_PPN',
            'JUMLAH_PPNBM',
            'ID_KETERANGAN_TAMBAHAN',
            'FG_UANG_MUKA',
            'UANG_MUKA_DPP',
            'UANG_MUKA_PPN',
            'UANG_MUKA_PPNBM',
            'REFERENSI',
        ], ';');

        foreach ($this->invoices($from, $to) as $invoice) {
            $customer = $invoice->customer;

            if (Money::compare($invoice->tax_amount, '0') <= 0 || $customer === null) {
                continue;
            }

            fputcsv($handle, [
                'FK',
                '01',
                '0',
                $invoice->invoice_number,
                $invoice->issued_at->format('m'),
                $invoice->issued_at->format('Y'),
                $invoice->issued_at->format('d/m/Y'),
                $customer->tax_id ?? '',
                $customer->name,
                $customer->address ?? '',
                Money::round($invoice->subtotal),
                Money::round($invoice->tax_amount),
                '0',
                '',
                '0',
                '0',
                '0',
                '0',
                $invoice->invoice_number,
            ], ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content !== false ? $content : '';
    }
}
