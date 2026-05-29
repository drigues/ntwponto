<?php

namespace App\Filament\Resources\MarcacaoResource\Pages;

use App\Enums\TipoMarcacao;
use App\Filament\Resources\MarcacaoResource;
use App\Services\MarcacaoService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMarcacao extends CreateRecord
{
    protected static string $resource = MarcacaoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $dataHora = Carbon::parse($data['data_hora']);
        $data['data_civil'] = $dataHora->copy()->timezone('Europe/Lisbon')->toDateString();
        $data['gps_indisponivel'] = true;
        $data['editado_por'] = auth()->id();
        $data['editado_em'] = now();

        $tipo = TipoMarcacao::from($data['tipo']);
        $service = app(MarcacaoService::class);

        if (! $service->validarSequenciaDoDia((int) $data['user_id'], $data['data_civil'], $tipo)) {
            throw ValidationException::withMessages([
                'data.tipo' => 'Esta marcação quebraria a sequência válida do dia (entrada → início pausa → fim pausa → saída).',
            ]);
        }

        return $data;
    }
}
