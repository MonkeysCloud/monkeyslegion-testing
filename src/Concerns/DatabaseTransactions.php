<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Database\Contracts\ConnectionInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Wrap each test in a database transaction that rolls back automatically.
 *
 * Unlike RefreshDatabase, this does NOT run migrations — assumes the schema
 * is already in place (e.g., from a previous test class or manual setup).
 *
 * Usage:
 *   final class UserTest extends TestCase
 *   {
 *       use DatabaseTransactions;
 *
 *       public function test_create(): void
 *       {
 *           // Changes are rolled back after the test.
 *       }
 *   }
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait DatabaseTransactions
{
    /**
     * Begin a transaction for this test.
     */
    protected function bootDatabaseTransactions(): void
    {
        $connection = $this->container->get(ConnectionInterface::class);
        $connection->beginTransaction();
    }

    /**
     * Roll back the test transaction.
     */
    protected function rollbackDatabaseTransactions(): void
    {
        $connection = $this->container->get(ConnectionInterface::class);

        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
    }
}
