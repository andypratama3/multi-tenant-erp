<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Actions\PostStockAdjustmentAction;
use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource\Pages;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Stock Opname')
                ->schema([
                    Select::make('warehouse_id')
                        ->label('Gudang')
                        ->required()
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all()),
                    DatePicker::make('adjusted_at')->label('Tanggal Opname')->required()->default(now()->toDateString()),
                    TextInput::make('reason')->label('Alasan')->required()->maxLength(255),
                    Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Opname')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('product_id')
                                ->label('Produk')
                                ->required()
                                ->searchable()
                                ->options(fn (): array => Product::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all()),
                            TextInput::make('counted_quantity')->label('Hasil Hitung')->numeric()->required()->minValue(0),
                            TextInput::make('unit_cost')->label('Harga Satuan')->numeric()->required()->minValue(0),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->addActionLabel('Tambah Item'),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Opname')
                ->schema([
                    TextEntry::make('adjustment_number')->label('No. Opname'),
                    TextEntry::make('warehouse.name')->label('Gudang'),
                    TextEntry::make('adjusted_at')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('reason')->label('Alasan'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—'),
                ])
                ->columns(3),
            Section::make('Item')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('system_quantity')->label('Sistem'),
                            TextEntry::make('counted_quantity')->label('Hitungan'),
                            TextEntry::make('difference_quantity')->label('Selisih'),
                            TextEntry::make('unit_cost')->label('Harga')->money('IDR'),
                        ])
                        ->columns(5),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('adjustment_number')->label('No. Opname')->searchable()->sortable(),
            TextColumn::make('warehouse.name')->label('Gudang'),
            TextColumn::make('adjusted_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('reason')->label('Alasan')->limit(40),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('items_count')->counts('items')->label('Item'),
        ])
            ->filters([
                SelectFilter::make('warehouse_id')->label('Gudang')->relationship('warehouse', 'name'),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'posted' => 'Posted']),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('post')
                    ->label('Posting')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('post')
                    ->visible(fn (StockAdjustment $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (StockAdjustment $record): void {
                        $postedBy = auth()->id();

                        try {
                            app(PostStockAdjustmentAction::class)->execute(
                                $record->id,
                                $postedBy !== null ? (string) $postedBy : null,
                            );
                            Notification::make()->title('Stock opname berhasil diposting.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal posting stock opname.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Belum ada stock opname')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['warehouse', 'items.product']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
        ];
    }
}
