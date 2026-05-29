<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RelatorioPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GerarRelatorioPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $tipo,
        public int $userId,
        public int $requestedBy,
        public string $de,
        public string $ate,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(RelatorioPdfService $service): void
    {
        $de = CarbonImmutable::parse($this->de);
        $ate = CarbonImmutable::parse($this->ate);

        if ($this->tipo === 'geral') {
            $service->gerarGeralPdf($de, $ate);
        } else {
            $user = User::findOrFail($this->userId);
            $service->gerarIndividualPdf($user, $de, $ate);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('security')->critical('PDF generation failed', [
            'tipo' => $this->tipo,
            'user_id' => $this->userId,
            'requested_by' => $this->requestedBy,
            'error' => $exception->getMessage(),
        ]);
    }
}
