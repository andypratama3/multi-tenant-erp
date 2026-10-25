<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\ProductResource\Pages;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Inventory\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Produk')
                ->schema([
                    TextInput::make('sku')
                        ->label('SKU')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama Produk')
                        ->required()
                        ->maxLength(150),
                    Select::make('product_category_id')
                        ->label('Kategori')
                        ->options(fn (): array => ProductCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->nullable(),
                    Select::make('unit_id')
                        ->label('Satuan')
                        ->options(fn (): array => Unit::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('barcode')
                        ->label('Barcode')
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Harga & Persediaan')
                ->schema([
                    TextInput::make('cost_price')
                        ->label('Harga Pokok')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('selling_price')
                        ->label('Harga Jual')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('min_stock')
                        ->label('Stok Minimum')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('reorder_point')
                        ->label('Titik Pesan Ulang')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns(2),
            Section::make('Status')
                ->schema([
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Toggle::make('is_stockable')->label('Dikelola Stok')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->placeholder('—'),
                TextColumn::make('unit.symbol')
                    ->label('Satuan'),
                TextColumn::make('cost_price')
                    ->label('Harga Pokok')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('product_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')->label('Status Aktif'),
                TernaryFilter::make('is_stockable')->label('Dikelola Stok'),
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
            ->emptyStateHeading('Belum ada produk')
            ->emptyStateDescription('Tambahkan produk pertama untuk mulai mencatat persediaan dan penjualan.')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('name');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'unit']);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['sku', 'name', 'barcode'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
