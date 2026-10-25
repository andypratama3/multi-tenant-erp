<?php

namespace App\Domain\Accounting\Filament\Resources\AccountResource\Pages;

use App\Domain\Accounting\Filament\Resources\AccountResource;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;
}
