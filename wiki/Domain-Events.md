# Domain Events

## What is a Domain Event?

A domain event represents something meaningful that happened in the domain. Events are immutable facts —
once something happened, it cannot be changed. They are the primary mechanism for cross-boundary
communication in Domain-Driven Design.

## The `IsDomainEvent` Trait

The `IsDomainEvent` trait provides a default implementation of the `Event` contract from
[php-contracts](https://github.com/ComplexHeart/php-contracts). It auto-generates all required
metadata so you can focus on defining what happened.

### Auto-Generated Metadata

| Method | What it does | How |
|---|---|---|
| `eventId()` | Unique event identifier | UUID v4 via `ramsey/uuid` |
| `eventName()` | Semantic event name | Derived from class name in dotted format |
| `payload()` | Event data as array | Built from public properties via reflection |
| `occurredOn()` | When the event happened | ISO-8601 timestamp |

### Event Name Convention

The event name is automatically derived from the class name by converting PascalCase to dotted lowercase:

| Class Name | Event Name |
|---|---|
| `OrderPlaced` | `order.placed` |
| `UserEmailUpdated` | `user.email.updated` |
| `CharacterCreated` | `character.created` |
| `PaymentProcessed` | `payment.processed` |

## Defining Events

Events should be `final` classes with `public readonly` constructor-promoted properties:

```php
use ComplexHeart\Domain\Contracts\Events\Event;
use ComplexHeart\Domain\Events\IsDomainEvent;

final class OrderPlaced implements Event
{
    use IsDomainEvent;

    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly float $total,
    ) {
    }
}
```

## Creating Events

Use the `new()` or `make()` static factory to create event instances:

```php
// Named parameters
$event = OrderPlaced::new(
    orderId: 'order-123',
    customerId: 'customer-456',
    total: 99.95,
);

// Positional parameters
$event = OrderPlaced::new('order-123', 'customer-456', 99.95);

// make() alias
$event = OrderPlaced::make(orderId: 'order-123', customerId: 'customer-456', total: 99.95);
```

## Accessing Event Data

```php
// Auto-generated metadata
$event->eventId();    // "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
$event->eventName();  // "order.placed"
$event->occurredOn(); // "2026-03-15T10:30:00+00:00"

// Payload as associative array
$event->payload();    // ['orderId' => 'order-123', 'customerId' => 'customer-456', 'total' => 99.95]

// Direct property access
$event->orderId;      // "order-123"
$event->customerId;   // "customer-456"
$event->total;        // 99.95
```

## Integration with Aggregates

Domain events are collected by aggregates using the `HasDomainEvents` trait from
[php-domain-model](https://github.com/ComplexHeart/php-domain-model) and published
through an `EventBus` implementation:

```php
use ComplexHeart\Domain\Contracts\Model\Aggregate;
use ComplexHeart\Domain\Model\IsAggregate;

class Order extends Model implements Aggregate
{
    use IsAggregate;

    public static function place(string $id, string $customerId, float $total): static
    {
        $order = static::new(id: $id, customerId: $customerId, total: $total);
        $order->registerDomainEvent(
            OrderPlaced::new(orderId: $id, customerId: $customerId, total: $total)
        );
        return $order;
    }
}
```

## Publishing Events

Events are published through an `EventBus` in the application layer (use cases):

```php
final readonly class PlaceOrder
{
    public function __construct(
        private OrderRepository $orders,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(string $id, string $customerId, float $total): Order
    {
        $order = Order::place($id, $customerId, $total);
        $this->orders->store($order);
        $order->publishDomainEvents($this->eventBus);
        return $order;
    }
}
```

The `EventBus` is a domain contract — the implementation is provided by the framework
bridge package (e.g., [on-laravel](https://github.com/ComplexHeart/on-laravel) provides
`IlluminateEventBus`).

## Best Practices

- **Events carry primitives only** — use strings, integers, floats, and arrays. Never put
  entities or value objects in events. This ensures events are serializable and framework-agnostic.
- **Events are immutable** — use `public readonly` properties. Once created, an event never changes.
- **Events are named in past tense** — `OrderPlaced`, `UserRegistered`, `PaymentProcessed`.
  They describe something that already happened.
- **One event per meaningful thing** — don't create a single `OrderChanged` event. Create
  `OrderPlaced`, `OrderShipped`, `OrderCancelled` — each with its own specific payload.
