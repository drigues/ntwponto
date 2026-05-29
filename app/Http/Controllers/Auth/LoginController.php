<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Honeypot check — fake success for bots
        if ($request->filled('website')) {
            Log::channel('security')->info('form.honeypot', [
                'form' => 'login',
                'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            ]);

            return redirect()->back()->with('success', 'Login efectuado.');
        }

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'website' => ['max:0'],
        ]);

        // Check lockout
        $key = 'login:'.$request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts >= 10) {
            $lockoutKey = $key.':lockout';
            $lockoutTime = Cache::get($lockoutKey);

            if ($lockoutTime && now()->lt($lockoutTime)) {
                $remaining = now()->diffInMinutes($lockoutTime) + 1;

                Log::channel('security')->warning('auth.brute_force', [
                    'ip_hash' => hash('sha256', $request->ip().config('app.key')),
                    'attempts' => $attempts,
                ]);

                return back()->withErrors([
                    'email' => "Demasiadas tentativas. Tenta novamente em {$remaining} minutos.",
                ])->onlyInput('email');
            }
        }

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            Cache::forget($key);
            Cache::forget($key.':lockout');

            $request->session()->regenerate();

            Log::channel('security')->info('auth.login.success', [
                'user_id' => Auth::id(),
                'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            ]);

            return redirect()->intended('/ponto');
        }

        // Failed attempt
        Cache::increment($key);
        $currentAttempts = Cache::get($key, 1);
        Cache::put($key, $currentAttempts, now()->addHour());

        if ($currentAttempts >= 10) {
            Cache::put($key.':lockout', now()->addMinutes(15), now()->addMinutes(15));
        }

        Log::channel('security')->warning('auth.login.failed', [
            'email_hash' => hash('sha256', $request->input('email')),
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            'attempts' => $currentAttempts,
        ]);

        return back()->withErrors([
            'email' => 'Credenciais inválidas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
