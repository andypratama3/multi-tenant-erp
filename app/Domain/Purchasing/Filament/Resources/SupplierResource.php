<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Purchasing\Filament\Resources\SupplierResource\Pages;
use App\Domain\Purchasing\Models\Supplier;
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

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?string $modelLabel = 'Pemasok';

    protected static ?string $pluralModelLabel = 'Pemasok';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pemasok')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('contact_person')
                        ->label('Kontak Person')
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Telepon')
                        ->tel()
                        ->maxLength(50),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('tax_id')
                        ->label('NPWP')
                        ->maxLength(30),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Ketentuan Pembayaran')
                ->schema([
                    TextInput::make('payment_terms_days')
                        ->label('Termin (hari)')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(365)
                        ->default(30)
                        ->required(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')
                ->label('Kode')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label('Nama')
                ->searchable()
                ->sortable(),
            TextColumn::make('contact_person')
                ->label('Kontak'),
            TextColumn::make('phone')
                ->label('Telepon'),
            TextColumn::make('payment_terms_days')
                ->label('Termin (hari)'),
            IconColumn::make('is_active')
                ->label('Aktif')
                ->boolean(),
        ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pemasok')
            ->emptyStateDescription('Tambahkan pemasok untuk mulai membuat pesanan pembelian.')
            ->defaultSort('name');
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'email', 'phone'];
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
