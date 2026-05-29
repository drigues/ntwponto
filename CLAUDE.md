# CLAUDE.md
# Ponto Eletrónico PME — Especificação do Projeto
# Lido automaticamente pelo Claude Code em cada sessão.

---

## 1. Visão Geral

Aplicação web de ponto eletrónico para pequenas e médias empresas.
Cada funcionário marca 4 momentos por dia (entrada, pausa de almoço,
retorno, saída). Na saída regista detalhes do trabalho realizado e,
opcionalmente, uma foto. O diretor/dono consulta relatórios individuais
e globais e exporta para PDF.

Princípio condutor: **simples, direto, completo na essência.**

---

## 2. Atores

| Papel | Permissões |
|---|---|
| **Funcionário** | Marca ponto, consulta o próprio histórico e relatório individual |
| **Admin (Diretor/Dono)** | Tudo do funcionário + CRUD de funcionários, edição de marcações, relatório geral, exportação PDF |

**Não existe registo público.** Todas as contas são criadas pelo admin no painel.

---

## 3. Engenharia — Pilares Obrigatórios

Ler antes de implementar qualquer feature:

- `PILAR-1-TDD.md` — TDD, pirâmide, cobertura ≥ 80%
- `PILAR-2-BOAS-PRATICAS.md` — arquitetura em camadas, SOLID, DTOs, performance
- `PILAR-3-SEGURANCA.md` — input, criptografia, autenticação, RGPD, headers
- `PILAR-4-UI-UX.md` — 4 estados, copy, acessibilidade, responsividade

A Definition of Done em `ENGINEERING-STANDARDS.md` aplica-se integralmente,
acrescida dos itens específicos da secção 11 deste documento.

---

## 4. Stack

```
Laravel 11+      framework
PHP 8.3
PostgreSQL       base de dados
Filament 3       painel admin
Tailwind CSS     estilo
Livewire 3       fluxo de marcação reativo
Alpine.js        captura de GPS no browser
barryvdh/laravel-dompdf      exportação PDF
spatie/laravel-activitylog   audit trail
spatie/laravel-backup        backups (3-2-1, ver PILAR-3)
sentry/sentry-laravel        monitorização de erros
intervention/image           redimensionamento de fotos
```

Fuso horário da aplicação: **Europe/Lisbon** (display).
Armazenamento de timestamps: **UTC**.

---

## 5. Autenticação

- **Sem registo público.** Rota e UI de `register` desactivadas.
- Login: email + password.
- Política de password: ver `PILAR-3-SEGURANCA.md` (mín. 8, letras, maiús/minús, números, não-comprometida).
- Brute force, lockout progressivo, regeneração de sessão: ver `PILAR-3`.
- Honeypot + `throttle:5,1` na rota de login.

### Criação de contas
- Apenas o admin cria utilizadores no painel (Filament).
- Ao criar, o admin define uma password inicial (gerada ou escolhida).
- O utilizador recebe as credenciais por canal seguro (responsabilidade do admin).

### Primeiro login
- Campo `users.must_change_password` (boolean, default `true`).
- Enquanto `true`, qualquer acesso após autenticação é redirecionado para o ecrã de definição de nova password — sem possibilidade de aceder ao resto da app antes de concluir.
- Ao definir nova password, o campo passa a `false`.

### Recuperação
- Rota `/password/forgot` activa (link por email).
- Admin pode também redefinir password de qualquer funcionário a partir do painel — ao fazê-lo, `must_change_password` volta a `true`.

---

## 6. Modelo de Dados

### `users`
```
id                       bigint pk
name                     string(100)
email                    string(255) unique
password                 string (hashed)
role                     enum: admin | funcionario     default: funcionario
cargo                    string(100) nullable
must_change_password     boolean default true
email_verified_at        timestamp nullable
created_at, updated_at, deleted_at (soft deletes)
```

Campos pessoais sensíveis adicionais (telefone, morada, NIF) devem usar cast `encrypted` (PILAR-3).

### `marcacoes`
```
id                  bigint pk
user_id             bigint fk users(id)  on delete restrict
tipo                enum: entrada | inicio_pausa | fim_pausa | saida
data_hora           timestamp (UTC)
latitude            decimal(10,7) nullable
longitude           decimal(10,7) nullable
gps_indisponivel    boolean default false
detalhes            text nullable           -- apenas tipo=saida
foto_path           string nullable         -- apenas tipo=saida
editado_por         bigint fk users(id) nullable
editado_em          timestamp nullable
created_at, updated_at
```

Índices: `(user_id, data_hora)`, `(data_hora)`.

### `spam_logs`
Conforme `PILAR-3-SEGURANCA.md` — usado pelo honeypot e rate-limit do login.

---

## 7. Regras de Negócio

### 7.1 Sequência válida de marcações no dia

```
entrada → inicio_pausa → fim_pausa → saida
```

- Cada tipo só pode existir **uma vez por dia** por utilizador (constraint unique composto: `user_id + tipo + data_civil`).
- A sequência é estrita — validar server-side antes de gravar (state machine).
- O "dia" delimita-se pela data civil em hora de Lisboa.
- Após `saida` registada, o dia fica fechado para o funcionário. Apenas o admin pode reabrir/editar.

### 7.2 Cálculo de horas trabalhadas no dia

```
horas = (saida − entrada) − (fim_pausa − inicio_pausa)
```

- Dia incompleto → mostrar "em curso" ou "marcação em falta".
- Marcações intermédias em falta → não inferir valores; sinalizar inconsistência ao admin.

### 7.3 Edição pelo admin

- Pode criar, editar e eliminar marcações.
- Cada alteração regista `editado_por` + `editado_em` e entra no `activitylog`.
- Marcações alteradas pelo admin aparecem assinaladas nos relatórios.

### 7.4 GPS

- Captura via Geolocation API do browser no momento da marcação.
- Requer **HTTPS** em produção.
- Se o utilizador negar permissão ou o browser não suportar: registar a marcação com `gps_indisponivel = true` — **nunca bloquear** a marcação.
- Reverse geocoding (morada legível) é opcional no relatório; as coordenadas são sempre o registo canónico.

### 7.5 Detalhes e foto (apenas em `saida`)

- Ambos opcionais.
- Detalhes: texto livre, máximo 2000 caracteres.
- Foto: validar **MIME real** (`image/jpeg`, `image/png`, `image/webp`), máximo **5 MB**.
- Após upload, redimensionar para largura máxima de **1920px** (Intervention Image).
- Storage via `Storage::put()` — driver definido em `config/filesystems.default`, nunca hardcoded.
- Servida em relatórios via URL assinada com expiração de 30 min.

---

## 8. Fluxos de UX

### 8.1 Marcação (funcionário) — botão contextual

Um único botão visível, em função do estado do dia:

| Estado actual | Botão visível | Próximo estado |
|---|---|---|
| Sem marcações hoje | "Marcar entrada" | aguarda `inicio_pausa` |
| Após `entrada` | "Sair para almoço" | aguarda `fim_pausa` |
| Após `inicio_pausa` | "Voltar do almoço" | aguarda `saida` |
| Após `fim_pausa` | "Marcar saída" → form (detalhes + foto) | dia fechado |
| Após `saida` | (sem botão, mostra "Dia concluído") | — |

O ecrã mostra também:
- Hora actual (server-side, refresh leve).
- Lista das marcações do dia já feitas, com hora e indicação se GPS foi capturado.
- Total de horas em curso (quando aplicável).

Os 4 estados de PILAR-4 (loading/empty/error/filled) aplicam-se a todos os componentes com dados.

### 8.2 Painel admin (Filament)

Recursos principais:

- **Funcionários** — lista, criar, editar, desactivar (soft delete), redefinir password, ver histórico de marcações.
- **Marcações** — lista global filtrável por funcionário e período; editar/criar manual.
- **Relatórios** — individual e geral, com exportação PDF.

Acesso ao painel Filament restringido a `users.role = admin`.

### 8.3 Primeiro login

Enquanto `must_change_password = true`, todas as rotas autenticadas redirecionam para `/password/change`. Sem possibilidade de aceder ao resto da app antes de definir nova password.

---

## 9. Relatórios

### 9.1 Relatório Individual

Disponível para o próprio funcionário (só os seus dados) e para o admin (qualquer funcionário).

Filtro: intervalo de datas (default = mês corrente).

Para cada dia no intervalo:
- Data e dia da semana.
- Hora de cada marcação (entrada, início pausa, fim pausa, saída).
- Total de horas trabalhadas.
- Detalhes da saída + thumbnail da foto (se existir).
- Indicação de inconsistências.
- Indicação se foi editado pelo admin.

Totais do período: dias trabalhados, total de horas, média diária.

### 9.2 Relatório Geral (admin)

Filtro: intervalo de datas + selecção de funcionários (default = todos activos).

Tabela com uma linha por funcionário:
- Nome, cargo.
- Dias trabalhados no período.
- Total de horas no período.
- Média diária.
- Contagem de inconsistências.

Clicar numa linha → drill-down para o Relatório Individual desse funcionário no mesmo período.

### 9.3 Exportação PDF

- Pacote: `barryvdh/laravel-dompdf`.
- Layout: A4, cabeçalho com nome da empresa, período e data de emissão.
- Disponível em ambos os relatórios.
- Gerado em **job assíncrono** se cobrir > 30 dias ou > 1 funcionário (ver PILAR-2: jobs para > 2s).
- Download via URL assinada com expiração de 30 min.

---

## 10. Decisões Travadas

```
Marcação           4 momentos/dia (entrada, inicio_pausa, fim_pausa, saida)
Sequência          estrita, validada server-side
GPS                captura no browser, fallback gps_indisponivel=true
Detalhes/foto      apenas na saída, ambos opcionais; foto máx. 5 MB
Edição             admin pode criar/editar/eliminar, com audit log
Fuso horário       Europe/Lisbon (display) — UTC (storage)
Registo            sem registo público; admin cria contas
1.º login          must_change_password força mudança antes do acesso
Recuperação        password reset por email + redefinição pelo admin
Relatórios         visualização web + exportação PDF
Stack              Laravel 11 + Filament 3 + PostgreSQL + Tailwind
```

---

## 11. Definition of Done — específico do projeto

Aplica-se a DoD de `ENGINEERING-STANDARDS.md`, com os acréscimos:

```
[ ] Sequência de marcações validada server-side (state machine)
[ ] Constraint unique (user_id, tipo, data_civil) na tabela marcacoes
[ ] GPS com fallback testado (browser sem suporte / utilizador negou)
[ ] Marcação não bloqueia quando GPS indisponível
[ ] Upload de foto valida MIME real e redimensiona para 1920px
[ ] must_change_password testado em primeiro login e após reset pelo admin
[ ] Rota /register desactivada — teste confirma 404
[ ] Painel Filament inacessível a role=funcionario — teste confirma 403
[ ] Botão contextual mostra apenas a próxima acção válida
[ ] Cálculo de horas correto com dia incompleto (sem inferência)
[ ] PDF de relatório gerado em < 5s para 1 mês de 1 funcionário
[ ] PDFs > 30 dias ou multi-funcionário gerados em job assíncrono
[ ] Fuso Europe/Lisbon consistente em UI e queries
[ ] Audit log activo em marcações e users
```

---

*Versão: 1.0 — Especificação inicial.*
