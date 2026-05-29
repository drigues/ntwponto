<?php

namespace App\Services;

use App\DTOs\DiaRelatorio;
use App\DTOs\RelatorioIndividual;
use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RelatorioIndividualService
{
    public function gerar(User $user, CarbonImmutable $de, CarbonImmutable $ate): RelatorioIndividual
    {
        $marcacoes = Marcacao::with('editor')
            ->where('user_id', $user->id)
            ->whereBetween('data_civil', [$de->toDateString(), $ate->toDateString()])
            ->orderBy('data_hora')
            ->get();

        $porDia = $marcacoes->groupBy('data_civil');

        $sequenciaCompleta = [
            TipoMarcacao::Entrada->value,
            TipoMarcacao::InicioPausa->value,
            TipoMarcacao::FimPausa->value,
            TipoMarcacao::Saida->value,
        ];

        /** @var Collection<int, DiaRelatorio> $dias */
        $dias = $porDia->map(function (Collection $marcacoesDia, string $data) use ($sequenciaCompleta): DiaRelatorio {
            $tipos = $marcacoesDia->pluck('tipo')->map(fn (TipoMarcacao $t) => $t->value)->toArray();

            $completo = $tipos === $sequenciaCompleta;

            $expectedPrefix = array_slice($sequenciaCompleta, 0, count($tipos));
            $inconsistencia = ! empty($tipos) && $tipos !== $expectedPrefix;

            $horasSegundos = null;
            if ($completo) {
                $entrada = $marcacoesDia->firstWhere('tipo', TipoMarcacao::Entrada);
                $inicioPausa = $marcacoesDia->firstWhere('tipo', TipoMarcacao::InicioPausa);
                $fimPausa = $marcacoesDia->firstWhere('tipo', TipoMarcacao::FimPausa);
                $saida = $marcacoesDia->firstWhere('tipo', TipoMarcacao::Saida);

                if ($entrada && $inicioPausa && $fimPausa && $saida) {
                    $total = $entrada->data_hora->diffInSeconds($saida->data_hora, absolute: true);
                    $pausa = $inicioPausa->data_hora->diffInSeconds($fimPausa->data_hora, absolute: true);
                    $horasSegundos = (int) ($total - $pausa);
                }
            }

            $emCurso = ! $completo && ! $inconsistencia;
            $editado = $marcacoesDia->contains(fn (Marcacao $m) => $m->editado_por !== null);

            return new DiaRelatorio(
                data: $data,
                marcacoes: $marcacoesDia,
                horasSegundos: $horasSegundos,
                emCurso: $emCurso,
                inconsistencia: $inconsistencia,
                editado: $editado,
            );
        })->sortKeys()->values();

        $diasTrabalhados = $dias->filter(fn (DiaRelatorio $d) => $d->horasSegundos !== null)->count();
        $totalHoras = $dias->sum(fn (DiaRelatorio $d) => $d->horasSegundos ?? 0);
        $media = $diasTrabalhados > 0 ? intdiv((int) $totalHoras, $diasTrabalhados) : 0;

        return new RelatorioIndividual(
            nomeUtilizador: $user->name,
            de: $de->toDateString(),
            ate: $ate->toDateString(),
            dias: $dias,
            diasTrabalhados: $diasTrabalhados,
            totalHorasSegundos: (int) $totalHoras,
            mediaSegundos: $media,
        );
    }
}
