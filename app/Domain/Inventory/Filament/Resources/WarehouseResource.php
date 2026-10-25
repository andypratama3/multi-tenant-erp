<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\WarehouseResource\Pages;
use App\Domain\Inventory\Models\Warehouse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Gudang';

    protected static ?string $pluralModelLabel = 'Gudang';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Gudang')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Gudang')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama Gudang')
                        ->required()
                        ->maxLength(100),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3)
                        ->columnSpanFull(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
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
            ->emptyStateHeading('Belum ada gudang')
            ->emptyStateDescription('Tambahkan gudang untuk mencatat stok per lokasi.')
            ->defaultSort('name');
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
