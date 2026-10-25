<?php

namespace App\Domain\Accounting\Filament\Resources;

use App\Domain\Accounting\Filament\Resources\AccountResource\Pages;
use App\Domain\Accounting\Models\Account;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('name')->required()->maxLength(150),
            Select::make('type')
                ->options([
                    'asset' => 'Asset',
                    'liability' => 'Liability',
                    'equity' => 'Equity',
                    'revenue' => 'Revenue',
                    'expense' => 'Expense',
                ])
                ->required(),
            Select::make('normal_balance')
                ->options([
                    'debit' => 'Debit',
                    'credit' => 'Credit',
                ])
                ->required(),
            Select::make('parent_id')
                ->options(Account::query()->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            Toggle::make('is_leaf')->default(true),
            Toggle::make('is_active')->default(true),
            Textarea::make('description')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'asset' => 'info',
                    'liability' => 'warning',
                    'equity' => 'success',
                    'revenue' => 'primary',
                    'expense' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('normal_balance'),
            IconColumn::make('is_leaf')->boolean(),
            IconColumn::make('is_active')->boolean(),
        ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
