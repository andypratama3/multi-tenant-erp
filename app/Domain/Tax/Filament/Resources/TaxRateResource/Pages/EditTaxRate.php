<?php

namespace App\Domain\Tax\Filament\Resources\TaxRateResource\Pages;

use App\Domain\Tax\Filament\Resources\TaxRateResource;
use Filament\Resources\Pages\EditRecord;

class EditTaxRate extends EditRecord
{
    protected static string $resource = TaxRateResource::class;
}
