<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Middleware test helpers for excluding specific middleware from the pipeline.
 *
 * Usage:
 *   $this->withoutMiddleware(AuthMiddleware::class)
 *        ->get('/protected')
 *        ->assertOk();
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait InteractsWithMiddleware
{
    /**
     * Exclude the given middleware classes from the request pipeline.
     *
     * @param string ...$classes Fully-qualified middleware class names
     */
    protected function withoutMiddleware(string ...$classes): void
    {
        $this->client = $this->client->withoutMiddleware(...$classes);
    }

    /**
     * Re-enable all middleware (clears exclusions).
     * The client is already fresh per test, but this can be called
     * mid-test after a withoutMiddleware() call.
     */
    protected function withMiddleware(): void
    {
        // Re-create the client to clear exclusions.
        $this->client = $this->createClient();
    }
}
