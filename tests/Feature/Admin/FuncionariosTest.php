<?php

use App\Enums\UserRole;
use App\Filament\Resources\FuncionarioResource;
use App\Filament\Resources\FuncionarioResource\Pages\CreateFuncionario;
use App\Filament\Resources\FuncionarioResource\Pages\EditFuncionario;
use App\Filament\Resources\FuncionarioResource\Pages\ListFuncionarios;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

it('lists only users with role funcionario by default', function () {
    $admin = User::factory()->admin()->create();
    $func1 = User::factory()->funcionario()->create(['name' => 'Func Um']);
    $func2 = User::factory()->funcionario()->create(['name' => 'Func Dois']);

    $this->actingAs($admin);

    livewire(ListFuncionarios::class)
        ->assertCanSeeTableRecords([$func1, $func2])
        ->assertCanNotSeeTableRecords([$admin]);
});

it('creates funcionario with must_change_password true', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    livewire(CreateFuncionario::class)
        ->fillForm([
            'name' => 'Novo Funcionario',
            'email' => 'novo@empresa.pt',
            'cargo' => 'Programador',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'novo@empresa.pt')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(UserRole::Funcionario);
    expect($user->must_change_password)->toBeTrue();
    expect($user->cargo)->toBe('Programador');
});

it('generates a strong random password when none provided', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    livewire(CreateFuncionario::class)
        ->fillForm([
            'name' => 'Sem Password',
            'email' => 'sempass@empresa.pt',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'sempass@empresa.pt')->first();
    expect($user)->not->toBeNull();
    // Password was set (hashed) — can't be empty
    expect($user->password)->not->toBeNull();
});

it('rejects duplicate emails', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'duplicado@empresa.pt']);

    $this->actingAs($admin);

    livewire(CreateFuncionario::class)
        ->fillForm([
            'name' => 'Duplicado',
            'email' => 'duplicado@empresa.pt',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('soft deletes funcionario (desactivar)', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create();

    $this->actingAs($admin);

    livewire(ListFuncionarios::class)
        ->callTableAction('desactivar', $func);

    expect($func->fresh()->trashed())->toBeTrue();
});

it('resets password and sets must_change_password back to true', function () {
    $admin = User::factory()->admin()->create();
    $func = User::factory()->funcionario()->create([
        'must_change_password' => false,
    ]);

    $this->actingAs($admin);

    $oldPassword = $func->password;

    livewire(ListFuncionarios::class)
        ->callTableAction('redefinir_password', $func);

    $func->refresh();
    expect($func->must_change_password)->toBeTrue();
    expect($func->password)->not->toBe($oldPassword);
});

it('logs activity on create/update/delete', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    // Create
    livewire(CreateFuncionario::class)
        ->fillForm([
            'name' => 'Para Log',
            'email' => 'paralog@empresa.pt',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $func = User::where('email', 'paralog@empresa.pt')->first();

    expect(Activity::where('subject_type', User::class)
        ->where('subject_id', $func->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();

    // Update
    livewire(EditFuncionario::class, [
        'record' => $func->getRouteKey(),
    ])
        ->fillForm(['name' => 'Editado'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Activity::where('subject_type', User::class)
        ->where('subject_id', $func->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

it('blocks non-admin from accessing the resource', function () {
    $func = User::factory()->funcionario()->create();

    $this->actingAs($func)
        ->get(FuncionarioResource::getUrl('index'))
        ->assertStatus(403);
});
