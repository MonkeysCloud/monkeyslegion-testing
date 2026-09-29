<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Fakes;

use MonkeysLegion\Queue\Contracts\JobInterface;
use MonkeysLegion\Queue\Contracts\QueueInterface;
use PHPUnit\Framework\AssertionFailedError;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * In-memory fake queue that records pushed jobs for assertion.
 *
 * Usage:
 *   $this->fakeQueue();
 *   MyJob::dispatch(['key' => 'value']);
 *   QueueFake::assertPushed(MyJob::class);
 *   QueueFake::assertPushedOn('emails', MyJob::class);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class QueueFake implements QueueInterface
{
    /** @var array<string, list<array{job: string, payload?: array}>> */
    private array $pushed = [];

    /** @var list<array{delay: int, job: string, payload?: array, queue: string}> */
    private array $delayed = [];

    /** @var list<array{job: string, payload?: array, queue: string}> */
    private array $bulkPushed = [];

    public function push(array $jobData, string $queue = 'default'): void
    {
        $this->pushed[$queue][] = $jobData;
    }

    public function later(int $delayInSeconds, array $jobData, string $queue = 'default'): void
    {
        $this->delayed[] = [
            'delay'    => $delayInSeconds,
            'job'      => $jobData['job'],
            'payload'  => $jobData['payload'] ?? [],
            'queue'    => $queue,
        ];
    }

    public function bulk(array $jobs, string $queue = 'default'): void
    {
        foreach ($jobs as $job) {
            $this->bulkPushed[] = [
                'job'     => $job['job'],
                'payload' => $job['payload'] ?? [],
                'queue'   => $queue,
            ];
        }
    }

    public function pop(string $queue = 'default'): ?JobInterface
    {
        return null; // Fake queue never processes jobs
    }

    public function ack(JobInterface $job): void
    {
        // No-op
    }

    public function release(JobInterface $job, int $delay = 0): void
    {
        // No-op
    }

    public function fail(JobInterface $job, ?\Throwable $e = null): void
    {
        // No-op
    }

    public function size(string $queue = 'default'): int
    {
        return $this->count($queue);
    }

    public function clear(string $queue = 'default'): void
    {
        $this->pushed[$queue] = [];
    }

    public function listQueue(string $queue = 'default', int $limit = 100): array
    {
        $jobs = $this->pushed[$queue] ?? [];
        return array_slice($jobs, 0, $limit);
    }

    public function count(string $queue = 'default'): int
    {
        return count($this->pushed[$queue] ?? []);
    }

    public function getFailed(int $limit = 100): array
    {
        return [];
    }

    public function clearFailed(): void
    {
        // No-op
    }

    public function countFailed(): int
    {
        return 0;
    }

    public function purge(): void
    {
        $this->pushed = [];
        $this->delayed = [];
        $this->bulkPushed = [];
    }

    public function retryFailed(int $limit = 100): void
    {
        // No-op
    }

    public function removeFailedJobs(string|array $jobIds): void
    {
        // No-op
    }

    public function peek(string $queue = 'default'): ?JobInterface
    {
        return null;
    }

    public function moveJobToQueue(string $jobId, string $fromQueue, string $toQueue): void
    {
        // No-op
    }

    public function processDelayedJobs(string $queue = 'default'): int
    {
        return 0;
    }

    public function getStats(string $queue = 'default'): array
    {
        return [
            'ready'      => $this->count($queue),
            'processing' => 0,
            'delayed'    => count(array_filter($this->delayed, fn($d) => $d['queue'] === $queue)),
            'failed'     => 0,
            'total_pushed' => $this->totalPushed(),
        ];
    }

    public function findJob(string $jobId, string $queue = 'default'): ?JobInterface
    {
        return null;
    }

    public function deleteJob(string $id, string $queue = 'default'): bool
    {
        return false;
    }

    public function getQueues(): array
    {
        return array_keys($this->pushed);
    }

    public function getSettings(): array
    {
        return ['driver' => 'fake'];
    }

    // ── Assertions ──────────────────────────────────────────────

    /**
     * Assert that a job of the given class was pushed.
     *
     * @param string $jobClass
     * @param callable|null $callback Optional callback to filter: fn(array $jobData): bool
     */
    public static function assertPushed(string $jobClass, ?callable $callback = null): void
    {
        $instance = self::instance();
        $jobs = $instance->allPushedJobs();

        $matching = array_filter($jobs, fn($j) => ($j['job'] ?? null) === $jobClass);

        if ($callback !== null) {
            $matching = array_filter($matching, $callback);
        }

        if ($matching === []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that job "%s" was pushed.', $jobClass)
            );
        }
    }

    /**
     * Assert that a job was NOT pushed.
     */
    public static function assertNotPushed(string $jobClass): void
    {
        $instance = self::instance();
        $jobs = $instance->allPushedJobs();

        $matching = array_filter($jobs, fn($j) => ($j['job'] ?? null) === $jobClass);

        if ($matching !== []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that job "%s" was not pushed. It was pushed %d time(s).', $jobClass, count($matching))
            );
        }
    }

    /**
     * Assert that a job was pushed onto a specific queue.
     */
    public static function assertPushedOn(string $queue, string $jobClass): void
    {
        $instance = self::instance();
        $jobs = $instance->pushed[$queue] ?? [];

        $matching = array_filter($jobs, fn($j) => ($j['job'] ?? null) === $jobClass);

        if ($matching === []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that job "%s" was pushed on queue "%s".', $jobClass, $queue)
            );
        }
    }

    /**
     * Assert the total number of jobs pushed.
     */
    public static function assertPushedCount(int $expected): void
    {
        $instance = self::instance();
        $actual = $instance->totalPushed();

        if ($actual !== $expected) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that %d job(s) were pushed. Got %d.', $expected, $actual)
            );
        }
    }

    /**
     * Assert nothing was pushed.
     */
    public static function assertNothingPushed(): void
    {
        $instance = self::instance();
        if ($instance->totalPushed() > 0) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that no jobs were pushed. Got %d.', $instance->totalPushed())
            );
        }
    }

    // ── Internal helpers ────────────────────────────────────────

    private static ?QueueFake $instance = null;

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
            throw new \RuntimeException('QueueFake not initialized. Call fakeQueue() first.');
        }
        return self::$instance;
    }

    /**
     * @return list<array{job: string, payload?: array}>
     */
    private function allPushedJobs(): array
    {
        $all = [];
        foreach ($this->pushed as $jobs) {
            $all = array_merge($all, $jobs);
        }
        $all = array_merge($all, array_map(
            fn($b) => ['job' => $b['job'], 'payload' => $b['payload']],
            $this->bulkPushed
        ));
        return $all;
    }

    private function totalPushed(): int
    {
        $count = 0;
        foreach ($this->pushed as $jobs) {
            $count += count($jobs);
        }
        $count += count($this->bulkPushed);
        return $count;
    }
}
