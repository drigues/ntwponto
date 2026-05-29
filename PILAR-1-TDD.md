# PILAR-1-TDD.md
# Test-Driven Development
# Ler antes de iniciar qualquer feature.

---

## Princípio

O teste não é uma verificação — é a especificação técnica do comportamento esperado.
Escrever o teste primeiro força clareza sobre o que o código deve fazer antes de o escrever.

**Nunca escrever código de produção sem um teste correspondente.**

---

## Ciclo obrigatório

```
RED      Escrever teste que falha
         (o código ainda não existe)
         ↓
GREEN    Implementar o mínimo
         para o teste passar
         ↓
REFACTOR Melhorar o código
         sem quebrar os testes
         ↓
RED      Próxima feature...
```

---

## Pirâmide de testes

```
        /\
       /E2E\          Poucos — fluxos críticos do utilizador
      /──────\
     / Feature\       Médios — HTTP, Livewire, integrações
    /──────────\
   / Unit Tests \     Maioria — Services, Models, Jobs
  /______________\
```

---

## Responsabilidade por camada

| Camada | Tipo | O que testar |
|---|---|---|
| Services | Unit | Regras de negócio, casos extremos |
| Models | Unit | Scopes, casts, relações, mutators |
| Controllers / API | Feature | Request → Response, status codes |
| Livewire / Components | Feature | Interacções, estado, validação |
| Jobs / Listeners | Unit | Execução, falhas, retries |
| Fluxos críticos | E2E | Caminho completo do utilizador |

---

## Convenções Pest

```php
// Nomenclatura — descreve comportamento, não implementação
it('rejects payment when balance is insufficient')
it('sends confirmation email after successful order')
it('calculates tax correctly for EU customers')

// Organização por contexto
describe('subscription renewal', function () {
    it('renews automatically before expiry date')
    it('sends reminder 7 days before expiry')
    it('downgrades plan when payment fails')
    it('preserves data after downgrade')
});

// Setup — sempre factories, nunca DB::insert() raw
beforeEach(function () {
    $this->user = User::factory()->verified()->create();
});

// Isolar dependências externas
it('sends welcome email on registration', function () {
    Mail::fake();
    $this->post('/register', [...]);
    Mail::assertSent(WelcomeMail::class);
});

it('dispatches job after upload', function () {
    Queue::fake();
    $this->post('/upload', [...]);
    Queue::assertPushed(ProcessImageJob::class);
});
```

---

## Cobertura mínima

| Componente | Mínimo |
|---|---|
| Services (negócio) | 90% |
| Models | 85% |
| Controllers / API | 80% |
| Jobs / Listeners | 80% |
| Global do projecto | 80% |

---

## Comando de validação — antes de qualquer commit

```bash
./vendor/bin/pest --parallel --coverage --min=80
```

---

## Instrução ao Claude Code para cada feature

```
PASSO 1 — RED
"Cria tests/Feature/[Nome]Test.php com os seguintes cenários:
[listar comportamentos esperados]
Corre e confirma que todos falham antes de implementar."

PASSO 2 — GREEN
"Implementa o código para fazer os testes passar.
Segue as regras dos PILAR-1, PILAR-2 e PILAR-3."

PASSO 3 — VALIDAR
"Corre: ./vendor/bin/pest --parallel --coverage --min=80
Só faz commit se tudo verde."
```
