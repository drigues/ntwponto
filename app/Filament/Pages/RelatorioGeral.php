<?php

namespace App\Filament\Pages;

use App\DTOs\LinhaRelatorio;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\RelatorioGeralService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class RelatorioGeral extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Relatório geral';

    protected static ?string $title = 'Relatório geral';

    protected static ?string $slug = 'relatorios-geral';

    protected string $view = 'filament.pages.relatorio-geral';

    public string $de;

    public string $ate;

    /** @var array<int> */
    public array $userIds = [];

    public function mount(): void
    {
        $hoje = CarbonImmutable::now('Europe/Lisbon');
        $this->de = $hoje->startOfMonth()->toDateString();
        $this->ate = $hoje->endOfMonth()->toDateString();
    }

    public function filtrar(): void
    {
        // Triggers re-render
    }

    /**
     * @return Collection<int, LinhaRelatorio>
     */
    public function getLinhasProperty(): Collection
    {
        $service = app(RelatorioGeralService::class);

        return $service->gerar(
            CarbonImmutable::parse($this->de),
            CarbonImmutable::parse($this->ate),
            ! empty($this->userIds) ? $this->userIds : null,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getFuncionariosProperty(): array
    {
        return User::where('role', UserRole::Funcionario)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
