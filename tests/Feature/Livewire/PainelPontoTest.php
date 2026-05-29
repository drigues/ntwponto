<?php

use App\Enums\TipoMarcacao;
use App\Livewire\PainelPonto;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\MarcacaoService;

use function Pest\Livewire\livewire;

it('redirects unauthenticated to login', function () {
    $this->get('/ponto')
        ->assertRedirect('/login');
});

it('redirects to /password/change when must_change_password', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get('/ponto')
        ->assertRedirect('/password/change');
});

it('shows "Marcar entrada" when day is empty', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Marcar entrada');
});

it('shows "Sair para almoço" after entrada', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    app(MarcacaoService::class)->registar($user, TipoMarcacao::Entrada);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Sair para almoço');
});

it('shows "Voltar do almoço" after inicio_pausa', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $service = app(MarcacaoService::class);
    $service->registar($user, TipoMarcacao::Entrada);
    $service->registar($user, TipoMarcacao::InicioPausa);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Voltar do almoço');
});

it('shows "Marcar saída" with form after fim_pausa', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $service = app(MarcacaoService::class);
    $service->registar($user, TipoMarcacao::Entrada);
    $service->registar($user, TipoMarcacao::InicioPausa);
    $service->registar($user, TipoMarcacao::FimPausa);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Marcar saída');
});

it('shows "Dia concluído" after saida', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $service = app(MarcacaoService::class);
    $service->registar($user, TipoMarcacao::Entrada);
    $service->registar($user, TipoMarcacao::InicioPausa);
    $service->registar($user, TipoMarcacao::FimPausa);
    $service->registar($user, TipoMarcacao::Saida);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Dia concluído');
});

it('records latitude and longitude when submitted', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarMarcacao', 38.7223340, -9.1393366)
        ->assertHasNoErrors();

    $marcacao = Marcacao::where('user_id', $user->id)->first();
    expect($marcacao)->not->toBeNull();
    expect((float) $marcacao->latitude)->toBe(38.722334);
    expect((float) $marcacao->longitude)->toBe(-9.1393366);
    expect($marcacao->gps_indisponivel)->toBeFalse();
});

it('records gps_indisponivel=true when coords omitted', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarMarcacao', null, null)
        ->assertHasNoErrors();

    $marcacao = Marcacao::where('user_id', $user->id)->first();
    expect($marcacao)->not->toBeNull();
    expect($marcacao->gps_indisponivel)->toBeTrue();
});

it('does not block marcacao when GPS unavailable', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarMarcacao', null, null)
        ->assertHasNoErrors();

    expect(Marcacao::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects out-of-order action attempts', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    // No entrada yet, try to register inicio_pausa via direct Livewire call
    app(MarcacaoService::class)->registar($user, TipoMarcacao::Entrada);

    $this->actingAs($user);

    // Force saida directly (skipping pausa)
    livewire(PainelPonto::class)
        ->set('tipoForcar', 'saida')
        ->call('registarMarcacaoForcar')
        ->assertDispatched('notify');
});

it('lists marcacoes of the day with hour and gps status', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $service = app(MarcacaoService::class);
    $service->registar($user, TipoMarcacao::Entrada, 38.7223340, -9.1393366);

    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->assertSee('Entrada')
        ->assertSee('GPS');
});
