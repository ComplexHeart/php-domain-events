<?php

declare(strict_types=1);

namespace ComplexHeart\Domain\Events;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionProperty;

/**
 * Trait IsDomainEvent
 *
 * Provides a default implementation of the Event contract.
 * Auto-generates eventId (UUID), eventName (dotted from class name),
 * payload (from public properties), and occurredOn (ISO-8601 timestamp).
 *
 * Usage:
 *   final class OrderPlaced implements Event
 *   {
 *       use IsDomainEvent;
 *
 *       public function __construct(
 *           public readonly string $orderId,
 *           public readonly string $customerId,
 *       ) {}
 *   }
 *
 *   $event = OrderPlaced::new(orderId: 'uuid', customerId: 'uuid');
 *   $event->eventId();    // auto-generated UUID
 *   $event->eventName();  // "order.placed"
 *   $event->payload();    // ['orderId' => '...', 'customerId' => '...']
 *   $event->occurredOn(); // "2026-03-15T10:30:00+00:00"
 *
 * @author Unay Santisteban <usantisteban@othercode.io>
 */
trait IsDomainEvent
{
    /**
     * The unique identifier for this event instance.
     */
    private string $_eventId;

    /**
     * The timestamp when this event occurred (ISO-8601).
     */
    private string $_occurredOn;

    /**
     * Create a new domain event instance.
     *
     * @param mixed ...$params Constructor parameters
     * @return static
     */
    public static function new(mixed ...$params): static
    {
        $instance = new static(...$params);
        $instance->_eventId = Uuid::uuid4()->toString();
        $instance->_occurredOn = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

        return $instance;
    }

    /**
     * Alias for new() — consistent with ComplexHeart naming conventions.
     *
     * @param mixed ...$params Constructor parameters
     * @return static
     */
    public static function make(mixed ...$params): static
    {
        return static::new(...$params);
    }

    /**
     * The unique id for the current domain event (UUID).
     */
    public function eventId(): string
    {
        return $this->_eventId;
    }

    /**
     * The unique domain event name, derived from the class name.
     *
     * Converts PascalCase class name to dotted lowercase:
     *   CharacterCreated  → character.created
     *   UserEmailUpdated  → user.email.updated
     *   OrderPlaced       → order.placed
     */
    public function eventName(): string
    {
        $className = basename(str_replace('\\', '/', static::class));

        return strtolower(
            (string) preg_replace('/([a-z])([A-Z])/', '$1.$2', $className)
        );
    }

    /**
     * Returns the event payload built from public instance properties.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $payload = [];
        $ref = new ReflectionClass(static::class);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $payload[$prop->getName()] = $prop->getValue($this);
        }

        return $payload;
    }

    /**
     * The timestamp when the domain event occurred in ISO-8601.
     */
    public function occurredOn(): string
    {
        return $this->_occurredOn;
    }
}
