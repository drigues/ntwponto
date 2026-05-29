<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RelatorioPdfService
{
    public function __construct(
        private readonly RelatorioIndividualService $individualService,
        private readonly RelatorioGeralService $geralService,
    ) {}

    public function gerarIndividualPdf(User $user, CarbonImmutable $de, CarbonImmutable $ate): string
    {
        $html = $this->renderHtmlIndividual($user, $de, $ate);

        $filename = $this->gerarFilename($user->id, 'individual');

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    public function gerarGeralPdf(CarbonImmutable $de, CarbonImmutable $ate): string
    {
        $html = $this->renderHtmlGeral($de, $ate);

        $filename = $this->gerarFilename(0, 'geral');

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    public function inlineIndividualPdf(User $user, CarbonImmutable $de, CarbonImmutable $ate): Response
    {
        $html = $this->renderHtmlIndividual($user, $de, $ate);

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        /** @var Response */
        return $pdf->download("relatorio-{$user->id}-{$de->toDateString()}-{$ate->toDateString()}.pdf");
    }

    public function renderHtmlIndividual(User $user, CarbonImmutable $de, CarbonImmutable $ate): string
    {
        $relatorio = $this->individualService->gerar($user, $de, $ate);

        return view('pdf.relatorio-individual', [
            'relatorio' => $relatorio,
            'user' => $user,
            'de' => $de->toDateString(),
            'ate' => $ate->toDateString(),
            'empresa' => config('app.name'),
            'dataEmissao' => now()->format('d/m/Y'),
        ])->render();
    }

    public function renderHtmlGeral(CarbonImmutable $de, CarbonImmutable $ate): string
    {
        $linhas = $this->geralService->gerar($de, $ate);

        return view('pdf.relatorio-geral', [
            'linhas' => $linhas,
            'de' => $de->toDateString(),
            'ate' => $ate->toDateString(),
            'empresa' => config('app.name'),
            'dataEmissao' => now()->format('d/m/Y'),
        ])->render();
    }

    private function gerarFilename(int $userId, string $tipo): string
    {
        $timestamp = now()->format('Ymd-Hi');
        $hash = substr(md5(uniqid('', true)), 0, 8);

        return "pdfs/relatorios/{$timestamp}-{$userId}-{$tipo}-{$hash}.pdf";
    }
}
