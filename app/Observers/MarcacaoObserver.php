<?php

namespace App\Observers;

use App\Models\Marcacao;
use Illuminate\Support\Facades\Cache;

class MarcacaoObserver
{
    public function created(Marcacao $marcacao): void
    {
        $this->invalidateCache($marcacao);
    }

    public function updated(Marcacao $marcacao): void
    {
        $this->invalidateCache($marcacao);
    }

    public function deleted(Marcacao $marcacao): void
    {
        $this->invalidateCache($marcacao);
    }

    private function invalidateCache(Marcacao $marcacao): void
    {
        // Flush all relatorio:geral cache entries
        // With file/array driver, we increment a version to invalidate
        Cache::increment('relatorio:geral:version');
    }
}
