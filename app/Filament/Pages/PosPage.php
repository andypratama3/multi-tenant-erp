<?php

namespace App\Filament\Pages;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
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
use App\Support\Money;
use App\Support\Taxes\TaxDefaults;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class PosPage extends Page
{
    protected string $view = 'filament.pages.pos';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static \UnitEnum|string|null $navigationGroup = 'Penjualan';

    protected static ?string $title = 'POS / Kasir';

    protected static ?string $navigationLabel = 'POS / Kasir';

    protected static ?int $navigationSort = 1;

    public string $customerId = '';

    public string $warehouseId = '';

    public string $productId = '';

    public string $quantity = '1';

    public string $amountPaid = '0';

    public string $paymentMethod = 'cash';

    public string $notes = '';

    /** @var array<string, array{product_id: string, name: string, sku: string, quantity: string, unit_price: string, tax_rate: string}> */
    public array $cart = [];

    public ?string $lastInvoiceNumber = null;

    public string $lastChange = '0.00';

    public bool $processing = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('Create:SalesInvoice')
            && $user->can('Confirm:SalesOrder')
            && $user->can('Create:Delivery')
            && $user->can('Post:Delivery');
    }

    public function mount(): void
    {
        $this->warehouseId = (string) (Warehouse::query()->where('is_active', true)->orderBy('name')->value('id') ?? '');
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
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, string> */
    public function products(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->where('is_stockable', true)
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => $product->sku.' — '.$product->name.' ('.Money::formatIdr($product->selling_price).')',
            ])
            ->all();
    }

    /** @return numeric-string */
    public function stockFor(string $productId): string
    {
        if ($this->warehouseId === '') {
            return '0.000';
        }

        $stock = StockBalance::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $this->warehouseId)
            ->value('quantity');

        return $stock !== null ? Money::decimal((string) $stock, 3) : '0.000';
    }

    public function addProduct(): void
    {
        $this->validate([
            'productId' => ['required', 'uuid'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'warehouseId' => ['required', 'uuid'],
        ]);

        $product = Product::query()
            ->where('is_active', true)
            ->where('is_stockable', true)
            ->find($this->productId);

        if ($product === null) {
            throw ValidationException::withMessages(['productId' => 'Produk tidak ditemukan atau tidak dikelola stoknya.']);
        }

        $quantity = Money::round($this->quantity, 3);
        $existing = $this->cart[$product->id] ?? null;
        $newQuantity = $existing !== null ? Money::add($existing['quantity'], $quantity, 3) : $quantity;
        $available = $this->stockFor($product->id);

        if (bccomp($newQuantity, $available, 3) === 1) {
            throw ValidationException::withMessages([
                'quantity' => "Stok tersedia hanya {$available} untuk {$product->name}.",
            ]);
        }

        $this->cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $newQuantity,
            'unit_price' => $product->selling_price,
            'tax_rate' => TaxDefaults::ppnRate(),
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

    public function setExactAmount(): void
    {
        $this->amountPaid = $this->total();
    }

    public function addCash(int $amount): void
    {
        $this->amountPaid = Money::add($this->amountPaid, $amount);
    }

    public function dismissLastReceipt(): void
    {
        $this->lastInvoiceNumber = null;
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
        if ($this->processing) {
            return;
        }

        $this->processing = true;

        try {
            $this->validate([
                'customerId' => ['required', 'uuid'],
                'warehouseId' => ['required', 'uuid'],
                'amountPaid' => ['required', 'numeric', 'min:0'],
                'paymentMethod' => ['required', 'in:cash,transfer,qris,other'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            if ($this->cart === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
            }

            $lines = $this->validatedLines();
            $serverSubtotal = '0.00';
            $serverTax = '0.00';

            foreach ($lines as $line) {
                $serverSubtotal = Money::add($serverSubtotal, $line['subtotal']);
                $serverTax = Money::add($serverTax, $line['tax_amount']);
            }

            $serverTotal = Money::add($serverSubtotal, $serverTax);

            if (Money::compare($this->amountPaid, $serverTotal) === -1) {
                throw ValidationException::withMessages(['amountPaid' => 'Pembayaran kurang dari total belanja.']);
            }

            $createdBy = auth()->id();
            $createdByString = $createdBy !== null ? (string) $createdBy : null;
            $change = Money::sub($this->amountPaid, $serverTotal);
            $notes = $this->notes !== '' ? $this->notes : 'POS';

            $invoiceNumber = DB::transaction(function () use ($lines, $serverTotal, $createdByString, $notes): string {
                $orderItems = [];
                $invoiceItems = [];

                foreach ($lines as $line) {
                    $orderItems[] = [
                        'product_id' => $line['product_id'],
                        'quantity_ordered' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'discount_rate' => '0.00',
                        'tax_rate' => $line['tax_rate'],
                    ];
                    $invoiceItems[] = [
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'discount_rate' => '0.00',
                        'tax_rate' => $line['tax_rate'],
                    ];
                }

                $order = app(CreateSalesOrderAction::class)->execute(new CreateSalesOrderData(
                    customerId: $this->customerId,
                    orderedAt: now()->toDateString(),
                    requiredAt: now()->toDateString(),
                    notes: $notes,
                    createdBy: $createdByString,
                    items: $orderItems,
                ));

                app(ConfirmSalesOrderAction::class)->execute(new ConfirmSalesOrderData(
                    salesOrderId: $order->id,
                    confirmedBy: $createdByString,
                ));

                $invoice = app(IssueSalesInvoiceAction::class)->execute(new IssueSalesInvoiceData(
                    salesOrderId: $order->id,
                    customerId: $this->customerId,
                    issuedAt: now()->toDateString(),
                    dueAt: now()->toDateString(),
                    createdBy: $createdByString,
                    items: $invoiceItems,
                    postJournal: true,
                    notes: $notes,
                ));

                app(RecordPaymentAction::class)->execute(new RecordPaymentData(
                    invoiceId: $invoice->id,
                    amount: $serverTotal,
                    paidAt: now()->toDateString(),
                    paymentMethod: $this->paymentMethod,
                    reference: 'POS',
                    notes: 'Pembayaran POS',
                    createdBy: $createdByString,
                    postJournal: true,
                ));

                $delivery = app(CreateDeliveryAction::class)->execute(new CreateDeliveryData(
                    salesOrderId: $order->id,
                    warehouseId: $this->warehouseId,
                    shippedAt: now()->toDateString(),
                    notes: $notes,
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
            $this->lastChange = Money::compare($change, '0') === 1 ? $change : '0.00';

            Notification::make()
                ->title('Transaksi POS berhasil')
                ->body('Invoice '.$this->lastInvoiceNumber.' berhasil dibayar. Kembalian: '.Money::formatIdr($this->lastChange))
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Transaksi POS gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->processing = false;
        }
    }

    /**
     * Re-read every cart line from the database so the client cannot alter
     * price, tax, product identity, or stock availability.
     *
     * @return array<int, array{product_id: string, quantity: string, unit_price: string, tax_rate: string, subtotal: string, tax_amount: string}>
     */
    private function validatedLines(): array
    {
        $lines = [];

        foreach ($this->cart as $productId => $item) {
            $product = Product::query()
                ->whereKey($productId)
                ->where('is_active', true)
                ->where('is_stockable', true)
                ->first();

            if ($product === null) {
                throw new InvalidArgumentException('Produk pada keranjang tidak lagi tersedia.');
            }

            $quantity = Money::round($item['quantity'], 3);

            if (! Money::isPositive($quantity)) {
                throw new InvalidArgumentException('Jumlah produk harus lebih besar dari nol.');
            }

            $available = $this->stockFor($product->id);

            if (bccomp($quantity, $available, 3) === 1) {
                throw new InvalidArgumentException("Stok {$product->name} tidak cukup. Tersedia {$available}.");
            }

            $unitPrice = Money::decimal($product->selling_price, 4);
            $taxRate = TaxDefaults::ppnRate();
            $subtotal = Money::round(Money::mul($quantity, $unitPrice, 4));

            $lines[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax_amount' => Money::percentage($subtotal, $taxRate),
            ];
        }

        return $lines;
    }
}
