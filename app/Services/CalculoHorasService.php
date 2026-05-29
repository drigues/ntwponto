<?php

namespace App\Services;

use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class CalculoHorasService
{
    /**
     * Returns total seconds worked in a day, or null if the day is incomplete.
     */
    public function horasDoDia(User $user, CarbonImmutable $dia): ?int
    {
        $marcacoes = $this->marcacoesDoDia($user, $dia);

        $entrada = $marcacoes->firstWhere('tipo', TipoMarcacao::Entrada);
        $inicioPausa = $marcacoes->firstWhere('tipo', TipoMarcacao::InicioPausa);
        $fimPausa = $marcacoes->firstWhere('tipo', TipoMarcacao::FimPausa);
        $saida = $marcacoes->firstWhere('tipo', TipoMarcacao::Saida);

        if (! $entrada || ! $inicioPausa || ! $fimPausa || ! $saida) {
            return null;
        }

        $totalSegundos = $entrada->data_hora->diffInSeconds($saida->data_hora, absolute: true);
        $pausaSegundos = $inicioPausa->data_hora->diffInSeconds($fimPausa->data_hora, absolute: true);

        return (int) ($totalSegundos - $pausaSegundos);
    }

    public function temInconsistencias(User $user, CarbonImmutable $dia): bool
    {
        $marcacoes = $this->marcacoesDoDia($user, $dia);

        $tipos = $marcacoes->pluck('tipo')->map(fn (TipoMarcacao $t) => $t->value)->toArray();

        $sequenciaCompleta = [
            TipoMarcacao::Entrada->value,
            TipoMarcacao::InicioPausa->value,
            TipoMarcacao::FimPausa->value,
            TipoMarcacao::Saida->value,
        ];

        // If empty, no inconsistency
        if (empty($tipos)) {
            return false;
        }

        // Check if types form a valid prefix of the complete sequence
        $expectedPrefix = array_slice($sequenciaCompleta, 0, count($tipos));

        return $tipos !== $expectedPrefix;
    }

    /**
     * @return Collection<int, Marcacao>
     */
    private function marcacoesDoDia(User $user, CarbonImmutable $dia): Collection
    {
        return Marcacao::where('user_id', $user->id)
            ->where('data_civil', $dia->toDateString())
            ->orderBy('data_hora')
            ->get();
    }
}
