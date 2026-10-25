<?php

namespace App\Domain\Tax\Filament\Resources\TaxRateResource\Pages;

use App\Domain\Tax\Filament\Resources\TaxRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxRate extends CreateRecord
{
    protected static string $resource = TaxRateResource::class;
}
