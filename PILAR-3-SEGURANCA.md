# PILAR-3-SEGURANCA.md
# Segurança, Criptografia, Backups e Operações
# Ler antes de iniciar qualquer feature.

---

## Input — regra absoluta

```php
// ❌ NUNCA
Model::create($request->all());
Model::create($request->except('_token'));

// ✅ SEMPRE — Form Request com regras explícitas
class StoreOrderRequest extends FormRequest {
    public function rules(): array {
        return [
            'name'             => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email:rfc,dns', 'max:255'],
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'payment_method'   => ['required', 'in:card,mbway,transfer'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.id'       => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'website'          => ['max:0'], // honeypot — falha se preenchido
        ];
    }
}
```

---

## Formulários Públicos — 3 camadas obrigatórias

**Camada 1 — Honeypot:**
```html
<div style="display:none" aria-hidden="true">
    <input type="text" name="website" tabindex="-1" autocomplete="off">
</div>
```
Resposta ao bot: retornar sucesso falso — nunca revelar que foi bloqueado.

**Camada 2 — Rate Limiting:**
```php
Route::post('/contact',        ...)->middleware('throttle:10,1');
Route::post('/login',          ...)->middleware('throttle:5,1');
Route::post('/register',       ...)->middleware('throttle:3,5');
Route::post('/password/forgot',...)->middleware('throttle:3,10');
```

**Camada 3 — Validação estrita:**
```php
'email'    => ['required', 'email:rfc,dns', 'max:255'],
'mensagem' => ['required', 'string', 'max:2000'],
'website'  => ['max:0'], // honeypot
```

**Monitorização de spam:**
```php
Schema::create('spam_logs', function (Blueprint $table) {
    $table->id();
    $table->string('tipo');       // honeypot | rate_limit | pattern
    $table->string('formulario');
    $table->string('ip_hash');    // SHA-256 — nunca IP raw
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

---

## Autenticação — Protecção Brute Force

```php
// Lockout progressivo
$key      = 'login:' . $request->ip();
$attempts = Cache::get($key, 0);

if ($attempts >= 10) {
    Cache::put($key . ':lockout', now()->addMinutes(15), now()->addMinutes(15));
    event(new BruteForceDetected($request->ip()));
}

// Login bem-sucedido — limpar contador e regenerar sessão
Cache::forget($key);
$request->session()->regenerate();
```

**Sessão — configuração segura:**
```php
// config/session.php
'secure'    => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
'lifetime'  => 120,
```

**Password — política:**
```php
Password::min(8)->letters()->mixedCase()->numbers()->uncompromised()
```

---

## Criptografia de dados sensíveis

```php
// Cast 'encrypted' — Laravel encripta via APP_KEY automaticamente
class User extends Model {
    protected $casts = [
        'nif'     => 'encrypted',
        'iban'    => 'encrypted',
        'phone'   => 'encrypted',
        'address' => 'encrypted',
    ];
}
// Na BD: base64:aBcDeFgH... (ilegível sem APP_KEY)
// Na app: valor original transparente

// APP_KEY — nunca no Git, sempre em variáveis de ambiente do servidor
// Rotação: php artisan key:generate --force
```

---

## Queries — sem SQL Injection

```php
// ❌ NUNCA
DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ SEMPRE
User::where('email', $email)->first();
DB::select("SELECT * FROM users WHERE email = ?", [$email]);

// Migrations de dados — Eloquent/Query Builder
// Nunca DB::statement() com sintaxe específica de motor de BD
User::updateOrCreate(['email' => $email], ['name' => $name]); // ✅
DB::statement("INSERT ... ON CONFLICT DO NOTHING");            // ❌
```

---

## Autorização

```php
// Policy para cada modelo com acções sensíveis
class DocumentPolicy {
    public function update(User $user, Document $document): bool {
        return $user->id === $document->user_id;
    }
    public function delete(User $user, Document $document): bool {
        return $user->hasRole('admin');
    }
}

// Controllers — authorize() obrigatório
public function update(Request $request, Document $document): Response {
    $this->authorize('update', $document);
}
```

---

## Uploads

```php
// Validar MIME type real — nunca confiar só na extensão
$mimeType = $request->file('imagem')->getMimeType();
$allowed  = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mimeType, $allowed)) {
    throw ValidationException::withMessages(['imagem' => 'Tipo não permitido.']);
}

'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
// Original nunca acessível publicamente
// Servir via URL assinada com expiração
$url = Storage::temporaryUrl($path, now()->addMinutes(30));
```

**HTML do utilizador — sanitizar antes de guardar:**
```php
// composer require ezyang/htmlpurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,h2,h3,a[href]');
$purifier = new HTMLPurifier($config);
$safe = $purifier->purify($input);
```

**Output em views:**
```blade
{{ $var }}    ← sempre (escapa HTML automaticamente)
{!! $var !!}  ← só conteúdo controlado e sanitizado
```

---

## Headers de segurança

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders {
    public function handle(Request $request, Closure $next): Response {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Produção:
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        return $response;
    }
}
// Registar em bootstrap/app.php → ->withMiddleware()
```

---

## Exposição e DNS

**robots.txt — bloquear sempre:**
```
Disallow: /admin
Disallow: /filament
Disallow: /horizon
Disallow: /telescope
Disallow: /_debugbar
```

**DNS anti-phishing — configurar no domínio de produção:**
```
SPF:   TXT @ "v=spf1 include:[provider] ~all"
DKIM:  configurar via provider de email
DMARC: TXT _dmarc "v=DMARC1; p=quarantine; pct=100"
```

---

## Infraestrutura — Checklist de Servidor

```bash
# Portas abertas — só o necessário
# 22 (SSH) · 80 (HTTP→HTTPS) · 443 (HTTPS)
# Fechar: 3306, 5432, 6379, 8080

# SSH — desactivar password auth
PasswordAuthentication no
PermitRootLogin no

# fail2ban
sudo apt install fail2ban
# bantime=3600 · findtime=600 · maxretry=5

# Permissões
chmod 600 .env
chmod -R 775 storage bootstrap/cache
```

---

## Monitorização de Segurança

**Canal de logging dedicado:**
```php
// config/logging.php
'security' => [
    'driver'     => 'daily',
    'path'       => storage_path('logs/security.log'),
    'days'       => 30,
    'permission' => 0600,
],
```

**Eventos a registar:**
```php
Log::channel('security')->info('auth.login.success',    ['user_id'    => $id,    'ip_hash' => $hash]);
Log::channel('security')->warning('auth.login.failed',  ['email_hash' => $hash,  'attempts' => $n]);
Log::channel('security')->warning('auth.brute_force',   ['ip_hash'    => $hash,  'attempts' => $n]);
Log::channel('security')->info('form.honeypot',         ['form'       => '...',  'ip_hash' => $hash]);
Log::channel('security')->warning('form.rate_limited',  ['form'       => '...',  'ip_hash' => $hash]);
Log::channel('security')->warning('upload.invalid_mime',['mime'       => '...']);
```

**Sentry — sem PII:**
```php
// config/sentry.php
'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
    if ($user = $event->getUser()) {
        $user->setEmail(null);
        $user->setIpAddress(null);
    }
    return $event;
},
```

---

## Variáveis de ambiente

```bash
# Produção — obrigatório
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error

# Nunca commitar — adicionar ao .gitignore
.env
.env.backup
.env.production
```

---

## Logging — sem dados pessoais

```php
// ✅ Contexto útil, sem dados sensíveis
Log::info('Payment processed', [
    'order_id' => $order->id,
    'user_id'  => $order->user_id,
    'amount'   => $order->total,
]);

// ❌ Nunca logar dados pessoais
Log::info('Action', [
    'email'    => $user->email,       // ❌ RGPD
    'password' => $data['password'],  // ❌ crítico
    'ip'       => $request->ip(),     // ❌ usar ip_hash
]);
```

---

## Auditoria — trail de alterações

```php
class Order extends Model {
    use LogsActivity; // spatie/laravel-activitylog

    protected static $logAttributes = ['status', 'total', 'payment_status'];
    protected static $logOnlyDirty  = true;
}
```

---

## Jobs resilientes

```php
class ProcessPaymentJob implements ShouldQueue {
    public int   $tries   = 3;
    public int   $timeout = 60;
    public array $backoff  = [30, 120, 300]; // 30s · 2min · 5min

    public function failed(Throwable $e): void {
        Log::channel('critical')->error('Job failed', [
            'job'       => static::class,
            'exception' => $e->getMessage(),
        ]);
    }
}
```

---

## RGPD — Dados Pessoais

**IPs — nunca guardar raw:**
```php
// ✅ Sempre hash
'ip_hash' => hash('sha256', $request->ip() . config('app.key'))

// ❌ Nunca
'ip' => $request->ip()
```

**Retenção — prazos máximos:**
```
Logs de segurança      → 30 dias → eliminar
Spam logs              → 7 dias  → eliminar
Sessões expiradas      → limpar via scheduler
Contas não verificadas → 30 dias → eliminar
```

**Scheduler de limpeza:**
```php
Schedule::call(function () {
    DB::table('sessions')
        ->where('last_activity', '<', now()->subDays(7)->timestamp)
        ->delete();
    SpamLog::where('created_at', '<', now()->subDays(7))->delete();
    User::whereNull('email_verified_at')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
})->daily();
```

**Direito ao esquecimento:**
```php
// Anonimizar — preserva integridade referencial
public function deleteAccount(User $user): void
{
    DB::transaction(function () use ($user) {
        $user->update([
            'name'  => 'Utilizador eliminado',
            'email' => 'deleted_' . $user->id . '@deleted.invalid',
            'phone' => null,
        ]);
        $user->sessions()->delete();
        $user->tokens()->delete();
        Log::channel('security')->info('account.deleted', ['user_id' => $user->id]);
    });
}
```

---

## Backups — estratégia 3-2-1

```
3 cópias dos dados
  2 em locais diferentes
    1 offsite (fora do servidor principal)

  Cópia 1 → Servidor de produção
  Cópia 2 → Forge Backups automáticos
  Cópia 3 → Backblaze B2 / AWS S3 / Hetzner Storage Box
```

```php
// config/backup.php
'destination' => [
    'disks' => ['backblaze'], // offsite obrigatório
],
'password' => env('BACKUP_ENCRYPTION_PASSWORD'), // sempre encriptado

Schedule::command('backup:run --only-db')->dailyAt('02:00')
    ->onFailure(fn() => Log::channel('critical')->error('Backup falhou!'));
Schedule::command('backup:monitor')->dailyAt('08:00');
Schedule::command('backup:clean')->dailyAt('04:00');
```

**Teste de restauro — executar mensalmente:**
```bash
# Num servidor/BD de teste — NUNCA em produção
# 1. Desencriptar e descomprimir o backup
# 2. Restaurar numa BD isolada
# 3. Verificar integridade dos dados
# 4. Documentar resultado e data do teste
```

---

## Pacotes de referência

```bash
composer require \
  spatie/laravel-activitylog \  # audit trail
  spatie/laravel-backup \       # backups automáticos
  sentry/sentry-laravel \       # monitorização de erros
  ezyang/htmlpurifier           # sanitização de HTML
```
