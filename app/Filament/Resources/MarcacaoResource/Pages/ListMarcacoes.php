<?php

namespace App\Filament\Resources\MarcacaoResource\Pages;

use App\Filament\Resources\MarcacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarcacoes extends ListRecords
{
    protected static string $resource = MarcacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
