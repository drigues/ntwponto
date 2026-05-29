<?php

use App\Enums\UserRole;
use App\Models\User;

it('creates admin with generated password', function () {
    $this->artisan('admin:create', ['email' => 'admin@empresa.pt'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Admin criado com sucesso')
        ->expectsOutputToContain('Password:');

    $user = User::where('email', 'admin@empresa.pt')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(UserRole::Admin);
    expect($user->must_change_password)->toBeTrue();
});

it('creates admin with explicit password and must_change_password false', function () {
    $this->artisan('admin:create', [
        'email' => 'admin2@empresa.pt',
        '--password' => 'MySecurePass1',
    ])->assertExitCode(0);

    $user = User::where('email', 'admin2@empresa.pt')->first();
    expect($user->must_change_password)->toBeFalse();
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'existing@empresa.pt']);

    $this->artisan('admin:create', ['email' => 'existing@empresa.pt'])
        ->assertExitCode(1)
        ->expectsOutputToContain('Já existe');
});
