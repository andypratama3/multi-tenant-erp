<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Sales\Filament\Resources\CustomerResource\Pages;
use App\Domain\Sales\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pelanggan')
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
            Section::make('Ketentuan Penjualan')
                ->schema([
                    TextInput::make('payment_terms_days')
                        ->label('Termin (hari)')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(365)
                        ->default(30)
                        ->required(),
                    TextInput::make('credit_limit')
                        ->label('Batas Kredit')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->default(0),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(3),
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
            TextColumn::make('phone')
                ->label('Telepon'),
            TextColumn::make('email')
                ->label('Email'),
            TextColumn::make('payment_terms_days')
                ->label('Termin (hari)'),
            TextColumn::make('credit_limit')
                ->label('Batas Kredit')
                ->money('IDR'),
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
            ->emptyStateHeading('Belum ada pelanggan')
            ->emptyStateDescription('Tambahkan pelanggan untuk mulai membuat pesanan penjualan.')
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
