<?php

use App\DTOs\LinhaRelatorio;
use App\Enums\TipoMarcacao;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\RelatorioGeralService;
use Carbon\CarbonImmutable;

it('blocks non-admin with 403', function () {
    $func = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($func)
        ->get('/admin/relatorios-geral')
        ->assertStatus(403);
});

it('defaults to current month and all active funcionarios', function () {
    $admin = User::factory()->admin()->create();
    $func1 = User::factory()->funcionario()->create();
    $func2 = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon');

    // Complete day for func1
    foreach ([
        [TipoMarcacao::Entrada, 9, 0],
        [TipoMarcacao::InicioPausa, 12, 0],
        [TipoMarcacao::FimPausa, 13, 0],
        [TipoMarcacao::Saida, 18, 0],
    ] as [$tipo, $hora, $min]) {
        Marcacao::factory()->create([
            'user_id' => $func1->id,
            'tipo' => $tipo,
            'data_hora' => now()->setTime($hora, $min),
            'data_civil' => $hoje->toDateString(),
        ]);
    }

    $this->actingAs($admin);

    $response = $this->get('/admin/relatorios-geral');
    $response->assertOk();
    $response->assertSee($func1->name);
    $response->assertSee($func2->name);
});

it('aggregates dias trabalhados, total horas, média diária per funcionario', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon');

    // Complete day: 9h work, 1h pause = 8h
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::InicioPausa, 'data_hora' => now()->setTime(12, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::FimPausa, 'data_hora' => now()->setTime(13, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Saida, 'data_hora' => now()->setTime(18, 0), 'data_civil' => $hoje->toDateString()]);

    $this->actingAs($admin);

    $response = $this->get('/admin/relatorios-geral');
    $response->assertOk();
    $response->assertSee('1 dia');
    $response->assertSee('8h 00m');
});

it('counts inconsistencias per funcionario', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    // Inconsistent: entrada + saida without pausa
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Saida, 'data_hora' => now()->setTime(18, 0), 'data_civil' => $hoje]);

    $this->actingAs($admin);

    $response = $this->get('/admin/relatorios-geral');
    $response->assertOk();
    $response->assertSee('1 inconsistência');
});

it('filters by selected funcionarios', function () {
    $func1 = User::factory()->funcionario()->create();
    $func2 = User::factory()->funcionario()->create();

    $hoje = CarbonImmutable::now('Europe/Lisbon');

    Marcacao::factory()->create(['user_id' => $func1->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func2->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje->toDateString()]);

    $service = app(RelatorioGeralService::class);
    $linhas = $service->gerar($hoje->startOfMonth(), $hoje->endOfMonth(), [$func1->id]);

    expect($linhas)->toHaveCount(1);
    expect($linhas->first())->toBeInstanceOf(LinhaRelatorio::class);
    expect($linhas->first()->userId)->toBe($func1->id);
});

it('drill-down link points to relatorio individual with same period', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje]);

    $this->actingAs($admin);

    $response = $this->get('/admin/relatorios-geral');
    $response->assertOk();
    $response->assertSee('Ver detalhe');
});

it('does not include soft-deleted funcionarios by default', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();
    $deleted = User::factory()->funcionario()->create();
    $deleted->delete();

    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => now()->setTime(9, 0), 'data_civil' => $hoje]);

    $this->actingAs($admin);

    $response = $this->get('/admin/relatorios-geral');
    $response->assertOk();
    $response->assertSee($func->name);
    $response->assertDontSee($deleted->name);
});
