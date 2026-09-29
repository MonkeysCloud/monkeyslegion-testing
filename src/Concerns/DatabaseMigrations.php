<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Database\Contracts\ConnectionInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Run migrations fresh (drop + recreate) before each test.
 * Slower than RefreshDatabase, but guarantees a completely clean state.
 *
 * Usage:
 *   final class UserTest extends TestCase
 *   {
 *       use DatabaseMigrations;
 *   }
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait DatabaseMigrations
{
    /**
     * Run a fresh migration before each test.
     */
    protected function bootDatabaseMigrations(): void
    {
        if ($this->container->has(\MonkeysLegion\Migration\Runner\MigrationRunner::class)) {
            $runner = $this->container->get(\MonkeysLegion\Migration\Runner\MigrationRunner::class);
            $migrationsDir = defined('ML_BASE_PATH')
                ? ML_BASE_PATH . '/database/migrations'
                : getcwd() . '/database/migrations';
            $runner->fresh($migrationsDir);
        }
    }
}
