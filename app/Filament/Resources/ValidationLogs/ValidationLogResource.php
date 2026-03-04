<?php

namespace App\Filament\Resources\ValidationLogs;

use App\Filament\Resources\ValidationLogs\Pages\ManageValidationLogs;
use App\Models\ValidationLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                //
            ])
            ->recordActions([
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
