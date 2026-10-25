<?php

namespace App\Domain\Tax\Filament\Resources;

use App\Domain\Tax\Filament\Resources\TaxRateResource\Pages;
use App\Domain\Tax\Models\TaxRate;
use Filament\Actions\CreateAction;
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

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(30)->unique(ignoreRecord: true),
            TextInput::make('name')->required()->maxLength(150),
            Select::make('type')
                ->options([
                    'ppn' => 'PPN',
                    'pph_21' => 'PPh 21',
                    'pph_22' => 'PPh 22',
                    'pph_23' => 'PPh 23',
                    'pph_final' => 'PPh Final',
                    'other' => 'Other',
                ])
                ->required(),
            TextInput::make('rate')->numeric()->required()->suffix('%'),
            Toggle::make('is_active')->default(true),
            Textarea::make('description')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'ppn' => 'info',
                    'pph_21' => 'warning',
                    'pph_22' => 'warning',
                    'pph_23' => 'warning',
                    'pph_final' => 'primary',
                    'other' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('rate')->suffix('%'),
            IconColumn::make('is_active')->boolean(),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()])
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
