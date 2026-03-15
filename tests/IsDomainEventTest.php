<?php

declare(strict_types=1);

namespace ComplexHeart\Domain\Events\Test;

use ComplexHeart\Domain\Contracts\Events\Event;
use ComplexHeart\Domain\Events\IsDomainEvent;
use Ramsey\Uuid\Uuid;

final class SampleItemCreated implements Event
{
    use IsDomainEvent;

    public function __construct(
        public readonly string $itemId,
        public readonly string $name,
        public readonly int $quantity,
    ) {
    }
}

final class UserEmailUpdated implements Event
{
    use IsDomainEvent;

    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {
    }
}

it('should generate a valid UUID as eventId', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 1);

    expect(Uuid::isValid($event->eventId()))->toBeTrue();
});

it('should generate unique eventId per instance', function () {
    $first = SampleItemCreated::new(itemId: 'item-1', name: 'Sword', quantity: 1);
    $second = SampleItemCreated::new(itemId: 'item-2', name: 'Shield', quantity: 1);

    expect($first->eventId())->not->toBe($second->eventId());
});

it('should derive eventName from class name in dotted format', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 1);

    expect($event->eventName())->toBe('sample.item.created');
});

it('should derive eventName with multiple words correctly', function () {
    $event = UserEmailUpdated::new(userId: 'user-123', email: 'new@example.com');

    expect($event->eventName())->toBe('user.email.updated');
});

it('should build payload from public properties', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 5);

    expect($event->payload())->toBe([
        'itemId' => 'item-123',
        'name' => 'Sword',
        'quantity' => 5,
    ]);
});

it('should generate occurredOn in ISO-8601 format', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 1);

    $parsed = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $event->occurredOn());
    expect($parsed)->toBeInstanceOf(\DateTimeImmutable::class);
});

it('should allow direct property access', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 3);

    expect($event->itemId)->toBe('item-123')
        ->and($event->name)->toBe('Sword')
        ->and($event->quantity)->toBe(3);
});

it('should implement the Event contract', function () {
    $event = SampleItemCreated::new(itemId: 'item-123', name: 'Sword', quantity: 1);

    expect($event)->toBeInstanceOf(Event::class);
});

it('should work with make() alias', function () {
    $event = SampleItemCreated::make(itemId: 'item-123', name: 'Sword', quantity: 1);

    expect($event)->toBeInstanceOf(Event::class)
        ->and(Uuid::isValid($event->eventId()))->toBeTrue()
        ->and($event->eventName())->toBe('sample.item.created');
});

it('should work with positional arguments', function () {
    $event = SampleItemCreated::new('item-123', 'Sword', 1);

    expect($event->itemId)->toBe('item-123')
        ->and($event->name)->toBe('Sword')
        ->and($event->quantity)->toBe(1);
});
