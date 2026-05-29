<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #666; }
        .meta { margin-bottom: 15px; }
        .meta p { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; font-size: 10px; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .day-header { background-color: #fef3c7; font-weight: bold; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-info { background-color: #dbeafe; color: #1e40af; }
        .totals { margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
        .totals table { border: none; }
        .totals td { border: none; font-size: 12px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa }}</h1>
        <p>Relatório individual — {{ $user->name }}</p>
        <p>Período: {{ $de }} a {{ $ate }}</p>
    </div>

    <div class="meta">
        <p>Data de emissão: {{ $dataEmissao }}</p>
    </div>

    @if($relatorio->dias->isEmpty())
        <p>Sem marcações neste período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Dia</th>
                    <th>Entrada</th>
                    <th>Início pausa</th>
                    <th>Fim pausa</th>
                    <th>Saída</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->dias as $dia)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($dia->data)->format('d/m/Y') }}</td>
                        @php
                            $entrada = $dia->marcacoes->firstWhere('tipo', \App\Enums\TipoMarcacao::Entrada);
                            $inicioPausa = $dia->marcacoes->firstWhere('tipo', \App\Enums\TipoMarcacao::InicioPausa);
                            $fimPausa = $dia->marcacoes->firstWhere('tipo', \App\Enums\TipoMarcacao::FimPausa);
                            $saida = $dia->marcacoes->firstWhere('tipo', \App\Enums\TipoMarcacao::Saida);
                        @endphp
                        <td>{{ $entrada ? $entrada->data_hora->timezone('Europe/Lisbon')->format('H:i') : '—' }}</td>
                        <td>{{ $inicioPausa ? $inicioPausa->data_hora->timezone('Europe/Lisbon')->format('H:i') : '—' }}</td>
                        <td>{{ $fimPausa ? $fimPausa->data_hora->timezone('Europe/Lisbon')->format('H:i') : '—' }}</td>
                        <td>{{ $saida ? $saida->data_hora->timezone('Europe/Lisbon')->format('H:i') : '—' }}</td>
                        <td>{{ $dia->horasFormatadas() }}</td>
                        <td>
                            @if($dia->editado)<span class="badge badge-warning">Editado</span>@endif
                            @if($dia->inconsistencia)<span class="badge badge-danger">Inconsistência</span>@endif
                            @if($dia->emCurso)<span class="badge badge-info">Em curso</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Dias trabalhados: {{ $relatorio->diasTrabalhados }}</td>
                    <td>Total de horas: {{ $relatorio->totalFormatado() }}</td>
                    <td>Média diária: {{ $relatorio->mediaFormatada() }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Documento gerado automaticamente por {{ $empresa }} em {{ $dataEmissao }}</p>
    </div>
</body>
</html>
