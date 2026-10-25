<?php

namespace App\Domain\Tax\Filament\Resources;

use App\Domain\Tax\Filament\Resources\TaxRateResource\Pages;
use App\Domain\Tax\Models\TaxRate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\UnitEnum|null $navigationGroup = 'Pajak';

    protected static ?string $modelLabel = 'Tarif Pajak';

    protected static ?string $pluralModelLabel = 'Tarif Pajak';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Kode')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama')->required()->maxLength(100),
            Select::make('type')
                ->options([
                    'ppn' => 'PPN',
                    'pph_21' => 'PPh 21',
                    'pph_22' => 'PPh 22',
                    'pph_23' => 'PPh 23',
                    'pph_final' => 'PPh Final',
                    'other' => 'Lainnya',
                ])
                ->required(),
            TextInput::make('rate')->label('Tarif')->numeric()->required()->minValue(0)->maxValue(100)->suffix('%'),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('description')->label('Deskripsi')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kode')->searchable()->sortable(),
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('type')->label('Jenis')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'ppn' => 'info',
                    'pph_21' => 'warning',
                    'pph_22' => 'warning',
                    'pph_23' => 'warning',
                    'pph_final' => 'primary',
                    'other' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('rate')->label('Tarif')->suffix('%'),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRates::route('/'),
            'create' => Pages\CreateTaxRate::route('/create'),
            'edit' => Pages\EditTaxRate::route('/{record}/edit'),
        ];
    }
}
