<?php

use App\Enums\TipoMarcacao;
use App\Livewire\PainelPonto;
use App\Models\Marcacao;
use App\Models\User;
use App\Services\MarcacaoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

function prepareUserForSaida(): User
{
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $service = app(MarcacaoService::class);
    $service->registar($user, TipoMarcacao::Entrada);
    $service->registar($user, TipoMarcacao::InicioPausa);
    $service->registar($user, TipoMarcacao::FimPausa);

    return $user;
}

it('accepts saida with no detalhes and no foto', function () {
    $user = prepareUserForSaida();
    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarSaida', null, null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    expect($saida)->not->toBeNull();
    expect($saida->detalhes)->toBeNull();
    expect($saida->foto_path)->toBeNull();
});

it('accepts saida with detalhes only', function () {
    $user = prepareUserForSaida();
    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarSaida', 'Trabalhei no relatório mensal.', null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    expect($saida->detalhes)->toBe('Trabalhei no relatório mensal.');
});

it('accepts saida with foto only', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    $foto = UploadedFile::fake()->image('trabalho.jpg', 800, 600);

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    expect($saida->foto_path)->not->toBeNull();
    Storage::assertExists($saida->foto_path);
});

it('rejects detalhes longer than 2000 chars', function () {
    $user = prepareUserForSaida();
    $this->actingAs($user);

    livewire(PainelPonto::class)
        ->call('registarSaida', str_repeat('a', 2001), null, null, null)
        ->assertHasErrors(['detalhes']);
});

it('rejects foto larger than 5MB', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    $foto = UploadedFile::fake()->image('grande.jpg')->size(6000);

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasErrors(['foto']);
});

it('rejects file with falsified mime extension', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    // Create a PHP file disguised as .jpg
    $foto = UploadedFile::fake()->createWithContent(
        'malicious.jpg',
        '<?php echo "hacked"; ?>'
    );

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasErrors(['foto']);
});

it('accepts jpeg, png, webp', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    $foto = UploadedFile::fake()->image('foto.png', 400, 300);

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    expect($saida->foto_path)->not->toBeNull();
});

it('resizes foto to max 1920px width', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    $foto = UploadedFile::fake()->image('grande.jpg', 3000, 2000);

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    $storedPath = Storage::path($saida->foto_path);
    $size = getimagesize($storedPath);
    expect($size[0])->toBeLessThanOrEqual(1920);
});

it('stores foto via default filesystem driver', function () {
    Storage::fake();
    $user = prepareUserForSaida();
    $this->actingAs($user);

    $foto = UploadedFile::fake()->image('foto.jpg', 400, 300);

    livewire(PainelPonto::class)
        ->set('foto', $foto)
        ->call('registarSaida', null, null, null, null)
        ->assertHasNoErrors();

    $saida = Marcacao::where('user_id', $user->id)
        ->where('tipo', TipoMarcacao::Saida)
        ->first();

    Storage::assertExists($saida->foto_path);
});

it('returns signed url with 30min expiration', function () {
    $user = User::factory()->funcionario()->create(['must_change_password' => false]);
    $marcacao = Marcacao::factory()->create([
        'user_id' => $user->id,
        'tipo' => TipoMarcacao::Saida,
        'data_civil' => now()->timezone('Europe/Lisbon')->toDateString(),
        'foto_path' => 'marcacoes/fotos/test.jpg',
    ]);

    $url = $marcacao->fotoUrlAssinada();

    expect($url)->not->toBeNull();
    expect($url)->toContain('signature=');
    expect($url)->toContain('expires=');
});
