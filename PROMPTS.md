# PROMPTS.md
# Ponto Eletrónico PME — Prompts de Implementação para o Claude Code
# Cada prompt é auto-contido. Executa um de cada vez, pela ordem.

---

## Como usar

1. Confirma que `CLAUDE.md`, `ENGINEERING-STANDARDS.md` e os 4 `PILAR-*.md` estão na raiz do projeto.
2. Abre uma sessão de Claude Code na raiz.
3. Copia o prompt completo, do `## Prompt N` ao fim da sua secção, e cola na sessão.
4. **Só avança para o prompt seguinte depois do anterior estar verde** (Pint, PHPStan, Pest ≥ 80%).
5. Commit no final de cada prompt com mensagem `feat: [nome do prompt]`.

---

## Prompt 1 — Setup do projeto

**Objetivo:** instalar Laravel 11 + Filament 3 + Postgres + ferramentas de qualidade e segurança.
**Dependências:** nenhuma.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (CI/CD, pacotes), `PILAR-3-SEGURANCA.md` (headers, robots, logging, variáveis ambiente), `CLAUDE.md` §4.

### RED — testes a escrever primeiro

Cria `tests/Feature/SetupTest.php`:

- `it('uses postgres connection')`
- `it('has timezone Europe/Lisbon')`
- `it('disables debug in non-local environment')`
- `it('returns security headers on every response')` — X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- `it('blocks admin paths in robots.txt')` — `/admin`, `/filament`, `/horizon`, `/telescope`, `/_debugbar`

### GREEN — implementação

- `composer create-project laravel/laravel .` (Laravel 11+).
- Configurar PostgreSQL em `.env` e `.env.testing`.
- `config/app.php`: `'timezone' => 'Europe/Lisbon'`, `'locale' => 'pt'`.
- Instalar:
  ```bash
  composer require filament/filament:^3 barryvdh/laravel-dompdf spatie/laravel-activitylog \
    spatie/laravel-backup sentry/sentry-laravel intervention/image ezyang/htmlpurifier
  composer require --dev pestphp/pest pestphp/pest-plugin-laravel \
    phpstan/phpstan nunomaduro/larastan laravel/pint
  ```
- `php artisan filament:install --panels` → criar painel `admin` em `/admin`.
- Middleware `SecurityHeaders` (PILAR-3) registado globalmente.
- `public/robots.txt` com as rotas bloqueadas (PILAR-3).
- Canal de logging `security` em `config/logging.php` (PILAR-3).
- `config/sentry.php` com `before_send` que remove PII (PILAR-3).
- `.github/workflows/ci.yml` exactamente como em PILAR-2.
- `.env.testing` configurado para testes (sqlite ou postgres de teste).
- Pest configurado com parallel + coverage no `phpunit.xml`.

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Painel `/admin` responde 302 (redirect para login).
- Headers de segurança presentes em todas as respostas.
- CI verde no GitHub.

---

## Prompt 2 — Autenticação fechada

**Objetivo:** login com email/password, sem registo público, role `admin`/`funcionario`, força de mudança de password no primeiro acesso.
**Dependências:** Prompt 1.
**Lê:** `PILAR-3-SEGURANCA.md` (autenticação, brute force, sessão, password), `CLAUDE.md` §5 e §6 (`users`).

### RED — testes a escrever primeiro

`tests/Feature/Auth/LoginTest.php`:

- `it('logs in with valid credentials')`
- `it('rejects invalid password')`
- `it('rate limits after 5 attempts per minute')`
- `it('triggers lockout after 10 failed attempts')`
- `it('logs honeypot trigger on login form')`
- `it('regenerates session on successful login')`

`tests/Feature/Auth/RegistrationDisabledTest.php`:

- `it('returns 404 on /register')`
- `it('returns 404 on POST /register')`

`tests/Feature/Auth/ForcePasswordChangeTest.php`:

- `it('redirects to /password/change when must_change_password is true')`
- `it('allows access to /password/change route')`
- `it('clears must_change_password after successful change')`
- `it('enforces password policy on change')`

`tests/Feature/Auth/FilamentAccessTest.php`:

- `it('allows admin role into /admin')`
- `it('denies funcionario role with 403')`

### GREEN — implementação

- Migration `users`: adicionar `role` (enum admin/funcionario, default funcionario), `cargo` nullable, `must_change_password` boolean default true, `softDeletes()`.
- `User` model: casts (`role` → Enum, `must_change_password` → bool), `HasRoles` se aplicável, traits `SoftDeletes`.
- Enum `UserRole` em `app/Enums/`.
- Remover Breeze/registo: desactivar rotas `register` (ou se usares Fortify, `features` sem `registration`).
- Login form com honeypot (campo `website` escondido) + `throttle:5,1`.
- Implementar lockout progressivo (PILAR-3).
- Middleware `EnsurePasswordIsChanged`: se `must_change_password = true` e rota actual ≠ `/password/change` ou `/logout` → redirect.
- Rota e Livewire component `PasswordChange`.
- Restringir painel Filament: `app/Providers/Filament/AdminPanelProvider.php` → `canAccessPanel` verifica `role === admin`.
- Comando `php artisan admin:create {email} {--password=}`: cria primeiro admin com `must_change_password = true` (se password gerada) ou false (se fornecida explicitamente). Gera password aleatória forte se omitida e imprime-a uma única vez.
- `config/session.php`: `secure=true`, `http_only=true`, `same_site=lax` (PILAR-3).
- Logging: `auth.login.success`, `auth.login.failed`, `auth.brute_force` no canal `security`.

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- `php artisan admin:create admin@empresa.pt` cria utilizador admin e devolve password.
- Login do admin entra em `/admin`; login de funcionário em `/admin` responde 403.
- Após criar funcionário no painel (Prompt 3), o primeiro acesso obriga a mudança de password.
- `/register` devolve 404.

---

## Prompt 3 — CRUD de funcionários no painel admin

**Objetivo:** admin cria, edita, desactiva funcionários e redefine passwords.
**Dependências:** Prompts 1–2.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (SOLID, DTOs), `PILAR-4-UI-UX.md` (copy, estados), `CLAUDE.md` §5 (criação) e §8.2 (painel).

### RED — testes a escrever primeiro

`tests/Feature/Admin/FuncionariosTest.php`:

- `it('lists only users with role funcionario by default')`
- `it('creates funcionario with must_change_password true')`
- `it('generates a strong random password when none provided')`
- `it('rejects duplicate emails')`
- `it('soft deletes funcionario (desactivar)')`
- `it('resets password and sets must_change_password back to true')`
- `it('logs activity on create/update/delete')`
- `it('blocks non-admin from accessing the resource')`

### GREEN — implementação

- Filament Resource `FuncionarioResource` (model `User`, query scoped a `role = funcionario`).
- Form: `name`, `email`, `cargo`, password (campo opcional — se vazio, gera aleatória ao guardar).
- Action "Redefinir password" → gera nova, marca `must_change_password = true`, mostra password ao admin uma única vez.
- Action "Desactivar/Reactivar" → soft delete / restore.
- Lista com filtros (activos/inactivos), search por nome/email.
- Activitylog activo no Model (`LogsActivity`, `logOnlyDirty`).
- Copy em sentence case, sem ALL CAPS, botões "verbo + objecto" (PILAR-4).

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Admin cria funcionário → recebe password gerada → funcionário faz login → é forçado a mudar password.
- Lista vazia mostra estado vazio com CTA "Adicionar funcionário" (PILAR-4).
- Todas as alterações ficam no `activity_log`.

---

## Prompt 4 — Tabela `marcacoes` + Service com state machine

**Objetivo:** modelo de dados, service de domínio e regras de sequência válida — sem UI ainda.
**Dependências:** Prompts 1–3.
**Lê:** `PILAR-1-TDD.md`, `PILAR-2-BOAS-PRATICAS.md` (Service Layer, DTOs, scopes), `CLAUDE.md` §6 (`marcacoes`), §7.1 (sequência), §7.2 (cálculo de horas).

### RED — testes a escrever primeiro

`tests/Unit/Services/MarcacaoServiceTest.php`:

- `it('registers entrada when no marcacoes today')`
- `it('rejects entrada when already exists today')` — `SequenciaInvalidaException`
- `it('registers inicio_pausa only after entrada')`
- `it('registers fim_pausa only after inicio_pausa')`
- `it('registers saida only after fim_pausa')`
- `it('rejects out-of-order sequence in any combination')`
- `it('stores latitude and longitude when provided')`
- `it('marks gps_indisponivel when coords are null')`
- `it('uses Europe/Lisbon to define data civil')` — marcação às 23:00 UTC de 31/12 = dia 1/1 em Lisboa (no inverno) ou cenário equivalente
- `it('enforces unique (user_id, tipo, data_civil)')` — constraint DB

`tests/Unit/Services/CalculoHorasTest.php`:

- `it('computes horas trabalhadas correctly')`
- `it('returns null when day is incomplete')`
- `it('flags inconsistency when intermediate marcacao is missing')`

`tests/Unit/Models/MarcacaoTest.php`:

- `it('casts tipo to TipoMarcacao enum')`
- `it('scopes doDia for given date')`
- `it('scopes doUtilizador and doPeriodo')`

### GREEN — implementação

- Migration `marcacoes` com todos os campos da §6 + índices `(user_id, data_hora)` e `(data_hora)` + constraint unique composto sobre `(user_id, tipo, data_civil)` via coluna gerada ou via lógica no service (preferível: coluna gerada `data_civil` derivada de `data_hora` em hora de Lisboa, com unique nessa coluna + user_id + tipo).
- Enum `TipoMarcacao` em `app/Enums/`.
- Model `Marcacao`: casts (incluindo enum), relação `user()`, scopes `doDia`, `doUtilizador`, `doPeriodo`, `comInconsistencias`.
- DTO `MarcacaoData` (imutável, readonly).
- Exception `SequenciaInvalidaException`.
- `MarcacaoService`:
  - `registar(User $user, TipoMarcacao $tipo, ?Coordenadas $coords): Marcacao`
  - `proximoTipoEsperado(User $user, CarbonImmutable $dia): ?TipoMarcacao`
  - Calcula `data_civil` em `Europe/Lisbon` antes de gravar.
- `CalculoHorasService`:
  - `horasDoDia(User $user, CarbonImmutable $dia): ?int` (segundos ou minutos)
  - `temInconsistencias(User $user, CarbonImmutable $dia): bool`
- Activitylog activo no Model.

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Cobertura do `MarcacaoService` ≥ 90% (regras de negócio — PILAR-1).
- Constraint unique testada com tentativa de duplicação.
- Caso de fuso horário (UTC vs Lisboa) coberto por teste explícito.

---

## Prompt 5 — UI de marcação para o funcionário

**Objetivo:** ecrã do funcionário com botão contextual, lista de marcações do dia e captura de GPS.
**Dependências:** Prompts 1–4.
**Lê:** `PILAR-4-UI-UX.md` (4 estados, copy, acessibilidade, responsividade), `CLAUDE.md` §8.1 (fluxo), §7.4 (GPS).

### RED — testes a escrever primeiro

`tests/Feature/Livewire/PainelPontoTest.php`:

- `it('redirects unauthenticated to login')`
- `it('redirects to /password/change when must_change_password')`
- `it('shows "Marcar entrada" when day is empty')`
- `it('shows "Sair para almoço" after entrada')`
- `it('shows "Voltar do almoço" after inicio_pausa')`
- `it('shows "Marcar saída" with form after fim_pausa')`
- `it('shows "Dia concluído" after saida')`
- `it('records latitude and longitude when submitted')`
- `it('records gps_indisponivel=true when coords omitted')`
- `it('does not block marcacao when GPS unavailable')`
- `it('rejects out-of-order action attempts')` — força via Livewire `call()` directo
- `it('lists marcacoes of the day with hour and gps status')`

### GREEN — implementação

- Rota `GET /ponto` (auth + middleware `EnsurePasswordIsChanged`).
- Livewire 3 component `PainelPonto`.
- Estado calculado server-side via `MarcacaoService::proximoTipoEsperado()`.
- Botão contextual com texto exacto da tabela §8.1.
- Captura GPS em Alpine.js antes do submit:
  - `navigator.geolocation.getCurrentPosition(...)` com timeout 5s
  - Em sucesso: passar `{lat, lng}` para a action Livewire
  - Em erro/negação/timeout: passar `null` e marcar `gps_indisponivel = true`
- Lista do dia: hora local Lisboa, ícone se GPS capturado, totais "em curso" se aplicável.
- 4 estados de PILAR-4 em todos os componentes com dados.
- Acessibilidade: focus visible, `aria-live="polite"` em mensagens, `aria-label` em botões com ícone.
- Responsividade: mobile-first, touch targets ≥ 44×44, inputs `font-size ≥ 16px`.
- `prefers-reduced-motion` respeitado (PILAR-4).

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Testado a 320px sem cortes.
- Negar permissão de GPS no browser → marcação regista com `gps_indisponivel=true`.
- Tentativa de "Sair para almoço" sem `entrada` lança erro tratado e mostra toast.

---

## Prompt 6 — Saída com detalhes + foto

**Objetivo:** ao marcar `saida`, recolher texto opcional e foto opcional com validações de segurança e redimensionamento.
**Dependências:** Prompts 1–5.
**Lê:** `PILAR-3-SEGURANCA.md` (uploads, MIME), `PILAR-4-UI-UX.md` (estados, copy), `CLAUDE.md` §7.5.

### RED — testes a escrever primeiro

`tests/Feature/Livewire/SaidaTest.php`:

- `it('accepts saida with no detalhes and no foto')`
- `it('accepts saida with detalhes only')`
- `it('accepts saida with foto only')`
- `it('rejects detalhes longer than 2000 chars')`
- `it('rejects foto larger than 5MB')`
- `it('rejects file with falsified mime extension')` — ficheiro PHP renomeado para .jpg
- `it('accepts jpeg, png, webp')`
- `it('resizes foto to max 1920px width')`
- `it('stores foto via default filesystem driver')`
- `it('returns signed url with 30min expiration')`

### GREEN — implementação

- Estender `PainelPonto`: quando o próximo tipo for `saida`, mostrar form expandido com:
  - `textarea` `detalhes` (max 2000 chars, contador de chars visível).
  - `input file` `foto` com preview.
- Form Request `RegistarSaidaRequest`:
  ```php
  'detalhes' => ['nullable', 'string', 'max:2000'],
  'foto'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
  ```
- Validar MIME **real** com `$file->getMimeType()` (não apenas extensão).
- Redimensionar com Intervention Image: largura máxima 1920px, mantendo proporção.
- `Storage::put($path, $content)` — sem hardcode de disk.
- Helper `Marcacao::fotoUrlAssinada(): ?string` → `Storage::temporaryUrl($this->foto_path, now()->addMinutes(30))`.
- Copy: placeholder "O que fizeste hoje? (opcional)" no textarea. Botão final "Marcar saída".

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Upload de `.php` renomeado para `.jpg` é rejeitado.
- Foto de 3000×2000 é guardada com 1920×1280.
- URL gerada para servir foto expira em 30 min.

---

## Prompt 7 — Edição de marcações pelo admin + audit log

**Objetivo:** admin pode criar, editar e eliminar marcações, mantendo sequência válida e rasto de alterações.
**Dependências:** Prompts 1–6.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (Events/Listeners, audit), `PILAR-3-SEGURANCA.md` (autorização), `CLAUDE.md` §7.3.

### RED — testes a escrever primeiro

`tests/Feature/Admin/MarcacoesTest.php`:

- `it('lists all marcacoes with filters')` — funcionário, período, tipo
- `it('admin can create marcacao for any funcionario')`
- `it('admin can edit data_hora of existing marcacao')`
- `it('admin can delete marcacao')`
- `it('rejects edit that breaks the day sequence')`
- `it('fills editado_por and editado_em on update')`
- `it('logs activity with before/after diff')`
- `it('blocks funcionario role with 403')`

### GREEN — implementação

- Filament Resource `MarcacaoResource`.
- Form: `user_id` (select), `tipo` (select com TipoMarcacao), `data_hora` (datetime picker).
- Validation chama `MarcacaoService::validarSequenciaDoDia()` — bloqueia gravação se a sequência final do dia ficar inválida.
- Em qualquer save/update, preencher `editado_por = auth()->id()` e `editado_em = now()`.
- Activitylog regista `attributes` e `old` (PILAR-2).
- Filtros: por funcionário, por período (data inicial/final), por tipo, por "editado pelo admin".
- Indicador visual nas linhas editadas pelo admin (ícone + tooltip).
- Policy `MarcacaoPolicy` — apenas admin (PILAR-3).

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Edição que resulte em sequência inválida bloqueia com mensagem clara.
- Cada edição cria entrada em `activity_log` com diff.
- Funcionário não vê o resource.

---

## Prompt 8 — Relatório Individual

**Objetivo:** funcionário vê o seu relatório, admin vê de qualquer um. Filtro por período. Cálculo de horas, detalhes, foto, inconsistências.
**Dependências:** Prompts 1–7.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (Service Layer, performance/cache), `PILAR-4-UI-UX.md`, `CLAUDE.md` §9.1.

### RED — testes a escrever primeiro

`tests/Feature/RelatorioIndividualTest.php`:

- `it('funcionario sees only own data')`
- `it('admin sees data of any funcionario')`
- `it('defaults to current month when no filter provided')`
- `it('lists each day with all marcacoes and computed hours')`
- `it('shows day as "em curso" when incomplete')`
- `it('flags day with missing intermediate marcacao')`
- `it('marks days edited by admin')`
- `it('aggregates totais: dias trabalhados, total horas, média diária')`

`tests/Unit/Services/RelatorioIndividualServiceTest.php`:

- `it('groups marcacoes by data_civil')`
- `it('eager loads to avoid N+1')`
- `it('uses Europe/Lisbon to group days')`

### GREEN — implementação

- Rota `GET /relatorio` (funcionário) e Filament page `RelatorioIndividual` (admin).
- `RelatorioIndividualService::gerar(User $user, CarbonImmutable $de, CarbonImmutable $ate): RelatorioIndividual` — DTO com colecção de `DiaRelatorio`.
- Componente Livewire com filtros (período).
- Performance: eager load `Marcacao::with('user', 'editor')`, agrupar em memória depois de uma query única por período.
- View: cada dia em card com hora de cada marcação, total, detalhes, thumbnail (link para foto via URL assinada).
- 4 estados (PILAR-4).
- Tabela responsiva — scroll horizontal ou layout alternativo em mobile.

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Sem N+1 (verificável com Laravel Debugbar em dev ou `assertQueryCountLessThan` em teste).
- Funcionário não consegue ver outro funcionário (tentativa via URL manipulada → 403).
- Mês com 22 dias úteis carrega em < 500ms.

---

## Prompt 9 — Relatório Geral (admin)

**Objetivo:** painel agregado para o diretor com totais por funcionário, drill-down para individual.
**Dependências:** Prompts 1–8.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (cache, performance), `CLAUDE.md` §9.2.

### RED — testes a escrever primeiro

`tests/Feature/RelatorioGeralTest.php`:

- `it('blocks non-admin with 403')`
- `it('defaults to current month and all active funcionarios')`
- `it('aggregates dias trabalhados, total horas, média diária per funcionario')`
- `it('counts inconsistencias per funcionario')`
- `it('filters by selected funcionarios')`
- `it('drill-down link points to relatorio individual with same period')`
- `it('does not include soft-deleted funcionarios by default')`

### GREEN — implementação

- Filament custom page `RelatorioGeral` em `/admin/relatorios/geral`.
- `RelatorioGeralService::gerar(CarbonImmutable $de, CarbonImmutable $ate, ?array $userIds): Collection<LinhaRelatorio>`.
- Query única com agregação SQL (`SUM`, `COUNT`) — sem loops por funcionário.
- Cache::remember por chave `relatorio:geral:{hash}` com TTL 5 min; invalidar via Observer no `Marcacao` model.
- Tabela ordenável por qualquer coluna.
- Cada linha tem link "Ver detalhe" → relatório individual desse funcionário no mesmo período.

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- Página carrega em < 800ms para 20 funcionários × 1 mês.
- Cache invalidado quando uma marcação é alterada.
- Estado vazio claro quando o período não tem dados.

---

## Prompt 10 — Exportação PDF

**Objetivo:** exportar relatório individual e geral em PDF; job assíncrono para relatórios pesados; download por URL assinada.
**Dependências:** Prompts 1–9.
**Lê:** `PILAR-2-BOAS-PRATICAS.md` (jobs > 2s, eager loading), `PILAR-3-SEGURANCA.md` (URLs assinadas, retenção), `CLAUDE.md` §9.3.

### RED — testes a escrever primeiro

`tests/Feature/RelatorioPdfTest.php`:

- `it('generates pdf inline for individual report of 1 month')`
- `it('dispatches job when period > 30 days')`
- `it('dispatches job when geral has > 1 funcionario')`
- `it('stores pdf and returns signed url valid for 30 min')`
- `it('rejects download with expired url')`
- `it('blocks non-owner from downloading individual pdf')`
- `it('pdf contains nome empresa, periodo and data de emissao no cabeçalho')`

`tests/Unit/Jobs/GerarRelatorioPdfJobTest.php`:

- `it('has 3 retries and timeout 120s')`
- `it('logs failure to critical channel')`

### GREEN — implementação

- Views Blade dedicadas: `pdf/relatorio-individual.blade.php` e `pdf/relatorio-geral.blade.php` (CSS inline para A4).
- `RelatorioPdfService::gerar*(...)` usa `barryvdh/laravel-dompdf`.
- Job `GerarRelatorioPdfJob implements ShouldQueue` com `tries=3`, `timeout=120`, `backoff=[30,120,300]` (PILAR-3).
- Decisão sync vs async:
  - Individual ≤ 30 dias → sync, devolve link directo.
  - Individual > 30 dias **ou** geral com > 1 funcionário → job.
- Storage do PDF em `pdfs/relatorios/` com nome `YYYYMMDD-HHMM-{user_id}-{hash}.pdf`.
- Download via rota assinada `/pdf/{token}` (30 min de validade, com autorização verificada — o owner é quem pediu).
- Notificação Livewire (toast + polling) ou Echo opcional para quando o PDF assíncrono fica pronto.
- Botão "Exportar PDF" em ambos os relatórios.
- Scheduler: limpar PDFs com mais de 7 dias (PILAR-3 retenção):
  ```php
  Schedule::call(fn() => Storage::deleteDirectory(... older than 7 days))->daily();
  ```

### VALIDAR

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
./vendor/bin/pest --parallel --coverage --min=80
```

### Critérios de aceitação

- PDF de 1 mês de 1 funcionário gerado em < 5s (sync).
- PDF de 3 meses dispatched para queue (assert via `Queue::fake()`).
- URL expirada devolve 403.
- PDF correctamente renderizado em A4, com cabeçalho exigido.

---

## Definition of Done — global

No fim dos 10 prompts, verificar:

```
[ ] Todos os itens de ENGINEERING-STANDARDS.md → Definition of Done verdes
[ ] Todos os itens de CLAUDE.md §11 verdes
[ ] CI verde em GitHub Actions
[ ] APP_DEBUG=false em produção
[ ] DNS configurado: SPF + DKIM + DMARC
[ ] Backup 3-2-1 a correr em produção (PILAR-3)
[ ] Teste de restauro de backup documentado
[ ] Sentry a receber eventos sem PII
```

---

*Versão: 1.0 — Plano de implementação inicial.*
