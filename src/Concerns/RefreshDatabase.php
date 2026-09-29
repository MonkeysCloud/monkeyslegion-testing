<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Database\Contracts\ConnectionInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Run migrations once per test class, wrap each test in a transaction
 * that rolls back automatically.
 *
 * Usage:
 *   final class UserTest extends TestCase
 *   {
 *       use RefreshDatabase;
 *
 *       public function test_create(): void
 *       {
 *           // Database is freshly migrated.
 *           // Changes are rolled back after the test.
 *       }
 *   }
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait RefreshDatabase
{
    private static bool $migrationsRun = false;

    /**
     * Boot the database trait — runs migrations once, then wraps each test.
     */
    protected function bootRefreshDatabase(): void
    {
        $connection = $this->container->get(ConnectionInterface::class);

        if (!self::$migrationsRun) {
            $this->runMigrations($connection);
            self::$migrationsRun = true;
        }

        // Begin a transaction for this test — will be rolled back in tearDown.
        $connection->beginTransaction();
    }

    /**
     * Roll back the test transaction.
     */
    protected function rollbackRefreshDatabase(): void
    {
        $connection = $this->container->get(ConnectionInterface::class);

        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(ConnectionInterface $connection): void
    {
        // Try to use MigrationRunner if available.
        if ($this->container->has(\MonkeysLegion\Migration\Runner\MigrationRunner::class)) {
            $runner = $this->container->get(\MonkeysLegion\Migration\Runner\MigrationRunner::class);
            $migrationsDir = $this->getMigrationsDir();
            $runner->run($migrationsDir);
        }
    }

    /**
     * Get the migrations directory path.
     */
    protected function getMigrationsDir(): string
    {
        return defined('ML_BASE_PATH')
            ? ML_BASE_PATH . '/database/migrations'
            : getcwd() . '/database/migrations';
    }

    /**
     * Reset the migration state (for testing the trait itself).
     */
    protected static function resetRefreshDatabaseState(): void
    {
        self::$migrationsRun = false;
    }
}
