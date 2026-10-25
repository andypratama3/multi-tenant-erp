<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\UnitResource\Pages;
use App\Domain\Inventory\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Satuan';

    protected static ?string $pluralModelLabel = 'Satuan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Satuan')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Satuan')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('symbol')
                        ->label('Simbol')
                        ->required()
                        ->maxLength(10)
                        ->unique(ignoreRecord: true),
                    Toggle::make('is_base')->label('Satuan Dasar')->default(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol')
                    ->label('Simbol')
                    ->searchable(),
                IconColumn::make('is_base')
                    ->label('Satuan Dasar')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_base')->label('Satuan Dasar'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada satuan')
            ->emptyStateDescription('Tambahkan satuan seperti pcs, kg, atau liter.')
            ->defaultSort('name');
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'symbol'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
