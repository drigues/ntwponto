<?php

use App\Jobs\GerarRelatorioPdfJob;
use Illuminate\Support\Facades\Log;

it('has 3 retries and timeout 120s', function () {
    $job = new GerarRelatorioPdfJob(
        tipo: 'individual',
        userId: 1,
        requestedBy: 1,
        de: '2026-01-01',
        ate: '2026-01-31',
    );

    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(120);
    expect($job->backoff())->toBe([30, 120, 300]);
});

it('logs failure to critical channel', function () {
    $job = new GerarRelatorioPdfJob(
        tipo: 'individual',
        userId: 1,
        requestedBy: 1,
        de: '2026-01-01',
        ate: '2026-01-31',
    );

    Log::shouldReceive('channel')
        ->with('security')
        ->once()
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once();

    $job->failed(new RuntimeException('Test failure'));
});
