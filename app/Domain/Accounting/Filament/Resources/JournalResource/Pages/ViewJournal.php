<?php

namespace App\Domain\Accounting\Filament\Resources\JournalResource\Pages;

use App\Domain\Accounting\Filament\Resources\JournalResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['lines.account', 'activities.causer']);
    }
}
