<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Database\Contracts\ConnectionInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Database assertion helpers.
 *
 * Usage:
 *   $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
 *   $this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
 *   $this->assertDatabaseCount('posts', 5);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait InteractsWithDatabase
{
    /**
     * Assert that a row matching the criteria exists in the table.
     *
     * @param string $table
     * @param array<string, mixed> $data
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $count = $this->getDatabaseCount($table, $data);
        if ($count === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that table "%s" contains a row matching %s.',
                    $table,
                    json_encode($data, JSON_UNESCAPED_SLASHES)
                )
            );
        }
    }

    /**
     * Assert that no row matching the criteria exists in the table.
     *
     * @param string $table
     * @param array<string, mixed> $data
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $count = $this->getDatabaseCount($table, $data);
        if ($count > 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that table "%s" does not contain a row matching %s. Found %d row(s).',
                    $table,
                    json_encode($data, JSON_UNESCAPED_SLASHES),
                    $count
                )
            );
        }
    }

    /**
     * Assert that the table has the expected number of rows.
     *
     * @param string $table
     * @param int $expected
     * @param array<string, mixed> $data Optional WHERE criteria
     */
    protected function assertDatabaseCount(string $table, int $expected, array $data = []): void
    {
        $actual = $this->getDatabaseCount($table, $data);
        if ($actual !== $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Failed asserting that table "%s" has %d row(s). Got %d.', $table, $expected, $actual)
            );
        }
    }

    /**
     * Assert that a row exists and is soft-deleted (has a non-null deleted_at).
     *
     * @param string $table
     * @param array<string, mixed> $data
     */
    protected function assertSoftDeleted(string $table, array $data): void
    {
        $criteria = array_merge($data, ['deleted_at' => null]);
        $count = $this->getDatabaseCount($table, $criteria);

        if ($count > 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that row in "%s" matching %s is soft-deleted. Found %d non-deleted row(s).',
                    $table,
                    json_encode($data, JSON_UNESCAPED_SLASHES),
                    $count
                )
            );
        }

        // Also verify the row exists at all (even if soft-deleted)
        $totalCount = $this->getDatabaseCount($table, $data);
        if ($totalCount === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that table "%s" contains a row matching %s (soft-deleted or not).',
                    $table,
                    json_encode($data, JSON_UNESCAPED_SLASHES)
                )
            );
        }
    }

    /**
     * Assert that a row exists and is NOT soft-deleted.
     *
     * @param string $table
     * @param array<string, mixed> $data
     */
    protected function assertNotSoftDeleted(string $table, array $data): void
    {
        $criteria = array_merge($data, ['deleted_at' => null]);
        $count = $this->getDatabaseCount($table, $criteria);

        if ($count === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that row in "%s" matching %s is NOT soft-deleted.',
                    $table,
                    json_encode($data, JSON_UNESCAPED_SLASHES)
                )
            );
        }
    }

    /**
     * Get the count of rows matching the criteria.
     *
     * @param string $table
     * @param array<string, mixed> $data
     */
    private function getDatabaseCount(string $table, array $data): int
    {
        $connection = $this->container->get(ConnectionInterface::class);

        if ($data === []) {
            $stmt = $connection->query("SELECT COUNT(*) AS cnt FROM {$table}");
        } else {
            $where = implode(' AND ', array_map(fn($k) => "{$k} " . ($data[$k] === null ? 'IS NULL' : '= ?'), array_keys($data)));
            $params = array_values(array_filter($data, fn($v) => $v !== null));
            $stmt = $connection->query("SELECT COUNT(*) AS cnt FROM {$table} WHERE {$where}", $params);
        }

        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
