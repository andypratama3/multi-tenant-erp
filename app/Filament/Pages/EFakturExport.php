<?php

namespace App\Filament\Pages;

use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Tax\Services\EFakturExportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EFakturExport extends Page
{
    protected string $view = 'filament.pages.e-faktur-export';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Export e-Faktur';

    protected static ?int $navigationSort = 13;

    public string $fromDate = '';

    public string $toDate = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function download(): StreamedResponse
    {
        $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        $csv = (new EFakturExportService)->csv($this->fromDate, $this->toDate);
        $filename = 'efaktur-'.$this->fromDate.'-'.$this->toDate.'.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo "\xEF\xBB\xBF".$csv;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
