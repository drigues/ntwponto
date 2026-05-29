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
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa }}</h1>
        <p>Relatório geral</p>
        <p>Período: {{ $de }} a {{ $ate }}</p>
    </div>

    <div class="meta">
        <p>Data de emissão: {{ $dataEmissao }}</p>
    </div>

    @if($linhas->isEmpty())
        <p>Sem dados neste período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Cargo</th>
                    <th>Dias trabalhados</th>
                    <th>Total horas</th>
                    <th>Média diária</th>
                    <th>Inconsistências</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as $linha)
                    <tr>
                        <td>{{ $linha->nome }}</td>
                        <td>{{ $linha->cargo ?? '—' }}</td>
                        <td>{{ $linha->diasLabel() }}</td>
                        <td>{{ $linha->totalFormatado() }}</td>
                        <td>{{ $linha->mediaFormatada() }}</td>
                        <td>{{ $linha->inconsistenciasLabel() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Documento gerado automaticamente por {{ $empresa }} em {{ $dataEmissao }}</p>
    </div>
</body>
</html>
