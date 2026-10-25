<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Purchasing\Filament\Resources\SupplierResource\Pages;
use App\Domain\Purchasing\Models\Supplier;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('name')->required()->maxLength(150),
            TextInput::make('contact_person'),
            TextInput::make('phone'),
            TextInput::make('email')->email(),
            Textarea::make('address')->rows(3),
            TextInput::make('tax_id')->label('NPWP')->maxLength(30),
            TextInput::make('payment_terms_days')->numeric()->default(30)->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('contact_person'),
            TextColumn::make('phone'),
            TextColumn::make('payment_terms_days')->label('Terms (days)'),
            IconColumn::make('is_active')->boolean(),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
