<?php

namespace App\Models;

use App\Enums\TipoMarcacao;
use App\Observers\MarcacaoObserver;
use Carbon\CarbonImmutable;
use Database\Factories\MarcacaoFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property TipoMarcacao $tipo
 * @property Carbon $data_hora
 * @property string $data_civil
 * @property bool $gps_indisponivel
 * @property Carbon|null $editado_em
 */
#[ObservedBy(MarcacaoObserver::class)]
class Marcacao extends Model
{
    /** @use HasFactory<MarcacaoFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'marcacoes';

    protected $fillable = [
        'user_id',
        'tipo',
        'data_hora',
        'data_civil',
        'latitude',
        'longitude',
        'gps_indisponivel',
        'detalhes',
        'foto_path',
        'editado_por',
        'editado_em',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMarcacao::class,
            'data_hora' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gps_indisponivel' => 'boolean',
            'editado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    /** @param Builder<Marcacao> $query */
    public function scopeDoDia(Builder $query, CarbonImmutable $dia): void
    {
        $query->where('data_civil', $dia->toDateString());
    }

    /** @param Builder<Marcacao> $query */
    public function scopeDoUtilizador(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /** @param Builder<Marcacao> $query */
    public function scopeDoPeriodo(Builder $query, CarbonImmutable $de, CarbonImmutable $ate): void
    {
        $query->whereBetween('data_civil', [$de->toDateString(), $ate->toDateString()]);
    }

    public function fotoUrlAssinada(): ?string
    {
        if (! $this->foto_path) {
            return null;
        }

        return URL::signedRoute(
            'marcacao.foto',
            ['marcacao' => $this->id],
            now()->addMinutes(30),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'data_hora', 'data_civil', 'detalhes', 'editado_por'])
            ->logOnlyDirty();
    }
}
