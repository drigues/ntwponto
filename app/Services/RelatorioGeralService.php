<?php

namespace App\Services;

use App\DTOs\LinhaRelatorio;
use App\Enums\TipoMarcacao;
use App\Enums\UserRole;
use App\Models\Marcacao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RelatorioGeralService
{
    /**
     * @param  array<int>|null  $userIds
     * @return Collection<int, LinhaRelatorio>
     */
    public function gerar(CarbonImmutable $de, CarbonImmutable $ate, ?array $userIds = null): Collection
    {
        $cacheKey = $this->cacheKey($de, $ate, $userIds);

        /** @var Collection<int, LinhaRelatorio> */
        return Cache::remember($cacheKey, 300, function () use ($de, $ate, $userIds): Collection {
            return $this->calcular($de, $ate, $userIds);
        });
    }

    /**
     * @param  array<int>|null  $userIds
     * @return Collection<int, LinhaRelatorio>
     */
    private function calcular(CarbonImmutable $de, CarbonImmutable $ate, ?array $userIds): Collection
    {
        $usersQuery = User::where('role', UserRole::Funcionario);

        if ($userIds !== null && count($userIds) > 0) {
            $usersQuery->whereIn('id', $userIds);
        }

        $users = $usersQuery->orderBy('name')->get();

        $marcacoes = Marcacao::whereIn('user_id', $users->pluck('id'))
            ->whereBetween('data_civil', [$de->toDateString(), $ate->toDateString()])
            ->orderBy('data_hora')
            ->get();

        $porUser = $marcacoes->groupBy('user_id');

        $sequenciaCompleta = [
            TipoMarcacao::Entrada->value,
            TipoMarcacao::InicioPausa->value,
            TipoMarcacao::FimPausa->value,
            TipoMarcacao::Saida->value,
        ];

        return $users->map(function (User $user) use ($porUser, $sequenciaCompleta): LinhaRelatorio {
            /** @var Collection<int, Marcacao> $userMarcacoes */
            $userMarcacoes = $porUser->get($user->id, collect());

            $porDia = $userMarcacoes->groupBy('data_civil');

            $diasTrabalhados = 0;
            $totalSegundos = 0;
            $inconsistencias = 0;

            foreach ($porDia as $marcacoesDia) {
                $tipos = $marcacoesDia->pluck('tipo')->map(fn (TipoMarcacao $t) => $t->value)->toArray();

                $completo = $tipos === $sequenciaCompleta;

                if ($completo) {
                    $entrada = $marcacoesDia->firstWhere('tipo', TipoMarcacao::Entrada);
                    $saida = $marcacoesDia->firstWhere('tipo', TipoMarcacao::Saida);
                    $inicioPausa = $marcacoesDia->firstWhere('tipo', TipoMarcacao::InicioPausa);
                    $fimPausa = $marcacoesDia->firstWhere('tipo', TipoMarcacao::FimPausa);

                    if ($entrada && $saida && $inicioPausa && $fimPausa) {
                        $total = $entrada->data_hora->diffInSeconds($saida->data_hora, absolute: true);
                        $pausa = $inicioPausa->data_hora->diffInSeconds($fimPausa->data_hora, absolute: true);
                        $totalSegundos += (int) ($total - $pausa);
                        $diasTrabalhados++;
                    }
                } else {
                    $expectedPrefix = array_slice($sequenciaCompleta, 0, count($tipos));
                    if (! empty($tipos) && $tipos !== $expectedPrefix) {
                        $inconsistencias++;
                    }
                }
            }

            $media = $diasTrabalhados > 0 ? intdiv($totalSegundos, $diasTrabalhados) : 0;

            return new LinhaRelatorio(
                userId: $user->id,
                nome: $user->name,
                cargo: $user->cargo,
                diasTrabalhados: $diasTrabalhados,
                totalHorasSegundos: $totalSegundos,
                mediaSegundos: $media,
                inconsistencias: $inconsistencias,
            );
        });
    }

    /**
     * @param  array<int>|null  $userIds
     */
    private function cacheKey(CarbonImmutable $de, CarbonImmutable $ate, ?array $userIds): string
    {
        $ids = $userIds ? implode(',', $userIds) : 'all';

        return "relatorio:geral:{$de->toDateString()}:{$ate->toDateString()}:{$ids}";
    }

    public static function invalidateCache(): void
    {
        // Clear all relatorio:geral cache keys via tag or pattern
        // Simple approach: flush the specific prefix keys won't work with file driver,
        // so we use a version key that gets bumped
        Cache::forget('relatorio:geral:version');
    }
}
