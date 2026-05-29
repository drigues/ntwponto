<div>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Relatório de {{ $targetUser->name }}</h2>
    </div>

    {{-- Filtros --}}
    <form wire:submit="filtrar" class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="de" class="block text-sm font-medium text-gray-700">De</label>
            <input type="date" id="de" wire:model="de" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
        </div>
        <div>
            <label for="ate" class="block text-sm font-medium text-gray-700">Até</label>
            <input type="date" id="ate" wire:model="ate" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500" style="min-height: 44px;">
            Filtrar período
        </button>
    </form>

    {{-- Totais --}}
    @if($relatorio->diasTrabalhados > 0)
        <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Dias trabalhados</p>
                <p class="text-2xl font-bold text-gray-900">{{ $relatorio->diasTrabalhados }} dias</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Total de horas</p>
                <p class="text-2xl font-bold text-gray-900">{{ $relatorio->totalFormatado() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Média diária</p>
                <p class="text-2xl font-bold text-gray-900">{{ $relatorio->mediaFormatada() }}</p>
            </div>
        </div>
    @endif

    {{-- Dias --}}
    @if($relatorio->dias->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <p class="text-lg font-medium text-gray-900">Sem marcações neste período</p>
            <p class="mt-1 text-sm text-gray-500">Ajusta as datas ou verifica se existem marcações registadas.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($relatorio->dias as $dia)
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-medium text-gray-900">
                            {{ \Carbon\Carbon::parse($dia->data)->translatedFormat('d/m/Y — l') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($dia->editado)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Editado</span>
                            @endif
                            @if($dia->inconsistencia)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Inconsistência</span>
                            @endif
                            @if($dia->emCurso)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Em curso</span>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="pb-2 pr-4" scope="col">Tipo</th>
                                    <th class="pb-2 pr-4" scope="col">Hora</th>
                                    <th class="pb-2" scope="col">GPS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dia->marcacoes as $marcacao)
                                    <tr>
                                        <td class="py-1 pr-4 text-gray-900">
                                            {{ match($marcacao->tipo) {
                                                \App\Enums\TipoMarcacao::Entrada => 'Entrada',
                                                \App\Enums\TipoMarcacao::InicioPausa => 'Início pausa',
                                                \App\Enums\TipoMarcacao::FimPausa => 'Fim pausa',
                                                \App\Enums\TipoMarcacao::Saida => 'Saída',
                                            } }}
                                        </td>
                                        <td class="py-1 pr-4 text-gray-900">{{ $marcacao->data_hora->timezone('Europe/Lisbon')->format('H:i') }}</td>
                                        <td class="py-1 text-gray-500">
                                            @if($marcacao->gps_indisponivel)
                                                <span class="text-gray-400">Indisponível</span>
                                            @else
                                                <span class="text-green-600">Capturado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $saida = $dia->marcacoes->firstWhere('tipo', \App\Enums\TipoMarcacao::Saida);
                    @endphp

                    @if($saida && $saida->detalhes)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-sm text-gray-500">Detalhes</p>
                            <p class="text-sm text-gray-900">{{ $saida->detalhes }}</p>
                        </div>
                    @endif

                    @if($saida && $saida->foto_path)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <a href="{{ $saida->fotoUrlAssinada() }}" target="_blank" rel="noopener noreferrer" class="text-sm text-amber-600 hover:text-amber-800">
                                Ver foto
                            </a>
                        </div>
                    @endif

                    <div class="mt-3 pt-3 border-t border-gray-100 text-right">
                        <span class="text-sm font-medium text-gray-900">Total: {{ $dia->horasFormatadas() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
