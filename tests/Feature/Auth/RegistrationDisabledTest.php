<?php

it('returns 404 on /register', function () {
    $this->get('/register')->assertStatus(404);
});

it('returns 404 on POST /register', function () {
    $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(404);
});
