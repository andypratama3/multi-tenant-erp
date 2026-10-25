<?php

declare(strict_types=1);

namespace App\Filament\Support;

final class DocumentFormData
{
    /**
     * @return array<int, array{product_id: string, quantity_ordered: string, unit_price: string, tax_rate: string}>
     */
    public static function purchaseOrderItems(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $mapped[] = [
                'product_id' => self::value($item['product_id'] ?? null),
                'quantity_ordered' => self::value($item['quantity_ordered'] ?? null),
                'unit_price' => self::value($item['unit_price'] ?? null),
                'tax_rate' => self::value($item['tax_rate'] ?? null),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{product_id: string, quantity_ordered: string, unit_price: string, discount_rate: string, tax_rate: string}>
     */
    public static function salesOrderItems(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $mapped[] = [
                'product_id' => self::value($item['product_id'] ?? null),
                'quantity_ordered' => self::value($item['quantity_ordered'] ?? null),
                'unit_price' => self::value($item['unit_price'] ?? null),
                'discount_rate' => self::value($item['discount_rate'] ?? null),
                'tax_rate' => self::value($item['tax_rate'] ?? null),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{sales_order_item_id: string|null, product_id: string, quantity: string, unit_price: string, discount_rate: string, tax_rate: string}>
     */
    public static function invoiceItems(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $mapped[] = [
                'sales_order_item_id' => isset($item['sales_order_item_id']) && is_scalar($item['sales_order_item_id']) && (string) $item['sales_order_item_id'] !== ''
                    ? (string) $item['sales_order_item_id']
                    : null,
                'product_id' => self::value($item['product_id'] ?? null),
                'quantity' => self::value($item['quantity'] ?? null),
                'unit_price' => self::value($item['unit_price'] ?? null),
                'discount_rate' => self::value($item['discount_rate'] ?? null),
                'tax_rate' => self::value($item['tax_rate'] ?? null),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{purchase_order_item_id: string, quantity_received: string, unit_cost: string}>
     */
    public static function receiptItems(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $mapped[] = [
                'purchase_order_item_id' => self::value($item['purchase_order_item_id'] ?? null),
                'quantity_received' => self::value($item['quantity_received'] ?? null),
                'unit_cost' => self::value($item['unit_cost'] ?? null),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{sales_order_item_id: string, quantity_delivered: string}>
     */
    public static function deliveryItems(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $mapped[] = [
                'sales_order_item_id' => self::value($item['sales_order_item_id'] ?? null),
                'quantity_delivered' => self::value($item['quantity_delivered'] ?? null),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array{account_id: string, type: string, amount: string, description: string|null}>
     */
    public static function journalLines(mixed $items): array
    {
        $mapped = [];

        foreach (self::rows($items) as $item) {
            $description = $item['description'] ?? null;

            $mapped[] = [
                'account_id' => self::value($item['account_id'] ?? null),
                'type' => self::value($item['type'] ?? null),
                'amount' => self::value($item['amount'] ?? null),
                'description' => is_scalar($description) ? (string) $description : null,
            ];
        }

        return $mapped;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function rows(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, 'is_array'));
    }

    private static function value(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '0';
    }
}
