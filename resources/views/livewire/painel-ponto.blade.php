<div>
    {{-- Notification area --}}
    <div
        x-data="{ show: false, message: '', type: 'info' }"
        x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition
        x-cloak
        aria-live="polite"
        aria-atomic="true"
        class="fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-lg shadow-lg text-white text-sm"
        :class="{
            'bg-green-600': type === 'success',
            'bg-red-600': type === 'error',
            'bg-blue-600': type === 'info',
            'bg-amber-600': type === 'warning'
        }"
    >
        <p x-text="message"></p>
    </div>

    <div class="space-y-6">
        {{-- Current time --}}
        <div class="text-center">
            <p class="text-sm text-gray-500">{{ now()->timezone('Europe/Lisbon')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
            <p class="text-3xl font-bold text-gray-900 tabular-nums" wire:poll.10s>
                {{ now()->timezone('Europe/Lisbon')->format('H:i') }}
            </p>
        </div>

        {{-- Action button --}}
        <div class="text-center">
            @if ($diaConcluido)
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-green-50 text-green-700 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Dia concluído</span>
                </div>
            @elseif ($botaoTexto)
                <div x-data="gpsCapture()" x-init="init()">
                    <button
                        type="button"
                        x-on:click="registar()"
                        :disabled="loading"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        style="min-height: 44px; font-size: 16px;"
                    >
                        <span x-show="loading" class="animate-spin" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span x-text="loading ? 'A registar…' : '{{ $botaoTexto }}'"></span>
                    </button>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="mt-2 text-sm font-semibold text-gray-900">Sem marcações</h2>
                    <p class="mt-1 text-sm text-gray-500">O dia ainda não começou.</p>
                </div>
            @endif
        </div>

        {{-- Day's marcacoes list --}}
        @if ($marcacoes->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Marcações de hoje</h2>
                <ul class="divide-y divide-gray-100" role="list">
                    @foreach ($marcacoes as $marcacao)
                        <li class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ match($marcacao->tipo) {
                                        \App\Enums\TipoMarcacao::Entrada => 'bg-green-100 text-green-800',
                                        \App\Enums\TipoMarcacao::InicioPausa => 'bg-amber-100 text-amber-800',
                                        \App\Enums\TipoMarcacao::FimPausa => 'bg-blue-100 text-blue-800',
                                        \App\Enums\TipoMarcacao::Saida => 'bg-gray-100 text-gray-800',
                                    } }}">
                                    {{ match($marcacao->tipo) {
                                        \App\Enums\TipoMarcacao::Entrada => 'Entrada',
                                        \App\Enums\TipoMarcacao::InicioPausa => 'Início pausa',
                                        \App\Enums\TipoMarcacao::FimPausa => 'Fim pausa',
                                        \App\Enums\TipoMarcacao::Saida => 'Saída',
                                    } }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 tabular-nums">
                                    {{ $marcacao->data_hora->timezone('Europe/Lisbon')->format('H:i') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                @if (! $marcacao->gps_indisponivel)
                                    <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-label="GPS capturado">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    <span>GPS</span>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-label="GPS indisponível">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    <span class="text-gray-400">Sem GPS</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script>
        function gpsCapture() {
            return {
                loading: false,
                init() {},
                registar() {
                    this.loading = true;

                    if ('geolocation' in navigator) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                this.$wire.registarMarcacao(
                                    position.coords.latitude,
                                    position.coords.longitude
                                ).then(() => { this.loading = false; });
                            },
                            () => {
                                this.$wire.registarMarcacao(null, null)
                                    .then(() => { this.loading = false; });
                            },
                            { timeout: 5000, enableHighAccuracy: true }
                        );
                    } else {
                        this.$wire.registarMarcacao(null, null)
                            .then(() => { this.loading = false; });
                    }
                }
            };
        }
    </script>
</div>
