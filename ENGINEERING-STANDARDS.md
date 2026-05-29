# ENGINEERING-STANDARDS.md
# Modelo de Desenvolvimento de Software
# Ponto de entrada — referencia os 4 pilares.

---

## Pilares

| Ficheiro | Conteúdo |
|---|---|
| `PILAR-1-TDD.md` | Test-Driven Development — ciclo, pirâmide, convenções, cobertura |
| `PILAR-2-BOAS-PRATICAS.md` | Arquitectura, SOLID, DTOs, performance, CI/CD |
| `PILAR-3-SEGURANCA.md` | Input, criptografia, autenticação, RGPD, backups, infraestrutura |
| `PILAR-4-UI-UX.md` | Estados de interface, copy, acessibilidade, responsividade, transições |

**Ler os pilares relevantes antes de iniciar qualquer feature.**

---

## Quando ler cada pilar

```
Qualquer feature          → PILAR-1 + PILAR-2 + PILAR-3
Formulários / uploads     → + PILAR-3 (secção formulários públicos)
Componentes de interface  → + PILAR-4
Features com dados pessoais → + PILAR-3 (secção RGPD)
Novo projecto / deploy    → + PILAR-3 (secção infraestrutura)
```

---

## Como usar num projecto novo

```bash
# Copiar os 5 ficheiros para a raiz do projecto
cp ENGINEERING-STANDARDS.md  ~/projecto/CLAUDE.md
cp PILAR-1-TDD.md             ~/projecto/PILAR-1-TDD.md
cp PILAR-2-BOAS-PRATICAS.md   ~/projecto/PILAR-2-BOAS-PRATICAS.md
cp PILAR-3-SEGURANCA.md       ~/projecto/PILAR-3-SEGURANCA.md
cp PILAR-4-UI-UX.md           ~/projecto/PILAR-4-UI-UX.md
```

O `CLAUDE.md` é lido automaticamente pelo Claude Code ao iniciar cada sessão.
Os pilares são carregados conforme a necessidade do prompt activo.

---

## Instrução de sessão para o Claude Code

```
Lê os pilares relevantes antes de implementar:
- PILAR-1-TDD.md
- PILAR-2-BOAS-PRATICAS.md
- PILAR-3-SEGURANCA.md
- PILAR-4-UI-UX.md (se a feature tiver interface)

Segue os 4 pilares em toda a implementação.
```

---

## Definition of Done

Nenhuma feature está concluída sem:

```
TDD
 [ ] Testes escritos ANTES da implementação (RED confirmado)
 [ ] Todos os testes passam (GREEN confirmado)
 [ ] pest --parallel --coverage --min=80 → verde

Qualidade
 [ ] ./vendor/bin/pint --test → sem erros
 [ ] ./vendor/bin/phpstan analyse --level=6 → sem erros
 [ ] Sem dd(), var_dump(), dump() no código

Segurança
 [ ] Nenhum $request->all() — sempre $request->validated()
 [ ] Honeypot em formulários públicos novos
 [ ] Rate limiting em rotas públicas novas
 [ ] Campos sensíveis com cast 'encrypted'
 [ ] Sem credenciais no código
 [ ] authorize() em acções que requerem permissão
 [ ] APP_DEBUG=false em produção
 [ ] robots.txt bloqueia rotas de admin e debug
 [ ] Security headers activos
 [ ] DNS: SPF + DKIM + DMARC configurados

Base de Dados
 [ ] Migration com up() E down()
 [ ] Seeders com updateOrCreate
 [ ] Sem SQL raw específico de motor
 [ ] Sem Storage::disk() hardcoded

Performance
 [ ] Eager loading — sem N+1
 [ ] Cache::remember() em queries pesadas
 [ ] Jobs para operações > 2 segundos
 [ ] loading="lazy" + width/height em imagens

Interface (quando aplicável)
 [ ] 4 estados em cada componente com dados (loading/empty/error/filled)
 [ ] Estado vazio com ícone + título + descrição + CTA
 [ ] Erros de formulário inline por campo
 [ ] Toasts de erro persistem — success auto-dismiss 4s
 [ ] Confirmação antes de acções destrutivas
 [ ] Botões seguem "verbo + objecto"
 [ ] Mensagens de erro têm problema + solução
 [ ] Sentence case em todos os textos de interface

Acessibilidade (quando aplicável)
 [ ] Navegação completa por teclado funcional
 [ ] Contraste mínimo 4.5:1 em texto normal
 [ ] Focus visible em todos os elementos interactivos
 [ ] Imagens com alt adequado

Responsividade (quando aplicável)
 [ ] Testado em 320px — nada cortado
 [ ] Touch targets >= 44×44px
 [ ] Inputs com font-size >= 16px

RGPD
 [ ] IPs como ip_hash (SHA-256) — nunca raw
 [ ] Sem dados pessoais em logs
 [ ] Scheduler de limpeza activo
```

---

*Versão: 1.1 — Modelo genérico, independente de projecto.*
