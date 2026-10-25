<?php

namespace App\Filament\Concerns;

trait AuthorizesResourceAccess
{
    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canViewAny(), 403);
    }
}
