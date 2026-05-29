<?php

namespace App\Filament\Resources\MarcacaoResource\Pages;

use App\Filament\Resources\MarcacaoResource;
use Carbon\Carbon;
use Filament\Resources\Pages\EditRecord;

class EditMarcacao extends EditRecord
{
    protected static string $resource = MarcacaoResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['data_hora'])) {
            $dataHora = Carbon::parse($data['data_hora']);
            $data['data_civil'] = $dataHora->copy()->timezone('Europe/Lisbon')->toDateString();
        }

        $data['editado_por'] = auth()->id();
        $data['editado_em'] = now();

        return $data;
    }
}
