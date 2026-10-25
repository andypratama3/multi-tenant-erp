<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

final class DocumentTimeline
{
    public static function make(): Section
    {
        return Section::make('Riwayat Aktivitas')
            ->schema([
                RepeatableEntry::make('activities')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('event')
                            ->label('Aksi')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'created' => 'Dibuat',
                                'updated' => 'Diperbarui',
                                'deleted' => 'Dihapus',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('description')->label('Keterangan'),
                        TextEntry::make('causer.name')->label('Oleh')->placeholder('Sistem'),
                    ])
                    ->columns(4),
            ])
            ->collapsible()
            ->collapsed()
            ->columnSpanFull();
    }
}
