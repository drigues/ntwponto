<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AdminCreateCommand extends Command
{
    protected $signature = 'admin:create {email} {--password=}';

    protected $description = 'Cria um utilizador admin';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->error("Já existe um utilizador com o email {$email}.");

            return self::FAILURE;
        }

        $passwordProvided = $this->option('password');
        $password = $passwordProvided ?: Str::password(16);

        $user = User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'must_change_password' => $passwordProvided ? false : true,
            'email_verified_at' => now(),
        ]);

        $this->info('Admin criado com sucesso:');
        $this->info("  Email: {$user->email}");

        if (! $passwordProvided) {
            $this->info("  Password: {$password}");
            $this->warn('  Guarda esta password — não será mostrada novamente.');
            $this->warn('  O utilizador terá de alterar a password no primeiro login.');
        }

        return self::SUCCESS;
    }
}
