<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Pest PHP adapter — registers testing traits as Pest helpers.
 *
 * Only loaded when pestphp/pest is installed.
 *
 * Usage in tests/Pest.php:
 *   uses(MonkeysLegion\Testing\PestAdapter::class);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 *
 * @requires pestphp/pest (optional)
 */
trait PestAdapter
{
    /**
     * Pest beforeEach hook — boot the application and create the client.
     * Override in your test file if you need custom setup.
     */
    protected function pestSetUp(): void
    {
        $this->bootApplication();
        $this->client = $this->createClient();
    }

    /**
     * Pest afterEach hook — reset fakes and rollback transactions.
     */
    protected function pestTearDown(): void
    {
        // Reset fakes if FakesServices trait is used
        if (method_exists($this, 'resetFakes')) {
            $this->resetFakes();
        }

        // Rollback transactions if DatabaseTransactions trait is used
        if (method_exists($this, 'rollbackDatabaseTransactions')) {
            $this->rollbackDatabaseTransactions();
        }

        if (method_exists($this, 'rollbackRefreshDatabase')) {
            $this->rollbackRefreshDatabase();
        }
    }
}
