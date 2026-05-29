<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('shows forgot password form', function () {
    $this->get('/password/forgot')->assertStatus(200);
});

it('sends reset link for existing email', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/password/forgot', [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
});
