<?php

use App\Enums\TipoMarcacao;
use App\Livewire\RelatorioIndividual;
use App\Models\Marcacao;
use App\Models\User;

use function Pest\Livewire\livewire;

it('funcionario sees only own data', function () {
    $func1 = User::factory()->funcionario()->create();
    $func2 = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func1->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    Marcacao::factory()->create([
        'user_id' => $func2->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func1);

    livewire(RelatorioIndividual::class)
        ->assertSee($func1->name)
        ->assertDontSee($func2->name);
});

it('admin sees data of any funcionario', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($admin);

    livewire(RelatorioIndividual::class, ['userId' => $func->id])
        ->assertSee($func->name);
});

it('defaults to current month when no filter provided', function () {
    $func = User::factory()->funcionario()->create();

    $hoje = now()->timezone('Europe/Lisbon');
    $inicioMes = $hoje->startOfMonth()->toDateString();
    $fimMes = $hoje->endOfMonth()->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje->toDateString(),
    ]);

    $this->actingAs($func);

    $component = livewire(RelatorioIndividual::class);

    expect($component->get('de'))->toBe($inicioMes);
    expect($component->get('ate'))->toBe($fimMes);
});

it('lists each day with all marcacoes and computed hours', function () {
    $func = User::factory()->funcionario()->create();
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::InicioPausa,
        'data_hora' => now()->setTime(12, 0),
        'data_civil' => $hoje,
    ]);
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::FimPausa,
        'data_hora' => now()->setTime(13, 0),
        'data_civil' => $hoje,
    ]);
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Saida,
        'data_hora' => now()->setTime(18, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    livewire(RelatorioIndividual::class)
        ->assertSee('09:00')
        ->assertSee('12:00')
        ->assertSee('13:00')
        ->assertSee('18:00')
        ->assertSee('8h 00m');
});

it('shows day as "em curso" when incomplete', function () {
    $func = User::factory()->funcionario()->create();
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    livewire(RelatorioIndividual::class)
        ->assertSee('Em curso');
});

it('flags day with missing intermediate marcacao', function () {
    $func = User::factory()->funcionario()->create();
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    // Entrada + Saida without pausa — inconsistency
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Saida,
        'data_hora' => now()->setTime(18, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    livewire(RelatorioIndividual::class)
        ->assertSee('Inconsistência');
});

it('marks days edited by admin', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
        'editado_por' => $admin->id,
        'editado_em' => now(),
    ]);

    $this->actingAs($func);

    livewire(RelatorioIndividual::class)
        ->assertSee('Editado');
});

it('aggregates totais: dias trabalhados, total horas, média diária', function () {
    $func = User::factory()->funcionario()->create();
    $hoje = now()->timezone('Europe/Lisbon');
    $ontem = $hoje->copy()->subDay();

    // Day 1 — complete day: 9h work, 1h pause = 8h
    $baseOntem = now()->copy()->subDay();
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => $baseOntem->copy()->setTime(9, 0), 'data_civil' => $ontem->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::InicioPausa, 'data_hora' => $baseOntem->copy()->setTime(12, 0), 'data_civil' => $ontem->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::FimPausa, 'data_hora' => $baseOntem->copy()->setTime(13, 0), 'data_civil' => $ontem->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Saida, 'data_hora' => $baseOntem->copy()->setTime(18, 0), 'data_civil' => $ontem->toDateString()]);

    // Day 2 — complete day: 9h work, 1h pause = 8h
    $baseHoje = now()->copy();
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Entrada, 'data_hora' => $baseHoje->copy()->setTime(9, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::InicioPausa, 'data_hora' => $baseHoje->copy()->setTime(12, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::FimPausa, 'data_hora' => $baseHoje->copy()->setTime(13, 0), 'data_civil' => $hoje->toDateString()]);
    Marcacao::factory()->create(['user_id' => $func->id, 'tipo' => TipoMarcacao::Saida, 'data_hora' => $baseHoje->copy()->setTime(18, 0), 'data_civil' => $hoje->toDateString()]);

    $this->actingAs($func);

    livewire(RelatorioIndividual::class)
        ->assertSee('2 dias')
        ->assertSee('16h 00m')
        ->assertSee('8h 00m');
});
