<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\ProductCategoryResource\Pages;
use App\Domain\Inventory\Models\ProductCategory;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Kategori Produk';

    protected static ?string $pluralModelLabel = 'Kategori Produk';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Kategori')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, Set $set): void {
                            $set('slug', Str::slug($state));
                        }),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Select::make('parent_id')
                        ->label('Kategori Induk')
                        ->options(fn (): array => ProductCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->nullable(),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
            Section::make('Default Kedaluwarsa')
                ->description('Nilai ini dipakai otomatis saat produk baru memilih kategori ini.')
                ->schema([
                    Toggle::make('requires_expiry')
                        ->label('Produk kategori ini lacak kedaluwarsa')
                        ->default(false)
                        ->live(),
                    TextInput::make('default_shelf_life_days')
                        ->label('Umur Simpan Default (hari)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->visible(fn (Get $get): bool => (bool) $get('requires_expiry')),
                    TextInput::make('default_expiry_alert_days')
                        ->label('Peringatan Default (hari)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(7)
                        ->visible(fn (Get $get): bool => (bool) $get('requires_expiry')),
                ])
                ->columns(3),
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
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Kategori Induk')
                    ->placeholder('—'),
                IconColumn::make('requires_expiry')
                    ->label('Batch')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
                TernaryFilter::make('requires_expiry')->label('Lacak Kedaluwarsa'),
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
            ->emptyStateHeading('Belum ada kategori produk')
            ->emptyStateDescription('Buat kategori untuk mengelompokkan produk.')
            ->defaultSort('name');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent']);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
