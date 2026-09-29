<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit\Fakes;

use MonkeysLegion\Testing\Fakes\QueueFake;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QueueFakeTest extends TestCase
{
    private QueueFake $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new QueueFake();
        QueueFake::setInstance($this->queue);
    }

    protected function tearDown(): void
    {
        QueueFake::reset();
        parent::tearDown();
    }

    #[Test]
    public function push_records_job(): void
    {
        $this->queue->push(['job' => 'App\\Job\\SendEmail', 'payload' => ['to' => 'test@test.com']]);

        QueueFake::assertPushed('App\\Job\\SendEmail');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_pushed_on_specific_queue(): void
    {
        $this->queue->push(['job' => 'App\\Job\\ProcessPayment'], 'payments');

        QueueFake::assertPushedOn('payments', 'App\\Job\\ProcessPayment');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_not_pushed_when_no_job_pushed(): void
    {
        QueueFake::assertNotPushed('App\\Job\\NeverRun');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_pushed_count(): void
    {
        $this->queue->push(['job' => 'App\\Job\\Task1']);
        $this->queue->push(['job' => 'App\\Job\\Task2']);

        QueueFake::assertPushedCount(2);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_nothing_pushed(): void
    {
        QueueFake::assertNothingPushed();
        self::assertTrue(true);
    }

    #[Test]
    public function push_later_records_delayed_job(): void
    {
        $this->queue->later(60, ['job' => 'App\\Job\\DelayedTask']);

        // Delayed jobs don't count as "pushed" in the pushed array
        QueueFake::assertPushedCount(0);
        self::assertTrue(true); // assertion performed via assertPushedCount
    }

    #[Test]
    public function bulk_push_records_all_jobs(): void
    {
        $this->queue->bulk([
            ['job' => 'App\\Job\\Batch1'],
            ['job' => 'App\\Job\\Batch2'],
            ['job' => 'App\\Job\\Batch3'],
        ]);

        QueueFake::assertPushedCount(3);
        QueueFake::assertPushed('App\\Job\\Batch1');
        QueueFake::assertPushed('App\\Job\\Batch3');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_pushed_with_callback_filter(): void
    {
        $this->queue->push(['job' => 'App\\Job\\EmailJob', 'payload' => ['to' => 'admin@test.com']]);
        $this->queue->push(['job' => 'App\\Job\\EmailJob', 'payload' => ['to' => 'user@test.com']]);

        QueueFake::assertPushed('App\\Job\\EmailJob', fn($j) => ($j['payload']['to'] ?? '') === 'admin@test.com');
        self::assertTrue(true);
    }

    #[Test]
    public function size_returns_queue_size(): void
    {
        self::assertSame(0, $this->queue->size());

        $this->queue->push(['job' => 'Job1']);
        $this->queue->push(['job' => 'Job2']);

        self::assertSame(2, $this->queue->size());
    }

    #[Test]
    public function pop_always_returns_null(): void
    {
        $this->queue->push(['job' => 'Job1']);

        self::assertNull($this->queue->pop());
    }

    #[Test]
    public function get_queues_returns_all_queue_names(): void
    {
        $this->queue->push(['job' => 'Job1'], 'default');
        $this->queue->push(['job' => 'Job2'], 'high');

        $queues = $this->queue->getQueues();
        self::assertContains('default', $queues);
        self::assertContains('high', $queues);
    }
}
