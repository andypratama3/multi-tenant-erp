<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Actions\PostWarehouseTransferAction;
use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource\Pages;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseTransfer;
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

class WarehouseTransferResource extends Resource
{
    protected static ?string $model = WarehouseTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Transfer Gudang';

    protected static ?string $pluralModelLabel = 'Transfer Gudang';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer Gudang')
                ->schema([
                    Select::make('from_warehouse_id')
                        ->label('Gudang Asal')
                        ->required()
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all()),
                    Select::make('to_warehouse_id')
                        ->label('Gudang Tujuan')
                        ->required()
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->different('from_warehouse_id'),
                    DatePicker::make('transferred_at')->label('Tanggal Transfer')->required()->default(now()->toDateString()),
                    Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Transfer')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('product_id')
                                ->label('Produk')
                                ->required()
                                ->searchable()
                                ->options(fn (): array => Product::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all()),
                            TextInput::make('quantity')->label('Qty')->numeric()->required()->minValue(0.001),
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
            Section::make('Informasi Transfer')
                ->schema([
                    TextEntry::make('transfer_number')->label('No. Transfer'),
                    TextEntry::make('fromWarehouse.name')->label('Gudang Asal'),
                    TextEntry::make('toWarehouse.name')->label('Gudang Tujuan'),
                    TextEntry::make('transferred_at')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—'),
                ])
                ->columns(3),
            Section::make('Item')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('unit_cost')->label('Harga')->money('IDR'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transfer_number')->label('No. Transfer')->searchable()->sortable(),
            TextColumn::make('fromWarehouse.name')->label('Dari'),
            TextColumn::make('toWarehouse.name')->label('Ke'),
            TextColumn::make('transferred_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('items_count')->counts('items')->label('Item'),
        ])
            ->filters([
                SelectFilter::make('from_warehouse_id')->label('Gudang Asal')->relationship('fromWarehouse', 'name'),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'posted' => 'Posted']),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('post')
                    ->label('Posting')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('post')
                    ->visible(fn (WarehouseTransfer $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (WarehouseTransfer $record): void {
                        $postedBy = auth()->id();

                        try {
                            app(PostWarehouseTransferAction::class)->execute(
                                $record->id,
                                $postedBy !== null ? (string) $postedBy : null,
                            );
                            Notification::make()->title('Transfer berhasil diposting.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal posting transfer.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Belum ada transfer gudang')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['fromWarehouse', 'toWarehouse', 'items.product']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseTransfers::route('/'),
            'create' => Pages\CreateWarehouseTransfer::route('/create'),
            'view' => Pages\ViewWarehouseTransfer::route('/{record}'),
        ];
    }
}
