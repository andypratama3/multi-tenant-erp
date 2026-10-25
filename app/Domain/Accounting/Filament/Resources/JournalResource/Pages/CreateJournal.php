<?php

namespace App\Domain\Accounting\Filament\Resources\JournalResource\Pages;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Filament\Resources\JournalResource;
use App\Filament\Support\DocumentFormData;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(PostJournalAction::class)->execute(new PostJournalData(
                postedAt: (string) $data['posted_at'],
                description: (string) $data['description'],
                lines: DocumentFormData::journalLines($data['lines'] ?? []),
                createdBy: $createdBy !== null ? (string) $createdBy : null,
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Jurnal gagal diposting.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jurnal berhasil diposting.';
    }
}
