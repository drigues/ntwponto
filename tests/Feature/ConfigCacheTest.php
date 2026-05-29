<?php

use App\Sentry\RemovePii;
use Sentry\Event;
use Sentry\UserDataBag;

it('caches config without errors', function () {
    $exitCode = Artisan::call('config:cache');

    expect($exitCode)->toBe(0);

    // Cleanup
    Artisan::call('config:clear');
});

it('sentry before_send removes email and ip from event', function () {
    $event = Event::createEvent();
    $user = new UserDataBag;
    $user->setEmail('test@example.com');
    $user->setIpAddress('127.0.0.1');
    $event->setUser($user);

    $result = RemovePii::handle($event);

    expect($result)->not->toBeNull();
    expect($result->getUser()->getEmail())->toBeNull();
    expect($result->getUser()->getIpAddress())->toBeNull();
});
