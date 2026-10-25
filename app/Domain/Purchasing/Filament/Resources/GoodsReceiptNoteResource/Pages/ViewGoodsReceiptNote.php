<?php

namespace App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewGoodsReceiptNote extends ViewRecord
{
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['purchaseOrder.supplier', 'warehouse', 'items.product']);
    }

    protected static string $resource = GoodsReceiptNoteResource::class;
}
