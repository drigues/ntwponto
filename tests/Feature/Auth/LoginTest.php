<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password1'),
        'must_change_password' => false,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Password1',
    ]);

    $response->assertRedirect('/ponto');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password1'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

it('rate limits after 5 attempts per minute', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ]);

    $response->assertStatus(429);
});

it('triggers lockout after 10 failed attempts', function () {
    $user = User::factory()->create();

    // Simulate 10 prior failed attempts
    Cache::put('login:127.0.0.1', 10, now()->addHour());
    Cache::put('login:127.0.0.1:lockout', now()->addMinutes(15), now()->addMinutes(15));

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ]);

    $response->assertSessionHasErrors('email');
    $response->assertRedirect();
    $this->followRedirects($response);
});

it('logs honeypot trigger on login form', function () {
    Log::shouldReceive('channel')
        ->with('security')
        ->andReturnSelf();
    Log::shouldReceive('info')
        ->withArgs(fn (string $message) => $message === 'form.honeypot')
        ->once();

    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Password1',
        'website' => 'spam-bot-filled-this',
    ]);

    // Bot gets fake success — redirect, not error
    $response->assertRedirect();
    $this->assertGuest();
});

it('regenerates session on successful login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password1'),
        'must_change_password' => false,
    ]);

    $this->get('/login');
    $oldSessionId = session()->getId();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'Password1',
    ]);

    expect(session()->getId())->not->toBe($oldSessionId);
});
