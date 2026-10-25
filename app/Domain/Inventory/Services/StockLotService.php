<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockLedger;
use App\Domain\Inventory\Models\StockLot;
use App\Domain\Inventory\Models\StockLotAllocation;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class StockLotService
{
    /** @return numeric-string */
    public function availableQuantity(
        string $productId,
        string $warehouseId,
        bool $includeExpired = false,
    ): string {
        $query = StockLot::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_remaining', '>', 0);

        if (! $includeExpired) {
            $query->where(function (Builder $query): void {
                $query
                    ->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', today()->toDateString());
            });
        }

        return Money::decimal((string) $query->sum('quantity_remaining'), 3);
    }

    public function earliestExpiry(string $productId, string $warehouseId): ?Carbon
    {
        $expiry = StockLot::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sellable()
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->orderBy('received_at')
            ->value('expiry_date');

        return $expiry !== null ? Carbon::parse((string) $expiry) : null;
    }

    public function receive(Product $product, RecordStockMovementData $data, StockLedger $ledger): void
    {
        if (! $product->requires_expiry) {
            return;
        }

        $lots = $data->lots;

        if ($lots !== []) {
            $total = '0.000';

            foreach ($lots as $lot) {
                if (Money::compare($lot['quantity'], '0') !== 1) {
                    throw new InvalidArgumentException('Kuantitas batch harus lebih besar dari nol.');
                }

                $total = Money::add($total, $lot['quantity'], 3);
            }

            if (bccomp($total, Money::decimal($data->quantity, 3), 3) !== 0) {
                throw new InvalidArgumentException('Total kuantitas batch tidak sama dengan kuantitas pergerakan stok.');
            }

            foreach ($lots as $lot) {
                $this->createLot(
                    product: $product,
                    data: $data,
                    ledger: $ledger,
                    quantity: $lot['quantity'],
                    lotNumber: $lot['lot_number'] ?? null,
                    expiryDate: $lot['expiry_date'] ?? null,
                    unitCost: $lot['unit_cost'] ?? $data->unitCost,
                );
            }

            return;
        }

        $this->createLot(
            product: $product,
            data: $data,
            ledger: $ledger,
            quantity: Money::decimal($data->quantity, 3),
            lotNumber: $data->lotNumber,
            expiryDate: $data->expiryDate,
            unitCost: $data->unitCost,
        );
    }

    public function consume(Product $product, RecordStockMovementData $data, StockLedger $ledger): void
    {
        if (! $product->requires_expiry) {
            return;
        }

        $quantity = Money::decimal($data->quantity, 3);

        $remaining = bccomp($quantity, '0', 3) < 0
            ? bcsub('0', $quantity, 3)
            : $quantity;

        if (bccomp($remaining, '0', 3) !== 1) {
            return;
        }

        $query = StockLot::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $data->warehouseId)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('received_at')
            ->orderBy('created_at')
            ->lockForUpdate();

        if (! $data->allowExpired) {
            $query->where(function (Builder $query): void {
                $query
                    ->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', today()->toDateString());
            });
        }

        $available = Money::decimal((string) $query->clone()->sum('quantity_remaining'), 3);

        if (bccomp($remaining, $available, 3) === 1) {
            throw new InsufficientStockException(
                $product->id,
                $data->warehouseId,
                $remaining,
                $available,
            );
        }

        foreach ($query->get() as $lot) {
            if (bccomp($remaining, '0', 3) !== 1) {
                break;
            }

            $lotQuantity = Money::decimal($lot->quantity_remaining, 3);

            $take = bccomp($lotQuantity, $remaining, 3) <= 0
                ? $lotQuantity
                : $remaining;

            $lot->quantity_remaining = Money::sub($lot->quantity_remaining, $take, 3);
            $lot->save();

            StockLotAllocation::create([
                'stock_lot_id' => $lot->id,
                'stock_ledger_id' => $ledger->id,
                'quantity' => $take,
            ]);

            $remaining = Money::sub($remaining, $take, 3);
        }

        if (bccomp($remaining, '0', 3) === 1) {
            throw new InsufficientStockException(
                $product->id,
                $data->warehouseId,
                $remaining,
                $available,
            );
        }
    }

    /**
     * @return array<int, array{lot_number: string|null, expiry_date: string|null, quantity: string, unit_cost: string}>
     */
    public function snapshotsForLedger(string $ledgerId): array
    {
        return StockLotAllocation::query()
            ->with('stockLot')
            ->where('stock_ledger_id', $ledgerId)
            ->get()
            ->map(fn (StockLotAllocation $allocation): array => [
                'lot_number' => $allocation->stockLot->lot_number,
                'expiry_date' => $allocation->stockLot->expiry_date?->toDateString(),
                'quantity' => Money::decimal($allocation->quantity, 3),
                'unit_cost' => (string) $allocation->stockLot->unit_cost,
            ])
            ->all();
    }

    private function createLot(
        Product $product,
        RecordStockMovementData $data,
        StockLedger $ledger,
        string $quantity,
        ?string $lotNumber,
        ?string $expiryDate,
        string $unitCost,
    ): StockLot {
        $receivedAt = Carbon::parse($data->receivedAt ?? today()->toDateString())->startOfDay();

        if ($expiryDate === null && $product->shelf_life_days !== null) {
            $expiryDate = $receivedAt->copy()->addDays($product->shelf_life_days)->toDateString();
        }

        if ($expiryDate === null) {
            throw new InvalidArgumentException(
                "Tanggal kedaluwarsa wajib diisi untuk produk {$product->name}."
            );
        }

        return StockLot::create([
            'product_id' => $product->id,
            'warehouse_id' => $data->warehouseId,
            'source_type' => $data->referenceType,
            'source_id' => $data->referenceId,
            'lot_number' => $lotNumber,
            'received_at' => $receivedAt->toDateString(),
            'expiry_date' => Carbon::parse($expiryDate)->toDateString(),
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost' => $unitCost,
            'notes' => $data->notes,
        ]);
    }
}
