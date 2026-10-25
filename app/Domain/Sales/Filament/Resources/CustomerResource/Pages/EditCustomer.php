<?php

namespace App\Domain\Sales\Filament\Resources\CustomerResource\Pages;

use App\Domain\Sales\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
