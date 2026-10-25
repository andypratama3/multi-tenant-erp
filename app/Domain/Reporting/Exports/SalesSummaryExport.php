<?php

namespace App\Domain\Reporting\Exports;

use App\Domain\Reporting\Services\SalesSummaryReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesSummaryExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $from,
        private readonly string $to,
    ) {}

    /**
     * @return Collection<int, array{Tanggal: mixed, 'Jumlah Invoice': mixed, Subtotal: mixed, Pajak: mixed, Total: mixed, Dibayar: mixed}>
     */
    public function collection(): Collection
    {
        $report = new SalesSummaryReport;
        $rows = $report->dailySummary($this->from, $this->to);

        return $rows->map(function (\stdClass $row): array {
            return [
                'Tanggal' => $row->date,
                'Jumlah Invoice' => $row->invoice_count,
                'Subtotal' => $row->subtotal,
                'Pajak' => $row->tax_amount,
                'Total' => $row->total,
                'Dibayar' => $row->paid_amount,
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Tanggal', 'Jumlah Invoice', 'Subtotal', 'Pajak', 'Total', 'Dibayar'];
    }

    public function title(): string
    {
        return 'Ringkasan Penjualan';
    }
}
