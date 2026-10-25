<?php

namespace App\Filament\Support;

use App\Domain\Inventory\Actions\PostStockAdjustmentAction;
use App\Domain\Inventory\Actions\PostWarehouseTransferAction;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\WarehouseTransfer;
use App\Domain\Purchasing\Actions\PostGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\PostGrnData;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentAction;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentData;
use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Actions\PostDeliveryAction;
use App\Domain\Sales\Actions\PostDeliveryData;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Actions\VoidSalesInvoiceAction;
use App\Domain\Sales\Actions\VoidSalesInvoiceData;
use App\Domain\Sales\Filament\Resources\DeliveryResource;
use App\Domain\Sales\Filament\Resources\SalesInvoiceResource;
use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesReturn;
use App\Domain\Sales\Pdfs\SalesInvoicePdf;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Throwable;

final class DocumentActions
{
    public static function salesInvoicePdf(): Action
    {
        return Action::make('downloadPdf')
            ->label('Unduh PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->authorize('downloadPdf')
            ->action(fn (SalesInvoice $record) => (new SalesInvoicePdf($record))->download());
    }

    public static function salesInvoiceReturn(): Action
    {
        return Action::make('createReturn')
            ->label('Retur')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->authorize('create', SalesReturn::class)
            ->visible(fn (SalesInvoice $record): bool => $record->status !== 'cancelled')
            ->url(fn (SalesInvoice $record): string => SalesReturnResource::getUrl('create', ['invoice_id' => $record->id]));
    }

    public static function salesInvoicePayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Catat Pembayaran')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->authorize('recordPayment')
            ->visible(fn (SalesInvoice $record): bool => in_array($record->status, ['issued', 'partial'], true))
            ->schema([
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->prefix('Rp')
                    ->default(fn (SalesInvoice $record): string => Money::round(Money::sub(Money::sub($record->total, $record->returned_amount), $record->paid_amount))),
                DatePicker::make('paid_at')->label('Tanggal Bayar')->required()->default(now()->toDateString()),
                Select::make('payment_method')
                    ->label('Metode')
                    ->options(['cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'other' => 'Lainnya'])
                    ->required()
                    ->default('transfer'),
                TextInput::make('reference')->label('Referensi'),
                Textarea::make('notes')->label('Catatan')->rows(2),
            ])
            ->action(function (SalesInvoice $record, array $data): void {
                $createdBy = auth()->id();

                try {
                    app(RecordPaymentAction::class)->execute(new RecordPaymentData(
                        invoiceId: $record->id,
                        amount: (string) $data['amount'],
                        paidAt: (string) $data['paid_at'],
                        paymentMethod: (string) $data['payment_method'],
                        reference: isset($data['reference']) ? (string) $data['reference'] : null,
                        createdBy: $createdBy !== null ? (string) $createdBy : null,
                        postJournal: true,
                        notes: isset($data['notes']) ? (string) $data['notes'] : null,
                    ));

                    $record->refresh();
                    Notification::make()->title('Pembayaran berhasil dicatat.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal mencatat pembayaran.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function salesInvoiceVoid(): Action
    {
        return Action::make('void')
            ->label('Batalkan Faktur')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize('void')
            ->visible(fn (SalesInvoice $record): bool => ! in_array($record->status, ['cancelled', 'paid'], true)
                && ! Money::isPositive($record->returned_amount)
                && ($record->returns_count ?? $record->returns()->count()) === 0)
            ->schema([
                Textarea::make('reason')->label('Alasan Pembatalan')->required()->rows(2),
            ])
            ->action(function (SalesInvoice $record, array $data): void {
                $createdBy = auth()->id();

                try {
                    app(VoidSalesInvoiceAction::class)->execute(new VoidSalesInvoiceData(
                        invoiceId: $record->id,
                        reason: (string) $data['reason'],
                        createdBy: $createdBy !== null ? (string) $createdBy : null,
                    ));

                    $record->refresh();
                    Notification::make()->title('Faktur berhasil dibatalkan.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal membatalkan faktur.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function purchaseInvoicePayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Catat Pembayaran')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->authorize('recordPayment')
            ->visible(fn (PurchaseInvoice $record): bool => in_array($record->status, ['posted', 'partial'], true))
            ->schema([
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->prefix('Rp')
                    ->default(fn (PurchaseInvoice $record): string => Money::sub($record->total, $record->paid_amount)),
                DatePicker::make('paid_at')->label('Tanggal Bayar')->required()->default(now()->toDateString()),
                Select::make('payment_method')
                    ->label('Metode')
                    ->options(['cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'other' => 'Lainnya'])
                    ->required()
                    ->default('transfer'),
                TextInput::make('reference')->label('Referensi')->maxLength(100),
                Textarea::make('notes')->label('Catatan')->rows(2),
            ])
            ->action(function (PurchaseInvoice $record, array $data): void {
                $createdBy = auth()->id();

                try {
                    app(RecordSupplierPaymentAction::class)->execute(new RecordSupplierPaymentData(
                        purchaseInvoiceId: $record->id,
                        amount: (string) $data['amount'],
                        paidAt: (string) $data['paid_at'],
                        paymentMethod: (string) $data['payment_method'],
                        reference: isset($data['reference']) ? (string) $data['reference'] : null,
                        notes: isset($data['notes']) ? (string) $data['notes'] : null,
                        createdBy: $createdBy !== null ? (string) $createdBy : null,
                        postJournal: true,
                    ));

                    $record->refresh();
                    Notification::make()->title('Pembayaran utang berhasil dicatat.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Pembayaran gagal dicatat.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function salesOrderConfirm(): Action
    {
        return Action::make('confirm')
            ->label('Konfirmasi')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->authorize('confirm')
            ->visible(fn (SalesOrder $record): bool => $record->status === 'draft')
            ->action(function (SalesOrder $record): void {
                try {
                    app(ConfirmSalesOrderAction::class)->execute(new ConfirmSalesOrderData(
                        salesOrderId: $record->id,
                        confirmedBy: auth()->id() !== null ? (string) auth()->id() : null,
                    ));

                    $record->refresh();
                    Notification::make()->title('Pesanan penjualan dikonfirmasi.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal mengonfirmasi pesanan.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function salesOrderInvoice(): Action
    {
        return Action::make('invoice')
            ->label('Buat Invoice')
            ->icon('heroicon-o-document-plus')
            ->color('info')
            ->authorize('create', SalesInvoice::class)
            ->visible(fn (SalesOrder $record): bool => in_array($record->status, ['confirmed', 'partial'], true))
            ->url(fn (SalesOrder $record): string => SalesInvoiceResource::getUrl('create', ['sales_order_id' => $record->id]));
    }

    public static function salesOrderDelivery(): Action
    {
        return Action::make('delivery')
            ->label('Buat Pengiriman')
            ->icon('heroicon-o-truck')
            ->color('success')
            ->authorize('create', Delivery::class)
            ->visible(fn (SalesOrder $record): bool => in_array($record->status, ['confirmed', 'partial'], true))
            ->url(fn (SalesOrder $record): string => DeliveryResource::getUrl('create', ['sales_order_id' => $record->id]));
    }

    public static function deliveryPost(): Action
    {
        return Action::make('post')
            ->label('Posting')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Posting akan mengurangi stok dan membuat jurnal HPP. Tindakan ini tidak dapat dibatalkan.')
            ->authorize('post')
            ->visible(fn (Delivery $record): bool => $record->status === 'draft')
            ->action(function (Delivery $record): void {
                try {
                    app(PostDeliveryAction::class)->execute(new PostDeliveryData(
                        deliveryId: $record->id,
                        postJournal: true,
                    ));

                    $record->refresh();
                    Notification::make()->title('Pengiriman berhasil diposting.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal memposting pengiriman.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function goodsReceiptPost(): Action
    {
        return Action::make('post')
            ->label('Posting')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Posting akan menambah stok dan membuat jurnal persediaan. Tindakan ini tidak dapat dibatalkan.')
            ->authorize('post')
            ->visible(fn (GoodsReceiptNote $record): bool => $record->status === 'draft')
            ->action(function (GoodsReceiptNote $record): void {
                try {
                    app(PostGoodsReceiptNoteAction::class)->execute(new PostGrnData(
                        grnId: $record->id,
                        postJournal: true,
                    ));

                    $record->refresh();
                    Notification::make()->title('Penerimaan barang berhasil diposting.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal memposting penerimaan.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function stockAdjustmentPost(): Action
    {
        return Action::make('post')
            ->label('Posting')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->authorize('post')
            ->visible(fn (StockAdjustment $record): bool => $record->status === 'draft')
            ->requiresConfirmation()
            ->action(function (StockAdjustment $record): void {
                $postedBy = auth()->id();

                try {
                    app(PostStockAdjustmentAction::class)->execute(
                        $record->id,
                        $postedBy !== null ? (string) $postedBy : null,
                    );

                    $record->refresh();
                    Notification::make()->title('Stock opname berhasil diposting.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal posting stock opname.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function warehouseTransferPost(): Action
    {
        return Action::make('post')
            ->label('Posting')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->authorize('post')
            ->visible(fn (WarehouseTransfer $record): bool => $record->status === 'draft')
            ->requiresConfirmation()
            ->action(function (WarehouseTransfer $record): void {
                $postedBy = auth()->id();

                try {
                    app(PostWarehouseTransferAction::class)->execute(
                        $record->id,
                        $postedBy !== null ? (string) $postedBy : null,
                    );

                    $record->refresh();
                    Notification::make()->title('Transfer berhasil diposting.')->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title('Gagal posting transfer.')->body($exception->getMessage())->danger()->send();
                }
            });
    }

    public static function purchaseInvoiceUrl(PurchaseInvoice $invoice): string
    {
        return PurchaseInvoiceResource::getUrl('view', ['record' => $invoice->id]);
    }
}
