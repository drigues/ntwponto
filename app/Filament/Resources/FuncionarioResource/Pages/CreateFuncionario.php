<?php

namespace App\Filament\Resources\FuncionarioResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\FuncionarioResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateFuncionario extends CreateRecord
{
    protected static string $resource = FuncionarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Funcionario;
        $data['must_change_password'] = true;
        $data['password'] = Str::random(16);

        return $data;
    }
}
