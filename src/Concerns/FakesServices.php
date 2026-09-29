<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Mail\TransportInterface;
use MonkeysLegion\Queue\Contracts\QueueInterface;
use MonkeysLegion\Testing\Fakes\EventFake;
use MonkeysLegion\Testing\Fakes\MailFake;
use MonkeysLegion\Testing\Fakes\QueueFake;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Trait for swapping real subsystem implementations with fakes in the container.
 *
 * Usage:
 *   $this->fakeQueue();
 *   $this->fakeMail();
 *   $this->fakeEvents();
 *
 * Then use the static assertion methods:
 *   QueueFake::assertPushed(MyJob::class);
 *   MailFake::assertSent();
 *   EventFake::assertDispatched(MyEvent::class);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait FakesServices
{
    /**
     * Swap the queue implementation with a QueueFake.
     */
    protected function fakeQueue(): QueueFake
    {
        $fake = new QueueFake();
        QueueFake::setInstance($fake);
        $this->swap(QueueInterface::class, $fake);
        return $fake;
    }

    /**
     * Swap the mail transport with a MailFake.
     */
    protected function fakeMail(): MailFake
    {
        $fake = new MailFake();
        MailFake::setInstance($fake);
        $this->swap(TransportInterface::class, $fake);
        return $fake;
    }

    /**
     * Swap the event dispatcher with an EventFake.
     */
    protected function fakeEvents(): EventFake
    {
        $fake = new EventFake();
        EventFake::setInstance($fake);
        $this->swap(EventDispatcherInterface::class, $fake);
        return $fake;
    }

    /**
     * Fake all subsystems at once.
     */
    protected function fakeAll(): void
    {
        $this->fakeQueue();
        $this->fakeMail();
        $this->fakeEvents();
    }

    /**
     * Reset all fakes (called automatically in tearDown).
     */
    protected function resetFakes(): void
    {
        QueueFake::reset();
        MailFake::reset();
        EventFake::reset();
    }
}
