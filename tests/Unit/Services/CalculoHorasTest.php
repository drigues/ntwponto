<?php

use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\CalculoHorasService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->service = app(CalculoHorasService::class);
    $this->user = User::factory()->funcionario()->create();
    $this->dia = CarbonImmutable::create(2026, 5, 29, 0, 0, 0, 'Europe/Lisbon');
});

it('computes horas trabalhadas correctly', function () {
    // 09:00 entrada, 12:00 inicio_pausa, 13:00 fim_pausa, 18:00 saida
    // Work = (18:00 - 09:00) - (13:00 - 12:00) = 9h - 1h = 8h = 28800 seconds
    $baseDate = $this->dia->setTimezone('UTC');

    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => $baseDate->setTime(9, 0),
        'data_civil' => '2026-05-29',
    ]);
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::InicioPausa,
        'data_hora' => $baseDate->setTime(12, 0),
        'data_civil' => '2026-05-29',
    ]);
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::FimPausa,
        'data_hora' => $baseDate->setTime(13, 0),
        'data_civil' => '2026-05-29',
    ]);
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Saida,
        'data_hora' => $baseDate->setTime(18, 0),
        'data_civil' => '2026-05-29',
    ]);

    $seconds = $this->service->horasDoDia($this->user, $this->dia);

    expect($seconds)->toBe(28800);
});

it('returns null when day is incomplete', function () {
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => $this->dia->setTimezone('UTC')->setTime(9, 0),
        'data_civil' => '2026-05-29',
    ]);

    $result = $this->service->horasDoDia($this->user, $this->dia);

    expect($result)->toBeNull();
});

it('flags inconsistency when intermediate marcacao is missing', function () {
    // Has entrada and saida but no pausa
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => $this->dia->setTimezone('UTC')->setTime(9, 0),
        'data_civil' => '2026-05-29',
    ]);
    Marcacao::factory()->create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Saida,
        'data_hora' => $this->dia->setTimezone('UTC')->setTime(18, 0),
        'data_civil' => '2026-05-29',
    ]);

    $result = $this->service->temInconsistencias($this->user, $this->dia);

    expect($result)->toBeTrue();
});
