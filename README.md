# Domain Events

[![Tests](https://github.com/ComplexHeart/php-domain-events/actions/workflows/test.yml/badge.svg)](https://github.com/ComplexHeart/php-domain-events/actions/workflows/test.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=ComplexHeart_php-domain-events&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=ComplexHeart_php-domain-events)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=ComplexHeart_php-domain-events&metric=coverage)](https://sonarcloud.io/summary/new_code?id=ComplexHeart_php-domain-events)

## Building Rich Domain Events

Complex Heart Domain Events provides a trait-based implementation of the `Event` contract from
[php-contracts](https://github.com/ComplexHeart/php-contracts). It auto-generates event metadata so you can focus on
defining what happened in your domain without boilerplate.

The available trait:

- `IsDomainEvent` Implements the `Event` contract with auto-generated `eventId`, `eventName`, `payload`, and `occurredOn`.

## Key Features

- **Auto-Generated Event ID**: Each event instance gets a unique UUID v4 identifier
- **Convention-Based Event Name**: Derives a dotted event name from the class name (`OrderPlaced` → `order.placed`)
- **Automatic Payload**: Builds the payload from public properties via reflection
- **ISO-8601 Timestamps**: Records when the event occurred
- **Factory Method**: Uses `new()` / `make()` static factories consistent with the ComplexHeart ecosystem
- **Framework Agnostic**: Pure PHP, no framework dependencies — works with Laravel, Symfony, or standalone
- **PHPStan Level 8**: Complete static analysis support

## Installation

```bash
composer require complex-heart/domain-events
```

## Usage

Define your domain events as simple final classes with public readonly properties:

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

Create events using the static factory:

```php
$event = OrderPlaced::new(
    orderId: 'order-123',
    customerId: 'customer-456',
    total: 99.95,
);

$event->eventId();    // "a1b2c3d4-e5f6-..." (auto-generated UUID)
$event->eventName();  // "order.placed"
$event->payload();    // ['orderId' => 'order-123', 'customerId' => 'customer-456', 'total' => 99.95]
$event->occurredOn(); // "2026-03-15T10:30:00+00:00"

// Direct property access also works
$event->orderId;      // "order-123"
```

## Integration with Aggregates

Domain events are designed to work with aggregates that use the `HasDomainEvents` trait from
[php-domain-model](https://github.com/ComplexHeart/php-domain-model):

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

Then publish events through an `EventBus` implementation in your use case:

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

For more information and usage examples, please check the wiki.
