# PILAR-4-UI-UX.md
# Interface, Experiência de Utilizador, Copy e Acessibilidade
# Ler antes de implementar qualquer componente de interface.

---

## Estados de Interface

Todo o componente que carrega dados tem obrigatoriamente 4 estados.
Nunca aceitar um componente que só tem o estado FILLED.

```
LOADING  → dados ainda não chegaram
EMPTY    → dados chegaram mas não há nada
ERROR    → algo correu mal
FILLED   → estado normal com dados
```

### Loading

**Skeleton screens** — quando o layout é conhecido e estável:
```html
<!-- Tailwind -->
<div class="animate-pulse">
    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
</div>
```
- Altura e largura aproximam o conteúdo real
- Duração máxima: 10s — depois mostrar estado de erro

**Spinner** — em acções pontuais (submit, delete, upload):
- Centrado no elemento que processa, não na página inteira
- Sempre com texto ("A guardar…", "A enviar…")
- Elemento desactivado durante o loading (`disabled`)

**Progressive loading:**
- Conteúdo acima do fold carrega primeiro
- Imagens abaixo do fold com `loading="lazy"`
- Dados secundários podem carregar após o principal

### Estado Vazio

Estrutura obrigatória:
```
[ícone ou ilustração — 48–64px]
[Título — o quê está vazio]
[Descrição — porquê e o que pode fazer]
[CTA — acção principal]
```

```html
<!-- Exemplo -->
<div class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400">...</svg>
    <h3>Ainda não tens pedidos</h3>
    <p>Quando criares um pedido, aparece aqui.</p>
    <a href="/novo">Criar pedido</a>
</div>
```

Nunca:
- Tabela vazia sem mensagem
- "Não há dados." ou "Lista vazia." sem contexto
- Mesmo estado vazio para lista sem dados vs. sem resultados de pesquisa

### Erros e Feedback

**Erros de formulário — inline por campo:**
```html
<div>
    <label for="email">Email</label>
    <input id="email" type="email" aria-describedby="email-error"
           class="border-red-500">
    <p id="email-error" role="alert" class="text-red-600 text-sm mt-1">
        O email já está registado. Faz login ou recupera a password.
    </p>
</div>
```
- Erro aparece após blur ou submit — nunca durante a escrita
- Desaparece imediatamente quando o campo é corrigido
- Campo com erro tem indicação visual além da cor (ícone, border)

**Toasts — hierarquia:**
```
success  → verde    — auto-dismiss após 4s
info     → azul     — auto-dismiss após 4s
warning  → âmbar    — auto-dismiss após 6s
error    → vermelho  — persiste até fechar manualmente
```
Posição: canto superior direito (desktop) · topo centrado (mobile)

**Acções destrutivas:**
- Confirmação obrigatória antes de executar
- Modal mostra o que será afectado pelo nome
- Botão de confirmação em vermelho, cancelar em destaque menor

---

## Copy — Regras de Escrita

### Princípios
- **Directo** — diz o que é, não o que parece
- **Útil** — cada palavra serve o utilizador, não o produto
- **Humano** — escreve como falavas, não como um manual
- **Honesto** — não promete o que não cumpre

### Títulos e Headings
```
✅ "Os teus pedidos activos"
❌ "Gestão De Pedidos"
❌ "PEDIDOS"
```
- Sentence case sempre — nunca Title Case nem ALL CAPS
- Sem ponto final
- Máximo 8 palavras
- Descrevem o conteúdo, não o produto

### Botões e CTAs — verbo + objecto
```
✅ "Guardar perfil"        ← o quê acontece
✅ "Enviar pedido"         ← o quê acontece
✅ "Criar conta gratuita"  ← o quê + benefício

❌ "Submeter"              ← vago
❌ "OK"                    ← sem contexto
❌ "Confirmar"             ← o quê?
❌ "Clica aqui"            ← nunca
```

Botões destrutivos especificam o que é destruído:
```
✅ "Eliminar conta"    ❌ "Eliminar"
✅ "Cancelar reserva"  ❌ "Cancelar"
```

### Mensagens de Erro — problema + solução
```
✅ "O email já está registado. Faz login ou recupera a password."
✅ "Ficheiro demasiado grande. Máximo: 5 MB."

❌ "Campo inválido."
❌ "Erro de validação."
❌ "422 Unprocessable Entity."
```
Nunca culpar o utilizador. Nunca pontos de exclamação em erros.

### Labels de Formulário
- Sentence case, sem dois pontos no final
- Placeholder só para exemplo/formato — nunca instrução obrigatória
- Marcar campos OPCIONAIS com "(opcional)" — não os obrigatórios com "*"

### O que evitar sempre
```
Nunca:
  "Clica aqui" como texto de link
  "Por favor" antes de instruções
  "Simples", "fácil", "rápido" — mostra, não diz
  Frases passivas: "O erro foi encontrado" → "Encontrámos um erro"
  Jargão técnico: "500 Internal Server Error" → "Algo correu mal. Tenta novamente."
  Eufemismos: "Experiência degradada" → "Está lento"
```

---

## Acessibilidade — WCAG 2.1 AA

### Contraste
- Texto normal: mínimo **4.5:1** contra o fundo
- Texto grande (18px+ bold ou 24px+): mínimo **3:1**
- Componentes UI (borders, ícones activos): mínimo **3:1**
- Nunca transmitir informação só por cor — sempre com texto ou ícone adicional

### Semântica HTML
```html
<!-- Um único <h1> por página -->
<!-- Hierarquia lógica: h1 → h2 → h3 — nunca saltar níveis -->
<!-- Navegação com aria-label descritivo -->
<nav aria-label="Navegação principal">
<nav aria-label="Navegação de rodapé">

<!-- Regiões obrigatórias -->
<header> <main> <footer> <aside>

<!-- Cada input com label associado -->
<label for="email">Email</label>
<input id="email" type="email">

<!-- Tabelas com caption e th com scope -->
<table>
    <caption>Pedidos do mês</caption>
    <th scope="col">Data</th>
    <th scope="row">Janeiro</th>
```

### Teclado e Foco
```css
/* Focus visible sempre — nunca outline: none sem alternativa */
*:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
```
- Todos os elementos interactivos acessíveis via Tab
- Ordem de Tab segue o fluxo visual
- Esc fecha modais e dropdowns
- Modais: foco entra ao abrir, retorna ao trigger ao fechar

### ARIA
```html
<!-- Botões só com ícone -->
<button aria-label="Fechar menu">
    <svg>...</svg>
</button>

<!-- Modais -->
<div role="dialog" aria-modal="true" aria-labelledby="modal-title">

<!-- Toggles e accordions -->
<button aria-expanded="false" aria-controls="panel-id">

<!-- Feedback dinâmico -->
<div aria-live="polite" aria-atomic="true">

<!-- Campos com instrução adicional -->
<input aria-describedby="campo-ajuda">
<p id="campo-ajuda">Formato: DD/MM/AAAA</p>
```
Nunca usar ARIA para reparar HTML semântico incorrecto.

### Imagens
```html
<img src="..." alt="Descrição da imagem" width="800" height="600">  <!-- informativa -->
<img src="..." alt="" width="40" height="40" aria-hidden="true">    <!-- decorativa -->
```

---

## Responsividade

### Breakpoints a cobrir
```
Mobile S:  320px  — mínimo absoluto
Mobile M:  375px  — standard
Mobile L:  430px  — large
Tablet:    768px
Desktop:   1280px
Wide:      1440px
```

### Regras Mobile — críticas
- Nenhum elemento cortado horizontalmente
- Texto mínimo **16px** no corpo (evita zoom automático no iOS)
- Touch targets mínimo **44×44px** (botões, links, toggles)
- Inputs com `font-size >= 16px` (evita zoom automático iOS)
- Tabelas com scroll horizontal ou layout alternativo
- Espaçamentos laterais mínimos de 16px
- Formulários em coluna única

### Padrões por componente
```
Navegação:
  Mobile  → hamburger menu
  Desktop → nav horizontal

Grids de cards:
  Mobile  → 1 coluna
  Tablet  → 2 colunas
  Desktop → 3–4 colunas

Modais:
  Mobile  → bottom sheet ou full-screen
  Desktop → centrado com max-width

Sidebar de filtros:
  Mobile  → drawer/overlay
  Desktop → sidebar lateral fixa
```

### Core Web Vitals — objectivos
```
LCP < 2.5s   — Largest Contentful Paint
CLS < 0.1    — Cumulative Layout Shift (width+height em todas as imagens)
INP < 200ms  — sem JavaScript bloqueante no critical path
```

---

## Polish e Transições

### Regra base
Animação só onde acrescenta contexto — nunca decorativa.

### O que animar
```
✅ Hover em botões, links, cards clicáveis
✅ Focus em inputs (border color, ring)
✅ Abertura/fecho de accordions, dropdowns, modais
✅ Toast — enter e exit suave
✅ Skeleton — pulse
```

### O que nunca animar
```
✗ Conteúdo que aparece ao carregar a página
✗ Tabelas ou listas de dados
✗ Nada com duração > 400ms
✗ Nada sem prefers-reduced-motion
```

### prefers-reduced-motion — obrigatório
```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```
