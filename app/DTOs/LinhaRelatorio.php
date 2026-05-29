<?php

namespace App\DTOs;

final readonly class LinhaRelatorio
{
    public function __construct(
        public int $userId,
        public string $nome,
        public ?string $cargo,
        public int $diasTrabalhados,
        public int $totalHorasSegundos,
        public int $mediaSegundos,
        public int $inconsistencias,
    ) {}

    public function diasLabel(): string
    {
        return $this->diasTrabalhados === 1
            ? '1 dia'
            : $this->diasTrabalhados.' dias';
    }

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

    public function inconsistenciasLabel(): string
    {
        if ($this->inconsistencias === 0) {
            return '—';
        }

        return $this->inconsistencias === 1
            ? '1 inconsistência'
            : $this->inconsistencias.' inconsistências';
    }
}
