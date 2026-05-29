<?php

use App\DTOs\DiaRelatorio;
use App\DTOs\RelatorioIndividual as RelatorioIndividualDTO;
use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\RelatorioIndividualService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('groups marcacoes by data_civil', function () {
    $func = User::factory()->funcionario()->create();
    $hoje = CarbonImmutable::now('Europe/Lisbon');
    $ontem = $hoje->subDay();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->subDay()->setTime(9, 0),
        'data_civil' => $ontem->toDateString(),
    ]);

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje->toDateString(),
    ]);

    $service = app(RelatorioIndividualService::class);
    $relatorio = $service->gerar($func, $ontem, $hoje);

    expect($relatorio)->toBeInstanceOf(RelatorioIndividualDTO::class);
    expect($relatorio->dias)->toHaveCount(2);
    expect($relatorio->dias->first())->toBeInstanceOf(DiaRelatorio::class);
});

it('eager loads to avoid N+1', function () {
    $func = User::factory()->funcionario()->create();
    $admin = User::factory()->admin()->create();
    $hoje = CarbonImmutable::now('Europe/Lisbon');

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje->toDateString(),
        'editado_por' => $admin->id,
        'editado_em' => now(),
    ]);

    $service = app(RelatorioIndividualService::class);

    // Count queries: should be a single query + eager load
    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $service->gerar($func, $hoje, $hoje);

    // 1 query for marcacoes with eager load — should not exceed 2
    expect($queryCount)->toBeLessThanOrEqual(2);
});

it('uses Europe/Lisbon to group days', function () {
    $func = User::factory()->funcionario()->create();

    // Marcação at 23:30 UTC = 00:30 Europe/Lisbon (next day in winter)
    $utcTime = CarbonImmutable::parse('2026-01-15 23:30:00', 'UTC');
    $lisbonDate = $utcTime->timezone('Europe/Lisbon')->toDateString(); // 2026-01-16

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => $utcTime,
        'data_civil' => $lisbonDate,
    ]);

    $service = app(RelatorioIndividualService::class);
    $de = CarbonImmutable::parse($lisbonDate, 'Europe/Lisbon');
    $relatorio = $service->gerar($func, $de, $de);

    expect($relatorio->dias)->toHaveCount(1);
    expect($relatorio->dias->first()->data)->toBe($lisbonDate);
});
