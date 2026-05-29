<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\FuncionarioResource\Pages\CreateFuncionario;
use App\Filament\Resources\FuncionarioResource\Pages\EditFuncionario;
use App\Filament\Resources\FuncionarioResource\Pages\ListFuncionarios;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FuncionarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Funcionários';

    protected static ?string $modelLabel = 'Funcionário';

    protected static ?string $pluralModelLabel = 'Funcionários';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('cargo')
                    ->label('Cargo')
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->delete()),
                Action::make('redefinir_password')
                    ->label('Redefinir password')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'password' => Str::random(16),
                            'must_change_password' => true,
                        ]);
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Funcionario);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFuncionarios::route('/'),
            'create' => CreateFuncionario::route('/create'),
            'edit' => EditFuncionario::route('/{record}/edit'),
        ];
    }
}
