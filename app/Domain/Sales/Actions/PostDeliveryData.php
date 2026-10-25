<?php

namespace App\Domain\Sales\Actions;

final class PostDeliveryData
{
    public function __construct(
        public readonly string $deliveryId,
        public readonly bool $postJournal = false,
    ) {}
}
