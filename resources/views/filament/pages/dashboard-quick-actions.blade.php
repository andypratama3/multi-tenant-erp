@php
    $user = auth()->user();
    $actions = array_values(array_filter([
        $user?->can('create', \App\Domain\Sales\Models\SalesOrder::class) ? [
            'label' => 'Pesanan Penjualan',
            'description' => 'Buat SO baru',
            'url' => \App\Domain\Sales\Filament\Resources\SalesOrderResource::getUrl('create'),
            'icon' => 'heroicon-o-document-plus',
        ] : null,
        $user?->can('create', \App\Domain\Sales\Models\SalesInvoice::class) ? [
            'label' => 'Faktur Penjualan',
            'description' => 'Terbitkan invoice',
            'url' => \App\Domain\Sales\Filament\Resources\SalesInvoiceResource::getUrl('create'),
            'icon' => 'heroicon-o-receipt-percent',
        ] : null,
        $user?->can('create', \App\Domain\Purchasing\Models\PurchaseOrder::class) ? [
            'label' => 'Pesanan Pembelian',
            'description' => 'Buat PO baru',
            'url' => \App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource::getUrl('create'),
            'icon' => 'heroicon-o-shopping-cart',
        ] : null,
        $user?->can('create', \App\Domain\Accounting\Models\Journal::class) ? [
            'label' => 'Jurnal Manual',
            'description' => 'Posting jurnal',
            'url' => \App\Domain\Accounting\Filament\Resources\JournalResource::getUrl('create'),
            'icon' => 'heroicon-o-book-open',
        ] : null,
    ]));
@endphp

@if ($actions !== [])
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($actions as $action)
            <a
                href="{{ $action['url'] }}"
                class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-white/10 dark:bg-gray-900 dark:hover:border-primary-500/50"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                    <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</span>
                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $action['description'] }}</span>
                </span>
                <x-filament::icon icon="heroicon-m-arrow-right" class="ms-auto h-4 w-4 text-gray-300 transition group-hover:text-primary-500" />
            </a>
        @endforeach
    </div>
@endif
