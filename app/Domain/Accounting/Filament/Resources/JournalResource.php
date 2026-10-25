<?php

namespace App\Domain\Accounting\Filament\Resources;

use App\Domain\Accounting\Filament\Resources\JournalResource\Pages;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Jurnal';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('posted_at')
                ->label('Tanggal Jurnal')
                ->required()
                ->default(now()->toDateString()),
            Textarea::make('description')
                ->label('Deskripsi')
                ->required()
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('lines')
                ->label('Baris Jurnal')
                ->schema([
                    Select::make('account_id')
                        ->label('Akun')
                        ->required()
                        ->searchable()
                        ->options(fn (): array => Account::query()
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Account $account): array => [
                                $account->id => $account->code.' — '.$account->name,
                            ])
                            ->all()),
                    Select::make('type')
                        ->label('Posisi')
                        ->required()
                        ->options([
                            'debit' => 'Debit',
                            'credit' => 'Kredit',
                        ]),
                    TextInput::make('amount')
                        ->label('Nominal')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->prefix('Rp'),
                    TextInput::make('description')
                        ->label('Keterangan'),
                ])
                ->columns(4)
                ->minItems(2)
                ->defaultItems(2)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Jurnal')
                ->schema([
                    TextEntry::make('journal_number')->label('No. Jurnal'),
                    TextEntry::make('posted_at')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->formatStateUsing(fn (string $state): string => $state === 'posted' ? 'Diposting' : 'Draft')
                        ->color(fn (string $state): string => $state === 'posted' ? 'success' : 'gray'),
                    TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Baris Jurnal')
                ->schema([
                    RepeatableEntry::make('lines')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('account.code')->label('Kode Akun'),
                            TextEntry::make('account.name')->label('Nama Akun'),
                            TextEntry::make('type')->label('Posisi')->formatStateUsing(fn (string $state): string => $state === 'debit' ? 'Debit' : 'Kredit'),
                            TextEntry::make('amount')->label('Nominal')->money('IDR'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('—'),
                        ])
                        ->columns(5),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('journal_number')->label('No. Jurnal')->searchable()->sortable(),
            TextColumn::make('posted_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('description')->label('Deskripsi')->limit(50),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => $state === 'posted' ? 'Diposting' : 'Draft')
                ->color(fn (string $state): string => $state === 'posted' ? 'success' : 'gray'),
            TextColumn::make('lines_count')
                ->counts('lines')
                ->label('Baris'),
        ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view' => Pages\ViewJournal::route('/{record}'),
        ];
    }
}
