<?php

namespace App\Livewire;

use App\Enums\TipoMarcacao;
use App\Exceptions\SequenciaInvalidaException;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\MarcacaoService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class PainelPonto extends Component
{
    use WithFileUploads;

    public ?string $tipoForcar = null;

    /** @var TemporaryUploadedFile|null */
    public $foto = null;

    public function registarMarcacao(?float $latitude = null, ?float $longitude = null): void
    {
        /** @var User $user */
        $user = auth()->user();
        $proximo = $this->proximoTipo();

        if (! $proximo) {
            $this->dispatch('notify', type: 'info', message: 'Dia já concluído.');

            return;
        }

        if ($proximo === TipoMarcacao::Saida) {
            return;
        }

        try {
            app(MarcacaoService::class)->registar($user, $proximo, $latitude, $longitude);
        } catch (SequenciaInvalidaException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function registarSaida(
        ?string $detalhes = null,
        ?float $latitude = null,
        ?float $longitude = null,
        mixed $ignoredFoto = null,
    ): void {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make(
            ['detalhes' => $detalhes, 'foto' => $this->foto],
            [
                'detalhes' => ['nullable', 'string', 'max:2000'],
                'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
        );

        if ($validator->fails()) {
            /** @var array<string, list<string>> $messages */
            $messages = $validator->errors()->toArray();
            foreach ($messages as $field => $errors) {
                foreach ($errors as $error) {
                    $this->addError($field, $error);
                }
            }

            return;
        }

        // Validate real MIME type
        if ($this->foto) {
            $realMime = $this->foto->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (! in_array($realMime, $allowedMimes, true)) {
                $this->addError('foto', 'Tipo de ficheiro não permitido.');

                return;
            }
        }

        $fotoPath = null;
        if ($this->foto) {
            try {
                $fotoPath = $this->processarFoto();
            } catch (\Throwable) {
                $this->addError('foto', 'Não foi possível processar a imagem. Verifica que é um ficheiro de imagem válido.');

                return;
            }
        }

        try {
            app(MarcacaoService::class)->registar(
                $user,
                TipoMarcacao::Saida,
                $latitude,
                $longitude,
                $detalhes,
                $fotoPath,
            );
            $this->foto = null;
        } catch (SequenciaInvalidaException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function registarMarcacaoForcar(): void
    {
        if (! $this->tipoForcar) {
            return;
        }

        $tipo = TipoMarcacao::tryFrom($this->tipoForcar);
        if (! $tipo) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        try {
            app(MarcacaoService::class)->registar($user, $tipo);
        } catch (SequenciaInvalidaException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        $this->tipoForcar = null;
    }

    /** @return Collection<int, Marcacao> */
    public function getMarcacoesDoDiaProperty(): Collection
    {
        /** @var User $user */
        $user = auth()->user();
        $dataCivil = CarbonImmutable::now('Europe/Lisbon')->toDateString();

        return Marcacao::where('user_id', $user->id)
            ->where('data_civil', $dataCivil)
            ->orderBy('data_hora')
            ->get();
    }

    public function render(): View
    {
        $proximo = $this->proximoTipo();
        $marcacoes = $this->getMarcacoesDoDiaProperty();

        $botaoTexto = match ($proximo) {
            TipoMarcacao::Entrada => 'Marcar entrada',
            TipoMarcacao::InicioPausa => 'Sair para almoço',
            TipoMarcacao::FimPausa => 'Voltar do almoço',
            TipoMarcacao::Saida => 'Marcar saída',
            null => null,
        };

        $diaConcluido = $proximo === null && $marcacoes->isNotEmpty();

        return view('livewire.painel-ponto', [
            'botaoTexto' => $botaoTexto,
            'diaConcluido' => $diaConcluido,
            'marcacoes' => $marcacoes,
            'proximoTipo' => $proximo,
        ]);
    }

    private function proximoTipo(): ?TipoMarcacao
    {
        /** @var User $user */
        $user = auth()->user();

        return app(MarcacaoService::class)->proximoTipoEsperado(
            $user,
            CarbonImmutable::now('UTC'),
        );
    }

    private function processarFoto(): string
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decodePath($this->foto->getRealPath());

        if ($image->width() > 1920) {
            $image->scaleDown(width: 1920);
        }

        $filename = 'marcacoes/fotos/'.uniqid('foto_', true).'.jpg';
        $encoded = $image->encodeUsingMediaType('image/jpeg', quality: 85);

        Storage::put($filename, (string) $encoded);

        return $filename;
    }
}
