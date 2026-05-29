<?php

namespace App\DTOs;

use App\Models\Marcacao;
use Illuminate\Support\Collection;

final readonly class DiaRelatorio
{
    /**
     * @param  Collection<int, Marcacao>  $marcacoes
     */
    public function __construct(
        public string $data,
        public Collection $marcacoes,
        public ?int $horasSegundos,
        public bool $emCurso,
        public bool $inconsistencia,
        public bool $editado,
    ) {}

    public function horasFormatadas(): string
    {
        if ($this->horasSegundos === null) {
            return $this->emCurso ? 'Em curso' : '—';
        }

        $horas = intdiv($this->horasSegundos, 3600);
        $minutos = intdiv($this->horasSegundos % 3600, 60);

        return sprintf('%dh %02dm', $horas, $minutos);
    }
}
