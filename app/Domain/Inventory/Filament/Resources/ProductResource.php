<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\ProductResource\Pages;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Inventory\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->maxLength(100),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->columnSpanFull(),
            Select::make('product_category_id')
                ->label('Category')
                ->options(fn () => ProductCategory::pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            Select::make('unit_id')
                ->label('Unit')
                ->options(fn () => Unit::pluck('symbol', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('cost_price')
                ->numeric()
                ->required()
                ->default(0),
            TextInput::make('selling_price')
                ->numeric()
                ->required()
                ->default(0),
            TextInput::make('barcode')
                ->maxLength(100),
            Toggle::make('is_active')
                ->default(true),
            Toggle::make('is_stockable')
                ->default(true),
            TextInput::make('min_stock')
                ->numeric()
                ->default(0),
            TextInput::make('reorder_point')
                ->numeric()
                ->default(0),
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('unit.symbol')
                    ->label('Unit'),
                TextColumn::make('cost_price')
                    ->label('Cost Price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /** @return array<string, PageRegistration> */
    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'unit']);
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
