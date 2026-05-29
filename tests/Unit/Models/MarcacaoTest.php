<?php

use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use Carbon\CarbonImmutable;

it('casts tipo to TipoMarcacao enum', function () {
    $user = User::factory()->funcionario()->create();

    $marcacao = Marcacao::factory()->create([
        'user_id' => $user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => '2026-05-29',
    ]);

    $marcacao->refresh();
    expect($marcacao->tipo)->toBe(TipoMarcacao::Entrada);
});

it('scopes doDia for given date', function () {
    $user = User::factory()->funcionario()->create();

    $today = CarbonImmutable::create(2026, 5, 29, 0, 0, 0, 'Europe/Lisbon');
    $yesterday = $today->subDay();

    Marcacao::factory()->create([
        'user_id' => $user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => $today->toDateString(),
    ]);
    Marcacao::factory()->create([
        'user_id' => $user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => $yesterday->toDateString(),
    ]);

    $results = Marcacao::doDia($today)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->data_civil)->toBe($today->toDateString());
});

it('scopes doUtilizador and doPeriodo', function () {
    $user1 = User::factory()->funcionario()->create();
    $user2 = User::factory()->funcionario()->create();

    $start = CarbonImmutable::create(2026, 5, 1, 0, 0, 0, 'Europe/Lisbon');
    $end = CarbonImmutable::create(2026, 5, 31, 0, 0, 0, 'Europe/Lisbon');

    Marcacao::factory()->create([
        'user_id' => $user1->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => '2026-05-15',
        'data_hora' => CarbonImmutable::create(2026, 5, 15, 9, 0, 0, 'UTC'),
    ]);
    Marcacao::factory()->create([
        'user_id' => $user2->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => '2026-05-15',
        'data_hora' => CarbonImmutable::create(2026, 5, 15, 9, 0, 0, 'UTC'),
    ]);
    Marcacao::factory()->create([
        'user_id' => $user1->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => '2026-06-01',
        'data_hora' => CarbonImmutable::create(2026, 6, 1, 9, 0, 0, 'UTC'),
    ]);

    $results = Marcacao::doUtilizador($user1)->doPeriodo($start, $end)->get();

    expect($results)->toHaveCount(1);
});
