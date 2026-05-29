<?php

use App\Enums\TipoMarcacao;
use App\Exceptions\SequenciaInvalidaException;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\MarcacaoService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->service = app(MarcacaoService::class);
    $this->user = User::factory()->funcionario()->create();
});

it('registers entrada when no marcacoes today', function () {
    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::Entrada,
    );

    expect($marcacao)->toBeInstanceOf(Marcacao::class);
    expect($marcacao->tipo)->toBe(TipoMarcacao::Entrada);
    expect($marcacao->user_id)->toBe($this->user->id);
    expect($marcacao->gps_indisponivel)->toBeTrue();
});

it('rejects entrada when already exists today', function () {
    $this->service->registar($this->user, TipoMarcacao::Entrada);

    $this->service->registar($this->user, TipoMarcacao::Entrada);
})->throws(SequenciaInvalidaException::class);

it('registers inicio_pausa only after entrada', function () {
    $this->service->registar($this->user, TipoMarcacao::Entrada);

    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::InicioPausa,
    );

    expect($marcacao->tipo)->toBe(TipoMarcacao::InicioPausa);
});

it('registers fim_pausa only after inicio_pausa', function () {
    $this->service->registar($this->user, TipoMarcacao::Entrada);
    $this->service->registar($this->user, TipoMarcacao::InicioPausa);

    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::FimPausa,
    );

    expect($marcacao->tipo)->toBe(TipoMarcacao::FimPausa);
});

it('registers saida only after fim_pausa', function () {
    $this->service->registar($this->user, TipoMarcacao::Entrada);
    $this->service->registar($this->user, TipoMarcacao::InicioPausa);
    $this->service->registar($this->user, TipoMarcacao::FimPausa);

    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::Saida,
    );

    expect($marcacao->tipo)->toBe(TipoMarcacao::Saida);
});

it('rejects out-of-order sequence in any combination', function () {
    // inicio_pausa without entrada
    $this->service->registar($this->user, TipoMarcacao::InicioPausa);
})->throws(SequenciaInvalidaException::class);

it('stores latitude and longitude when provided', function () {
    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::Entrada,
        latitude: 38.7223340,
        longitude: -9.1393366,
    );

    expect((float) $marcacao->latitude)->toBe(38.722334);
    expect((float) $marcacao->longitude)->toBe(-9.1393366);
    expect($marcacao->gps_indisponivel)->toBeFalse();
});

it('marks gps_indisponivel when coords are null', function () {
    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::Entrada,
    );

    expect($marcacao->latitude)->toBeNull();
    expect($marcacao->longitude)->toBeNull();
    expect($marcacao->gps_indisponivel)->toBeTrue();
});

it('uses Europe/Lisbon to define data civil', function () {
    // In winter (WET = UTC+0), 23:30 UTC on Dec 31 = 23:30 Lisbon = still Dec 31
    // In summer (WEST = UTC+1), 23:30 UTC on Jun 30 = 00:30 Jul 1 Lisbon
    $summerNight = CarbonImmutable::create(2026, 6, 30, 23, 30, 0, 'UTC');

    $this->travelTo($summerNight);

    $marcacao = $this->service->registar(
        $this->user,
        TipoMarcacao::Entrada,
    );

    // 23:30 UTC in summer = 00:30 Jul 1 in Lisbon
    expect($marcacao->data_civil)->toBe('2026-07-01');
});

it('enforces unique (user_id, tipo, data_civil)', function () {
    $this->service->registar($this->user, TipoMarcacao::Entrada);

    // Try to insert a duplicate directly via model to test DB constraint
    Marcacao::create([
        'user_id' => $this->user->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now(),
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
        'gps_indisponivel' => true,
    ]);
})->throws(QueryException::class);
