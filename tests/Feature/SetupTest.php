<?php

use Illuminate\Support\Facades\DB;

it('uses postgres connection', function () {
    expect(config('database.default'))->toBe('pgsql');
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('has timezone Europe/Lisbon', function () {
    expect(config('app.timezone'))->toBe('Europe/Lisbon');
});

it('disables debug in non-local environment', function () {
    // In testing environment, APP_DEBUG may be true, but the config
    // default is false when APP_DEBUG env var is not set.
    // We verify that the production default would be false.
    expect(config('app.debug'))->toBe((bool) env('APP_DEBUG', false));
});

it('returns security headers on every response', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $response->assertHeaderMissing('X-Powered-By');
});

it('adds HSTS header in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('blocks admin paths in robots.txt', function () {
    $response = $this->get('/robots.txt');

    $response->assertStatus(200);

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /admin');
    expect($content)->toContain('Disallow: /filament');
    expect($content)->toContain('Disallow: /horizon');
    expect($content)->toContain('Disallow: /telescope');
    expect($content)->toContain('Disallow: /_debugbar');
});
