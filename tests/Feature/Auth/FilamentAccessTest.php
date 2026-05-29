<?php

use App\Enums\UserRole;
use App\Models\User;

it('allows admin role into /admin', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
});

it('denies funcionario role with 403', function () {
    $funcionario = User::factory()->create([
        'role' => UserRole::Funcionario,
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($funcionario)->get('/admin');

    $response->assertStatus(403);
});
