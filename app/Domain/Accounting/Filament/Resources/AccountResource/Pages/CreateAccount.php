<?php

namespace App\Domain\Accounting\Filament\Resources\AccountResource\Pages;

use App\Domain\Accounting\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
