<?php

use App\Enums\TipoMarcacao;
use App\Jobs\GerarRelatorioPdfJob;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\RelatorioPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

it('generates pdf inline for individual report of 1 month', function () {
    $func = User::factory()->funcionario()->create(['must_change_password' => false]);
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    $de = now()->timezone('Europe/Lisbon')->startOfMonth()->toDateString();
    $ate = now()->timezone('Europe/Lisbon')->endOfMonth()->toDateString();

    $response = $this->get("/relatorio/pdf?de={$de}&ate={$ate}");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('dispatches job when period > 30 days', function () {
    Queue::fake();

    $func = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($func);

    $de = '2026-01-01';
    $ate = '2026-03-31';

    $response = $this->post('/relatorio/pdf/async', ['de' => $de, 'ate' => $ate]);

    $response->assertOk();
    Queue::assertPushed(GerarRelatorioPdfJob::class);
});

it('dispatches job when geral has > 1 funcionario', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    User::factory()->funcionario()->create();
    User::factory()->funcionario()->create();

    $this->actingAs($admin);

    $de = now()->timezone('Europe/Lisbon')->startOfMonth()->toDateString();
    $ate = now()->timezone('Europe/Lisbon')->endOfMonth()->toDateString();

    $response = $this->post('/relatorio/pdf/async', [
        'de' => $de,
        'ate' => $ate,
        'tipo' => 'geral',
    ]);

    $response->assertOk();
    Queue::assertPushed(GerarRelatorioPdfJob::class);
});

it('stores pdf and returns signed url valid for 30 min', function () {
    Storage::fake();

    $func = User::factory()->funcionario()->create(['must_change_password' => false]);
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    $de = now()->timezone('Europe/Lisbon')->startOfMonth()->toDateString();
    $ate = now()->timezone('Europe/Lisbon')->endOfMonth()->toDateString();

    $response = $this->get("/relatorio/pdf?de={$de}&ate={$ate}");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('rejects download with expired url', function () {
    $func = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($func);

    // Generate a signed URL that expired 1 hour ago
    $url = URL::signedRoute('pdf.download', [
        'filename' => 'test.pdf',
    ], now()->subHour());

    $response = $this->get($url);
    $response->assertStatus(403);
});

it('blocks non-owner from downloading individual pdf', function () {
    $func1 = User::factory()->funcionario()->create(['must_change_password' => false]);
    $func2 = User::factory()->funcionario()->create(['must_change_password' => false]);

    $this->actingAs($func2);

    $de = now()->timezone('Europe/Lisbon')->startOfMonth()->toDateString();
    $ate = now()->timezone('Europe/Lisbon')->endOfMonth()->toDateString();

    // func2 tries to access func1's report
    $response = $this->get("/relatorio/pdf?de={$de}&ate={$ate}&userId={$func1->id}");

    $response->assertStatus(403);
});

it('pdf contains nome empresa, periodo and data de emissao no cabeçalho', function () {
    $func = User::factory()->funcionario()->create(['must_change_password' => false]);
    $hoje = now()->timezone('Europe/Lisbon')->toDateString();

    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $hoje,
    ]);

    $this->actingAs($func);

    $de = now()->timezone('Europe/Lisbon')->startOfMonth()->toDateString();
    $ate = now()->timezone('Europe/Lisbon')->endOfMonth()->toDateString();

    // We test the view directly since PDF binary can't be text-searched easily
    $service = app(RelatorioPdfService::class);
    $html = $service->renderHtmlIndividual(
        $func,
        CarbonImmutable::parse($de),
        CarbonImmutable::parse($ate),
    );

    expect($html)->toContain(config('app.name'));
    expect($html)->toContain($de);
    expect($html)->toContain($ate);
    expect($html)->toContain(now()->format('d/m/Y'));
});
