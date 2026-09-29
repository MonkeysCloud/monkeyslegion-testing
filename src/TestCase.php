<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing;

use MonkeysLegion\DI\Container;
use MonkeysLegion\Framework\Application;
use MonkeysLegion\Http\MiddlewareDispatcher;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Base test case that boots the application and provides a TestHttpClient.
 *
 * Compose with concern traits for additional capabilities:
 *   - RefreshDatabase / DatabaseTransactions for DB testing
 *   - InteractsWithAuth for actingAs()
 *   - InteractsWithDatabase for assertDatabaseHas()
 *   - FakesServices for Queue/Mail/Event fakes
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected Container $container;
    protected TestHttpClient $client;

    /** @var bool Whether the application kernel has been booted (cached across tests in same process). */
    private static bool $booted = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootApplication();
        $this->client = $this->createClient();
    }

    protected function tearDown(): void
    {
        // Reset per-test state — container persists but client is fresh per test.
        parent::tearDown();
    }

    /**
     * Boot the application kernel (cached across tests in the same process).
     */
    protected function bootApplication(): void
    {
        if (!defined('ML_BASE_PATH')) {
            define('ML_BASE_PATH', realpath(getcwd() ?: '.'));
        }

        // Always re-create the container so tests get fresh service instances.
        $this->container = Application::create(basePath: ML_BASE_PATH)->boot();
        self::$booted = true;
    }

    /**
     * Create a TestHttpClient wired to the middleware dispatcher.
     */
    protected function createClient(): TestHttpClient
    {
        if ($this->container->has(MiddlewareDispatcher::class)) {
            $dispatcher = $this->container->get(MiddlewareDispatcher::class);
        } else {
            $this->markTestSkipped('MiddlewareDispatcher not available in container.');
            $dispatcher = $this->createStub(MiddlewareDispatcher::class);
        }

        return new TestHttpClient($dispatcher);
    }

    /**
     * Resolve a service from the container.
     */
    protected function app(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Swap a service in the container (for fakes).
     */
    protected function swap(string $id, object $instance): void
    {
        $this->container->set($id, $instance);
    }

    // ── Convenience HTTP methods (delegate to client) ───────────

    protected function get(string $uri, array $headers = []): TestResponse
    {
        return $this->client->get($uri, $headers);
    }

    protected function getJson(string $uri, array $headers = []): TestResponse
    {
        return $this->client->getJson($uri, $headers);
    }

    protected function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->post($uri, $data, $headers);
    }

    protected function postJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->postJson($uri, $data, $headers);
    }

    protected function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->put($uri, $data, $headers);
    }

    protected function putJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->putJson($uri, $data, $headers);
    }

    protected function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->patchJson($uri, $data, $headers);
    }

    protected function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->client->delete($uri, $headers);
    }

    protected function deleteJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->client->deleteJson($uri, $data, $headers);
    }
}
