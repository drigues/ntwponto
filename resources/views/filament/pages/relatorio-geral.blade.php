<x-filament-panels::page>
    {{-- Filtros --}}
    <form wire:submit="filtrar" class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="de" class="block text-sm font-medium text-gray-700 dark:text-gray-300">De</label>
            <input type="date" id="de" wire:model="de" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="ate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Até</label>
            <input type="date" id="ate" wire:model="ate" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="userIds" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Funcionários</label>
            <select id="userIds" wire:model="userIds" multiple class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" style="min-height: 44px;">
                @foreach($this->funcionarios as $id => $nome)
                    <option value="{{ $id }}">{{ $nome }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500" style="min-height: 44px;">
            Filtrar período
        </button>
    </form>

    {{-- Tabela --}}
    @if($this->linhas->isEmpty())
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
            <p class="text-lg font-medium text-gray-900 dark:text-gray-100">Sem dados neste período</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajusta as datas ou verifica se existem marcações registadas.</p>
        </div>
    @else
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Funcionário</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cargo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dias trabalhados</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total horas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Média diária</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Inconsistências</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->linhas as $linha)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $linha->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $linha->cargo ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $linha->diasLabel() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $linha->totalFormatado() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $linha->mediaFormatada() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $linha->inconsistenciasLabel() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('relatorio') }}?userId={{ $linha->userId }}&de={{ $this->de }}&ate={{ $this->ate }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                    Ver detalhe
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
