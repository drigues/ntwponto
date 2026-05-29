<?php

use App\Models\User;

it('redirects to /password/change when must_change_password is true', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $this->actingAs($user)
        ->get('/ponto')
        ->assertRedirect('/password/change');
});

it('allows access to /password/change route', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $response = $this->actingAs($user)->get('/password/change');

    $response->assertStatus(200);
});

it('clears must_change_password after successful change', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword1',
        'must_change_password' => true,
    ]);

    $response = $this->actingAs($user)->post('/password/change', [
        'current_password' => 'OldPassword1',
        'password' => 'N3wSecurePass!x9',
        'password_confirmation' => 'N3wSecurePass!x9',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/ponto');
    expect($user->fresh()->must_change_password)->toBeFalse();
});

it('enforces password policy on change', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword1',
        'must_change_password' => true,
    ]);

    // Too short / no uppercase / no numbers
    $response = $this->actingAs($user)->post('/password/change', [
        'current_password' => 'OldPassword1',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertSessionHasErrors('password');
    expect($user->fresh()->must_change_password)->toBeTrue();
});
