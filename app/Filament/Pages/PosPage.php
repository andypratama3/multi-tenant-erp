<?php

namespace App\Filament\Pages;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Actions\CreateDeliveryAction;
use App\Domain\Sales\Actions\CreateDeliveryData;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Actions\PostDeliveryAction;
use App\Domain\Sales\Actions\PostDeliveryData;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Tax\Models\TaxRate;
use App\Support\Money;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PosPage extends Page
{
    protected string $view = 'filament.pages.pos';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static \UnitEnum|string|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'POS / Kasir';

    protected static ?int $navigationSort = 1;

    public string $customerId = '';

    public string $warehouseId = '';

    public string $productId = '';

    public string $quantity = '1';

    public string $amountPaid = '0';

    public string $notes = '';

    /** @var array<string, array{product_id: string, name: string, sku: string, quantity: string, unit_price: string, tax_rate: string}> */
    public array $cart = [];

    public ?string $lastInvoiceNumber = null;

    public string $lastChange = '0.00';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    public function mount(): void
    {
        $this->warehouseId = (string) (Warehouse::query()->orderBy('name')->value('id') ?? '');
        $this->customerId = (string) (Customer::query()->where('is_active', true)->orderBy('name')->value('id') ?? '');
    }

    /** @return array<string, string> */
    public function customers(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, string> */
    public function warehouses(): array
    {
        return Warehouse::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<string, string> */
    public function products(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => $product->sku.' — '.$product->name.' ('.Money::formatIdr($product->selling_price).')',
            ])
            ->all();
    }

    public function addProduct(): void
    {
        $this->validate([
            'productId' => ['required', 'uuid'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $product = Product::query()->where('is_active', true)->find($this->productId);

        if ($product === null) {
            throw ValidationException::withMessages(['productId' => 'Produk tidak ditemukan.']);
        }

        $quantity = Money::round($this->quantity, 3);
        $existing = $this->cart[$product->id] ?? null;

        $this->cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $existing !== null ? Money::add($existing['quantity'], $quantity, 3) : $quantity,
            'unit_price' => $product->selling_price,
            'tax_rate' => $this->defaultTaxRate(),
        ];

        $this->productId = '';
        $this->quantity = '1';
    }

    public function removeProduct(string $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->amountPaid = '0';
        $this->lastChange = '0.00';
    }

    public function subtotal(): string
    {
        $subtotal = '0.00';

        foreach ($this->cart as $item) {
            $subtotal = Money::add($subtotal, Money::round(Money::mul($item['quantity'], $item['unit_price'], 4)));
        }

        return $subtotal;
    }

    public function taxAmount(): string
    {
        $tax = '0.00';

        foreach ($this->cart as $item) {
            $line = Money::round(Money::mul($item['quantity'], $item['unit_price'], 4));
            $tax = Money::add($tax, Money::percentage($line, $item['tax_rate']));
        }

        return $tax;
    }

    public function total(): string
    {
        return Money::add($this->subtotal(), $this->taxAmount());
    }

    public function change(): string
    {
        if (! is_numeric($this->amountPaid)) {
            return '0.00';
        }

        $change = Money::sub($this->amountPaid, $this->total());

        return Money::compare($change, '0') === 1 ? $change : '0.00';
    }

    public function process(): void
    {
        $this->validate([
            'customerId' => ['required', 'uuid'],
            'warehouseId' => ['required', 'uuid'],
            'amountPaid' => ['required', 'numeric', 'min:0'],
        ]);

        if ($this->cart === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }

        $total = $this->total();

        if (Money::compare($this->amountPaid, $total) === -1) {
            throw ValidationException::withMessages(['amountPaid' => 'Pembayaran kurang dari total belanja.']);
        }

        $createdBy = auth()->id();
        $createdByString = $createdBy !== null ? (string) $createdBy : null;
        $change = $this->change();

        try {
            $invoiceNumber = DB::transaction(function () use ($total, $createdByString): string {
                $items = [];

                foreach ($this->cart as $item) {
                    $items[] = [
                        'product_id' => $item['product_id'],
                        'quantity_ordered' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_rate' => '0.00',
                        'tax_rate' => $item['tax_rate'],
                    ];
                }

                $order = app(CreateSalesOrderAction::class)->execute(new CreateSalesOrderData(
                    customerId: $this->customerId,
                    orderedAt: now()->toDateString(),
                    requiredAt: now()->toDateString(),
                    notes: $this->notes !== '' ? $this->notes : 'POS',
                    createdBy: $createdByString,
                    items: $items,
                ));

                app(ConfirmSalesOrderAction::class)->execute(new ConfirmSalesOrderData(
                    salesOrderId: $order->id,
                    confirmedBy: $createdByString,
                ));

                $invoiceItems = [];

                foreach ($this->cart as $item) {
                    $invoiceItems[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_rate' => '0.00',
                        'tax_rate' => $item['tax_rate'],
                    ];
                }

                $invoice = app(IssueSalesInvoiceAction::class)->execute(new IssueSalesInvoiceData(
                    salesOrderId: $order->id,
                    customerId: $this->customerId,
                    issuedAt: now()->toDateString(),
                    dueAt: now()->toDateString(),
                    createdBy: $createdByString,
                    items: $invoiceItems,
                    postJournal: true,
                    notes: 'POS',
                ));

                app(RecordPaymentAction::class)->execute(new RecordPaymentData(
                    invoiceId: $invoice->id,
                    amount: $total,
                    paidAt: now()->toDateString(),
                    paymentMethod: 'cash',
                    reference: 'POS',
                    notes: 'Pembayaran POS',
                    createdBy: $createdByString,
                    postJournal: true,
                ));

                $delivery = app(CreateDeliveryAction::class)->execute(new CreateDeliveryData(
                    salesOrderId: $order->id,
                    warehouseId: $this->warehouseId,
                    shippedAt: now()->toDateString(),
                    notes: 'POS',
                    createdBy: $createdByString,
                    items: [],
                ));

                app(PostDeliveryAction::class)->execute(new PostDeliveryData(
                    deliveryId: $delivery->id,
                    postJournal: true,
                ));

                return $invoice->invoice_number;
            });

            $this->lastInvoiceNumber = $invoiceNumber;
            $this->clearCart();
            $this->notes = '';
            $this->lastChange = $change;

            Notification::make()
                ->title('Transaksi POS berhasil')
                ->body('Invoice '.$this->lastInvoiceNumber.' berhasil dibayar. Kembalian: '.Money::formatIdr($change))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Transaksi POS gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function defaultTaxRate(): string
    {
        $rate = TaxRate::query()
            ->where('type', 'ppn')
            ->where('is_active', true)
            ->latest()
            ->value('rate');

        return $rate !== null ? Money::decimal((string) $rate, 2) : '11.00';
    }
}
