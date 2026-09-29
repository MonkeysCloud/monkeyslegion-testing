<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit\Fakes;

use MonkeysLegion\Testing\Fakes\EventFake;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventFakeTest extends TestCase
{
    private EventFake $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = new EventFake();
        EventFake::setInstance($this->events);
    }

    protected function tearDown(): void
    {
        EventFake::reset();
        parent::tearDown();
    }

    #[Test]
    public function dispatch_records_event(): void
    {
        $event = new \stdClass();
        $event->data = 'test';

        $this->events->dispatch($event);

        EventFake::assertDispatched(\stdClass::class);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_not_dispatched_when_nothing_dispatched(): void
    {
        EventFake::assertNotDispatched(\stdClass::class);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_dispatched_count(): void
    {
        $this->events->dispatch(new \stdClass());
        $this->events->dispatch(new \stdClass());

        EventFake::assertDispatchedCount(2);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_nothing_dispatched(): void
    {
        EventFake::assertNothingDispatched();
        self::assertTrue(true);
    }

    #[Test]
    public function assert_dispatched_with_callback_filter(): void
    {
        $event = new \stdClass();
        $event->id = 42;

        $this->events->dispatch($event);

        EventFake::assertDispatched(\stdClass::class, fn($e) => $e->id === 42);
        self::assertTrue(true);
    }

    #[Test]
    public function listen_registers_listener_and_calls_it_on_dispatch(): void
    {
        $called = false;
        $this->events->listen(\stdClass::class, function (\stdClass $event) use (&$called) {
            $called = true;
        });

        $this->events->dispatch(new \stdClass());

        self::assertTrue($called);
    }

    #[Test]
    public function assert_listening_passes_when_listener_registered(): void
    {
        $this->events->listen(\stdClass::class, 'MyListenerClass');

        EventFake::assertListening(\stdClass::class, 'MyListenerClass');
        self::assertTrue(true);
    }


    #[Test]
    public function get_dispatched_of_type_filters_by_class(): void
    {
        $event1 = new \stdClass();
        $event2 = new class {};

        $this->events->dispatch($event1);
        $this->events->dispatch($event2);

        $stdEvents = $this->events->getDispatchedOfType(\stdClass::class);
        self::assertCount(1, $stdEvents);
    }
}
