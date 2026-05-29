<?php

namespace Database\Factories;

use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Marcacao> */
class MarcacaoFactory extends Factory
{
    protected $model = Marcacao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $dataHora = $this->faker->dateTimeBetween('-1 month', 'now', 'UTC');

        return [
            'user_id' => User::factory(),
            'tipo' => TipoMarcacao::Entrada,
            'data_hora' => $dataHora,
            'data_civil' => (new \DateTimeImmutable($dataHora->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone('Europe/Lisbon'))
                ->format('Y-m-d'),
            'gps_indisponivel' => true,
        ];
    }
}
