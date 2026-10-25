<?php

namespace App\Domain\Sales\Filament\Resources\CustomerResource\Pages;

use App\Domain\Sales\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
