<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

final readonly class RelatorioIndividual
{
    /**
     * @param  Collection<int, DiaRelatorio>  $dias
     */
    public function __construct(
        public string $nomeUtilizador,
        public string $de,
        public string $ate,
        public Collection $dias,
        public int $diasTrabalhados,
        public int $totalHorasSegundos,
        public int $mediaSegundos,
    ) {}

    public function totalFormatado(): string
    {
        $horas = intdiv($this->totalHorasSegundos, 3600);
        $minutos = intdiv($this->totalHorasSegundos % 3600, 60);

        return sprintf('%dh %02dm', $horas, $minutos);
    }

    public function mediaFormatada(): string
    {
        $horas = intdiv($this->mediaSegundos, 3600);
        $minutos = intdiv($this->mediaSegundos % 3600, 60);

        return sprintf('%dh %02dm', $horas, $minutos);
    }
}
