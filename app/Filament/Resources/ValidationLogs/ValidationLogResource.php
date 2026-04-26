<?php

namespace App\Filament\Resources\ValidationLogs;

use App\Filament\Resources\ValidationLogs\Pages\ManageValidationLogs;
use App\Models\ValidationLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ValidationLogResource extends Resource
{
    protected static ?string $model = ValidationLog::class;
    protected static ?string $modelLabel = 'validación';
    protected static ?string $pluralModelLabel= 'validaciones';
    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('institution_id')
                    ->relationship('institution', 'name')
                    ->required(),
                TextInput::make('document_number'),
                TextInput::make('similarity')
                    ->numeric(),
                Toggle::make('matched')
                    ->required(),
                DateTimePicker::make('validated_at')
                    ->required(),
                Textarea::make('response')
                    ->label('Response (JSON)')
                    ->rows(12)
                    ->columnSpanFull()
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return null;
                        }

                        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    })
                    ->dehydrateStateUsing(function ($state) {
                        if (blank($state)) {
                            return null;
                        }

                        if (is_array($state)) {
                            return $state;
                        }

                        $decoded = json_decode((string) $state, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $decoded;
                        }

                        return ['raw' => (string) $state];
                    })
                    ->helperText('Puedes pegar JSON válido o texto simple.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                TextColumn::make('institution.name')
                    ->searchable(),
                TextColumn::make('document_number')
                    ->searchable(),
                TextColumn::make('similarity')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('matched')
                    ->boolean(),
                TextColumn::make('validated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('response_preview')
                    ->label('Response')
                    ->state(function (ValidationLog $record): ?string {
                        if (blank($record->response)) {
                            return null;
                        }

                        return json_encode($record->response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    })
                    ->limit(80)
                    ->wrap()
                    ->tooltip(fn (?string $state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('institution_id')
                    ->relationship('institution', 'name')
                    ->label('Institución'),
            ])
            ->recordActions([
                Action::make('view_response')
                    ->label('Ver JSON')
                    ->icon('heroicon-m-code-bracket-square')
                    ->color('info')
                    ->visible(fn (ValidationLog $record): bool => !blank($record->response))
                    ->modalHeading('Response de validación')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (ValidationLog $record) => view('filament.validation-logs.response-modal', [
                        'response' => $record->response,
                    ]))
                    ->action(fn () => null),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageValidationLogs::route('/'),
        ];
    }
}
