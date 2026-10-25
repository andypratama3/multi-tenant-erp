<?php

namespace App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource;
use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Filament\Support\DocumentActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewGoodsReceiptNote extends ViewRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['purchaseOrder.supplier', 'warehouse', 'items.product', 'purchaseInvoice', 'activities.causer']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocumentActions::goodsReceiptPost(),
            Action::make('createInvoice')
                ->label('Buat Faktur Pembelian')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->authorize('create', PurchaseInvoice::class)
                ->visible(fn (GoodsReceiptNote $record): bool => $record->status === 'posted' && $record->purchaseInvoice === null)
                ->url(fn (GoodsReceiptNote $record): string => PurchaseInvoiceResource::getUrl('create', ['grn_id' => $record->id])),
        ];
    }
}
