<?php

namespace App\Filament\Resources;

use App\Enums\TipoMarcacao;
use App\Enums\UserRole;
use App\Filament\Resources\MarcacaoResource\Pages\CreateMarcacao;
use App\Filament\Resources\MarcacaoResource\Pages\EditMarcacao;
use App\Filament\Resources\MarcacaoResource\Pages\ListMarcacoes;
use App\Models\Marcacao;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarcacaoResource extends Resource
{
    protected static ?string $model = Marcacao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Marcações';

    protected static ?string $modelLabel = 'Marcação';

    protected static ?string $pluralModelLabel = 'Marcações';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Funcionário')
                    ->options(
                        User::where('role', UserRole::Funcionario)
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),
                Select::make('tipo')
                    ->label('Tipo')
                    ->options(collect(TipoMarcacao::cases())->mapWithKeys(
                        fn (TipoMarcacao $t) => [$t->value => match ($t) {
                            TipoMarcacao::Entrada => 'Entrada',
                            TipoMarcacao::InicioPausa => 'Início pausa',
                            TipoMarcacao::FimPausa => 'Fim pausa',
                            TipoMarcacao::Saida => 'Saída',
                        }]
                    ))
                    ->required(),
                DateTimePicker::make('data_hora')
                    ->label('Data e hora')
                    ->required()
                    ->seconds(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Funcionário')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (TipoMarcacao $state) => match ($state) {
                        TipoMarcacao::Entrada => 'Entrada',
                        TipoMarcacao::InicioPausa => 'Início pausa',
                        TipoMarcacao::FimPausa => 'Fim pausa',
                        TipoMarcacao::Saida => 'Saída',
                    })
                    ->sortable(),
                TextColumn::make('data_hora')
                    ->label('Data e hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('data_civil')
                    ->label('Dia')
                    ->sortable(),
                IconColumn::make('editado_por')
                    ->label('Editado')
                    ->boolean()
                    ->getStateUsing(fn (Marcacao $record) => $record->editado_por !== null)
                    ->tooltip(fn (Marcacao $record) => $record->editado_por
                        ? 'Editado por admin em '.$record->editado_em?->format('d/m/Y H:i')
                        : null
                    ),
            ])
            ->defaultSort('data_hora', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Funcionário')
                    ->options(
                        User::where('role', UserRole::Funcionario)
                            ->pluck('name', 'id')
                    ),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(collect(TipoMarcacao::cases())->mapWithKeys(
                        fn (TipoMarcacao $t) => [$t->value => match ($t) {
                            TipoMarcacao::Entrada => 'Entrada',
                            TipoMarcacao::InicioPausa => 'Início pausa',
                            TipoMarcacao::FimPausa => 'Fim pausa',
                            TipoMarcacao::Saida => 'Saída',
                        }]
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('delete')
                    ->label('Eliminar')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Marcacao $record) => $record->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarcacoes::route('/'),
            'create' => CreateMarcacao::route('/create'),
            'edit' => EditMarcacao::route('/{record}/edit'),
        ];
    }
}
