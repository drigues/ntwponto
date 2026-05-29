<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RelatorioIndividualService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class RelatorioIndividual extends Component
{
    public ?int $userId = null;

    public string $de;

    public string $ate;

    public function mount(?int $userId = null): void
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        $hoje = CarbonImmutable::now('Europe/Lisbon');
        $this->de = $hoje->startOfMonth()->toDateString();
        $this->ate = $hoje->endOfMonth()->toDateString();

        if ($userId !== null) {
            // Only admin can view other users
            abort_unless($authUser->role === UserRole::Admin, Response::HTTP_FORBIDDEN);
            $this->userId = $userId;
        } else {
            $this->userId = $authUser->id;
        }
    }

    public function filtrar(): void
    {
        // Triggers re-render with updated dates
    }

    public function render(): View
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        $targetUser = $this->userId === $authUser->id
            ? $authUser
            : User::findOrFail($this->userId);

        // Funcionario can only see own data
        if ($authUser->role !== UserRole::Admin && $targetUser->id !== $authUser->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $service = app(RelatorioIndividualService::class);
        $relatorio = $service->gerar(
            $targetUser,
            CarbonImmutable::parse($this->de),
            CarbonImmutable::parse($this->ate),
        );

        return view('livewire.relatorio-individual', [
            'relatorio' => $relatorio,
            'targetUser' => $targetUser,
        ]);
    }
}
