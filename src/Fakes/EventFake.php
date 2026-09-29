<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Fakes;

use PHPUnit\Framework\AssertionFailedError;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * In-memory fake event dispatcher that records dispatched events for assertion.
 *
 * Usage:
 *   $this->fakeEvents();
 *   $dispatcher->dispatch(new UserCreated($user));
 *   EventFake::assertDispatched(UserCreated::class);
 *   EventFake::assertDispatched(UserCreated::class, fn($e) => $e->user->id === 1);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class EventFake implements EventDispatcherInterface
{
    /** @var list<object> */
    private array $dispatched = [];

    /** @var array<class-string, list<callable>> */
    private array $listeners = [];

    /**
     * Dispatch an event, recording it for assertions.
     *
     * @template T of object
     * @param T $event
     * @return T
     */
    public function dispatch(object $event): object
    {
        $this->dispatched[] = $event;

        // Call registered listeners (for assertListening support)
        $eventClass = $event::class;
        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            $listener($event);
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }

    /**
     * Register a listener for an event class.
     *
     * @param class-string $eventClass
     * @param callable $listener
     */
    public function listen(string $eventClass, callable|string $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Get all dispatched events.
     *
     * @return list<object>
     */
    public function getDispatched(): array
    {
        return $this->dispatched;
    }

    /**
     * Get dispatched events of a specific class.
     *
     * @param class-string $eventClass
     * @return list<object>
     */
    public function getDispatchedOfType(string $eventClass): array
    {
        return array_values(array_filter(
            $this->dispatched,
            fn($e) => $e instanceof $eventClass
        ));
    }

    // ── Assertions ──────────────────────────────────────────────

    /**
     * Assert that an event of the given class was dispatched.
     *
     * @param class-string $eventClass
     * @param callable|null $callback Optional filter: fn($event): bool
     */
    public static function assertDispatched(string $eventClass, ?callable $callback = null): void
    {
        $instance = self::instance();
        $events = $instance->getDispatchedOfType($eventClass);

        if ($callback !== null) {
            $events = array_filter($events, $callback);
        }

        if ($events === []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that event "%s" was dispatched.', $eventClass)
            );
        }
    }

    /**
     * Assert that an event was NOT dispatched.
     *
     * @param class-string $eventClass
     */
    public static function assertNotDispatched(string $eventClass): void
    {
        $instance = self::instance();
        $events = $instance->getDispatchedOfType($eventClass);

        if ($events !== []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that event "%s" was not dispatched. It was dispatched %d time(s).', $eventClass, count($events))
            );
        }
    }

    /**
     * Assert the total number of dispatched events.
     */
    public static function assertDispatchedCount(int $expected): void
    {
        $instance = self::instance();
        $actual = count($instance->dispatched);

        if ($actual !== $expected) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that %d event(s) were dispatched. Got %d.', $expected, $actual)
            );
        }
    }

    /**
     * Assert nothing was dispatched.
     */
    public static function assertNothingDispatched(): void
    {
        $instance = self::instance();
        if ($instance->dispatched !== []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that no events were dispatched. Got %d.', count($instance->dispatched))
            );
        }
    }

    /**
     * Assert that a listener is registered for an event.
     *
     * @param class-string $eventClass
     */
    public static function assertListening(string $eventClass, callable|string $listener): void
    {
        $instance = self::instance();
        $listeners = $instance->listeners[$eventClass] ?? [];

        if ($listeners === []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that any listener is registered for event "%s".', $eventClass)
            );
        }

        if (is_string($listener)) {
            // Check if any listener is the given class@method or class
            $found = false;
            foreach ($listeners as $l) {
                if (is_array($l) && is_object($l[0]) && $l[0]::class === $listener) {
                    $found = true; break;
                }
                if (is_string($l) && $l === $listener) {
                    $found = true; break;
                }
            }
            if (!$found) {
                throw new AssertionFailedError(
                    sprintf('Failed asserting that listener "%s" is registered for event "%s".', $listener, $eventClass)
                );
            }
        }
    }

    // ── Internal helpers ────────────────────────────────────────

    private static ?EventFake $instance = null;

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('EventFake not initialized. Call fakeEvents() first.');
        }
        return self::$instance;
    }
}
