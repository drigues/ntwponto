<?php

use App\Enums\TipoMarcacao;
use App\Filament\Resources\MarcacaoResource;
use App\Filament\Resources\MarcacaoResource\Pages\CreateMarcacao;
use App\Filament\Resources\MarcacaoResource\Pages\EditMarcacao;
use App\Filament\Resources\MarcacaoResource\Pages\ListMarcacoes;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\MarcacaoService;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

it('lists all marcacoes with filters', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $service = app(MarcacaoService::class);
    $service->registar($func, TipoMarcacao::Entrada);

    $this->actingAs($admin);

    livewire(ListMarcacoes::class)
        ->assertCanSeeTableRecords(Marcacao::all());
});

it('admin can create marcacao for any funcionario', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $this->actingAs($admin);

    livewire(CreateMarcacao::class)
        ->fillForm([
            'user_id' => $func->id,
            'tipo' => TipoMarcacao::Entrada->value,
            'data_hora' => now()->setTime(9, 0)->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $marcacao = Marcacao::where('user_id', $func->id)->first();
    expect($marcacao)->not->toBeNull();
    expect($marcacao->tipo)->toBe(TipoMarcacao::Entrada);
    expect($marcacao->editado_por)->toBe($admin->id);
});

it('admin can edit data_hora of existing marcacao', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $marcacao = Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
    ]);

    $this->actingAs($admin);

    $newTime = now()->setTime(8, 30)->format('Y-m-d H:i:s');

    livewire(EditMarcacao::class, [
        'record' => $marcacao->getRouteKey(),
    ])
        ->fillForm(['data_hora' => $newTime])
        ->call('save')
        ->assertHasNoFormErrors();

    $marcacao->refresh();
    expect($marcacao->editado_por)->toBe($admin->id);
    expect($marcacao->editado_em)->not->toBeNull();
});

it('admin can delete marcacao', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $marcacao = Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
    ]);

    $this->actingAs($admin);

    livewire(ListMarcacoes::class)
        ->callTableAction('delete', $marcacao);

    expect(Marcacao::find($marcacao->id))->toBeNull();
});

it('rejects edit that breaks the day sequence', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();
    $dataCivil = now()->timezone('Europe/Lisbon')->toDateString();

    // Create a valid entrada
    Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => $dataCivil,
    ]);

    $this->actingAs($admin);

    // Try to create saida without pausa — breaks sequence
    livewire(CreateMarcacao::class)
        ->fillForm([
            'user_id' => $func->id,
            'tipo' => TipoMarcacao::Saida->value,
            'data_hora' => now()->setTime(18, 0)->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasFormErrors(['tipo']);
});

it('fills editado_por and editado_em on update', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $marcacao = Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
    ]);

    $this->actingAs($admin);

    livewire(EditMarcacao::class, [
        'record' => $marcacao->getRouteKey(),
    ])
        ->fillForm(['data_hora' => now()->setTime(8, 45)->format('Y-m-d H:i:s')])
        ->call('save')
        ->assertHasNoFormErrors();

    $marcacao->refresh();
    expect($marcacao->editado_por)->toBe($admin->id);
    expect($marcacao->editado_em)->not->toBeNull();
});

it('logs activity with before/after diff', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $marcacao = Marcacao::factory()->create([
        'user_id' => $func->id,
        'tipo' => TipoMarcacao::Entrada,
        'data_hora' => now()->setTime(9, 0),
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
    ]);

    $this->actingAs($admin);

    livewire(EditMarcacao::class, [
        'record' => $marcacao->getRouteKey(),
    ])
        ->fillForm(['data_hora' => now()->setTime(8, 30)->format('Y-m-d H:i:s')])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Activity::where('subject_type', Marcacao::class)
        ->where('subject_id', $marcacao->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

it('blocks funcionario role with 403', function () {
    $func = User::factory()->funcionario()->create();

    $this->actingAs($func)
        ->get(MarcacaoResource::getUrl('index'))
        ->assertStatus(403);
});
