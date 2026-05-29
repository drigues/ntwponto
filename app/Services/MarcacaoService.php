<?php

namespace App\Services;

use App\Enums\TipoMarcacao;
use App\Exceptions\SequenciaInvalidaException;
use App\Models\Marcacao;
use App\Models\User;
use Carbon\CarbonImmutable;

class MarcacaoService
{
    private const SEQUENCIA = [
        null => TipoMarcacao::Entrada,
        TipoMarcacao::Entrada->value => TipoMarcacao::InicioPausa,
        TipoMarcacao::InicioPausa->value => TipoMarcacao::FimPausa,
        TipoMarcacao::FimPausa->value => TipoMarcacao::Saida,
    ];

    public function registar(
        User $user,
        TipoMarcacao $tipo,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $detalhes = null,
        ?string $fotoPath = null,
    ): Marcacao {
        $agora = CarbonImmutable::now('UTC');
        $dataCivil = $agora->setTimezone('Europe/Lisbon')->toDateString();

        $esperado = $this->proximoTipoEsperado($user, $agora);

        if ($esperado !== $tipo) {
            throw new SequenciaInvalidaException(
                "Sequência inválida: esperado '{$esperado?->value}', recebido '{$tipo->value}'."
            );
        }

        $gpsIndisponivel = $latitude === null || $longitude === null;

        return Marcacao::create([
            'user_id' => $user->id,
            'tipo' => $tipo,
            'data_hora' => $agora,
            'data_civil' => $dataCivil,
            'latitude' => $gpsIndisponivel ? null : $latitude,
            'longitude' => $gpsIndisponivel ? null : $longitude,
            'gps_indisponivel' => $gpsIndisponivel,
            'detalhes' => $detalhes,
            'foto_path' => $fotoPath,
        ]);
    }

    /**
     * Validates whether adding a given tipo to a day's marcacoes would result in a valid sequence.
     * Used by admin when creating/editing marcacoes.
     */
    public function validarSequenciaDoDia(int $userId, string $dataCivil, TipoMarcacao $tipo, ?int $excludeId = null): bool
    {
        $existentes = Marcacao::where('user_id', $userId)
            ->where('data_civil', $dataCivil)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('data_hora')
            ->pluck('tipo')
            ->map(fn (TipoMarcacao $t) => $t->value)
            ->toArray();

        $existentes[] = $tipo->value;
        sort($existentes);

        $sequenciaCompleta = [
            TipoMarcacao::Entrada->value,
            TipoMarcacao::InicioPausa->value,
            TipoMarcacao::FimPausa->value,
            TipoMarcacao::Saida->value,
        ];

        // The existing types must form a valid prefix of the complete sequence
        $expectedPrefix = array_slice($sequenciaCompleta, 0, count($existentes));

        return $existentes === $expectedPrefix;
    }

    public function proximoTipoEsperado(User $user, CarbonImmutable $momento): ?TipoMarcacao
    {
        $dataCivil = $momento->setTimezone('Europe/Lisbon')->toDateString();

        $ultimaMarcacao = Marcacao::where('user_id', $user->id)
            ->where('data_civil', $dataCivil)
            ->orderByDesc('data_hora')
            ->first();

        $ultimoTipo = $ultimaMarcacao?->tipo?->value;

        return self::SEQUENCIA[$ultimoTipo] ?? null;
    }
}
