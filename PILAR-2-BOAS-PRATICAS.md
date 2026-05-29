# PILAR-2-BOAS-PRATICAS.md
# Engenharia de Software — Boas Práticas
# Ler antes de iniciar qualquer feature.

---

## Arquitectura em camadas

```
HTTP (Controller · API · Livewire · Command)
    ↓  DTO validado
Service Layer — lógica de negócio pura
    ↓  Eloquent / Repository
Model — scopes, casts, relações, regras de dados
    ↓
Base de Dados
```

---

## SOLID

**S — Single Responsibility**
Uma classe tem uma razão para mudar.

```php
// ✅
class OrderService   { }  // cria e gere pedidos
class InvoiceService { }  // gera facturas
class StockService   { }  // gere inventário

// ❌ uma classe que faz tudo
class OrderController {
    public function store() {
        // valida + verifica stock + processa pagamento + envia email
    }
}
```

**O — Open/Closed**
Aberto para extensão, fechado para modificação.

```php
// ✅ novo gateway sem alterar código existente
interface PaymentGateway {
    public function charge(int $amount, string $currency): PaymentResult;
}
class StripeGateway  implements PaymentGateway { }
class MbWayGateway   implements PaymentGateway { } // novo — sem tocar no resto
```

**I — Interface Segregation**
Interfaces pequenas e específicas.

```php
// ✅
interface CanReceiveEmail { public function getEmail(): string; }
interface HasSubscription { public function getActivePlan(): Plan; }

// ❌ interface monolítica com tudo junto
interface UserInterface { ... }
```

**D — Dependency Inversion**
Depender de abstrações, não de implementações concretas.

```php
// ✅ injectável, testável, substituível
public function __construct(
    private readonly PaymentGateway      $payment,
    private readonly NotificationService $notify,
) {}

// ❌ acoplamento directo
public function handle() {
    $payment = new StripeGateway(); // impossível de mockar em testes
}
```

---

## Events para side effects

```php
// Service: cria e dispara — não trata dos efeitos
class OrderService {
    public function create(OrderData $data): Order {
        $order = Order::create([...]);
        event(new OrderCreated($order));
        return $order;
    }
}

// Listeners independentes — cada um com a sua responsabilidade
class SendOrderConfirmationEmail implements ShouldQueue { }
class ReduceStockOnOrder            implements ShouldQueue { }
class CreateInvoiceOnOrder          implements ShouldQueue { }
```

---

## DTOs entre camadas

```php
// Dados tipados — sem arrays associativos entre camadas
final class OrderData {
    public function __construct(
        public readonly int     $userId,
        public readonly array   $items,
        public readonly string  $paymentMethod,
        public readonly ?string $couponCode = null,
    ) {}

    public static function fromRequest(StoreOrderRequest $request): self {
        return new self(
            userId:        auth()->id(),
            items:         $request->validated('items'),
            paymentMethod: $request->validated('payment_method'),
            couponCode:    $request->validated('coupon_code'),
        );
    }
}
```

---

## Scopes nos Models

```php
class Order extends Model {
    public function scopePending(Builder $query): Builder {
        return $query->where('status', 'pending');
    }
    public function scopeCompletedInPeriod(Builder $query, Carbon $from, Carbon $to): Builder {
        return $query->where('status', 'completed')
                     ->whereBetween('completed_at', [$from, $to]);
    }
}

// Uso expressivo
Order::pending()->forUser($id)->with('items')->paginate(20);
```

---

## Patterns obrigatórios

```php
// Migrations — verificar existência + down() sempre implementado
public function up(): void {
    if (!Schema::hasTable('orders')) {
        Schema::create('orders', function (Blueprint $table) { ... });
    }
}
public function down(): void {
    Schema::dropIfExists('orders');
}

// Seeders — sempre idempotentes
Category::updateOrCreate(['slug' => 'electronics'], ['name' => 'Electronics']);

// Storage — nunca hardcodar o driver
Storage::put($path, $content);                        // ✅
Storage::disk(config('filesystems.default'))->put();  // ✅
Storage::disk('s3')->put();                           // ❌

// Configurações de negócio — nunca hardcodar valores
$plan = Plan::where('slug', 'pro')->firstOrFail();    // ✅
if ($price > 29.99) { ... }                           // ❌
```

---

## Performance

**Eager loading — eliminar N+1**
```php
// ❌
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->user->name; // 1 query por order
}

// ✅
$orders = Order::with(['user', 'items.product'])->paginate(20);
```

**Cache em queries pesadas**
```php
$stats = Cache::remember('reports:monthly', 3600, fn() =>
    Order::completed()->thisMonth()->sum('total')
);

// Invalidar via Observer
class OrderObserver {
    public function saved(Order $order): void {
        Cache::forget('reports:monthly');
    }
}
```

**Jobs para operações lentas (> 2 segundos)**
```php
dispatch(new GenerateInvoicePdf($order));
dispatch(new SendEmailSequence($user));
dispatch(new ProcessImageConversions($upload));
```

**Imagens**
```html
<img src="..." alt="..." width="800" height="600" loading="lazy">
```

---

## CI/CD

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', coverage: pcov }

      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.testing .env && php artisan key:generate
      - run: php artisan migrate --force

      - run: ./vendor/bin/pint --test
      - run: ./vendor/bin/phpstan analyse --level=6
      - run: ./vendor/bin/pest --parallel --coverage --min=80
      - run: composer audit
```

---

## Pacotes de qualidade

```bash
composer require --dev \
  pestphp/pest \
  pestphp/pest-plugin-laravel \
  phpstan/phpstan \
  nunomaduro/larastan \
  laravel/pint
```
