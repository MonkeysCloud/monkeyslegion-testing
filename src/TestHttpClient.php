<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\UriFactory;
use Laminas\Diactoros\UploadedFileFactory;
use MonkeysLegion\Auth\Contract\AuthenticatableInterface;
use MonkeysLegion\Http\MiddlewareDispatcher;
use MonkeysLegion\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Fluent HTTP request builder that dispatches through the real middleware
 * pipeline and returns TestResponse instances.
 *
 * Usage:
 *   $this->client->get('/api/users')->assertOk()->assertJsonStructure(['data']);
 *   $this->client->withToken($jwt)->postJson('/api/posts', $data)->assertCreated();
 *   $this->client->actingAs($user)->get('/dashboard')->assertOk();
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class TestHttpClient
{
    /** @var array<string, string|string[]> */
    private array $headers = [];

    /** @var array<string, mixed> */
    private array $session = [];

    /** @var array<string, array{name: string, value: string}> */
    private array $cookies = [];

    /** @var array<string, mixed> */
    private array $serverParams = [];

    /** @var list<string> Middleware classes to exclude */
    private array $excludedMiddleware = [];

    private ?AuthenticatableInterface $actingAsUser = null;
    private ?string $guard = null;

    public function __construct(
        private readonly MiddlewareDispatcher $dispatcher,
    ) {}

    // ── Fluent builders ─────────────────────────────────────────

    /**
     * @param array<string, string|string[]> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($this->headers, $headers);
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->withHeader('Authorization', $type . ' ' . $token);
    }

    public function withBasicAuth(string $username, string $password): self
    {
        return $this->withHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
    }

    /**
     * @param array<string, mixed> $session
     */
    public function withSession(array $session): self
    {
        $clone = clone $this;
        $clone->session = array_merge($this->session, $session);
        return $clone;
    }

    public function withCookie(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->cookies[$name] = ['name' => $name, 'value' => $value];
        return $clone;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function withServerParams(array $serverParams): self
    {
        $clone = clone $this;
        $clone->serverParams = array_merge($this->serverParams, $serverParams);
        return $clone;
    }

    public function actingAs(AuthenticatableInterface $user, ?string $guard = null): self
    {
        $clone = clone $this;
        $clone->actingAsUser = $user;
        $clone->guard = $guard;
        return $clone;
    }

    public function withoutMiddleware(string ...$classes): self
    {
        $clone = clone $this;
        $clone->excludedMiddleware = array_merge($this->excludedMiddleware, $classes);
        return $clone;
    }

    // ── HTTP methods ────────────────────────────────────────────

    /**
     * @param array<string, string> $headers
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->send('GET', $uri, null, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public function getJson(string $uri, array $headers = []): TestResponse
    {
        return $this->send('GET', $uri, null, array_merge(['Accept' => 'application/json'], $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('POST', $uri, http_build_query($data), array_merge(['Content-Type' => 'application/x-www-form-urlencoded'], $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function postJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('POST', $uri, json_encode($data, JSON_THROW_ON_ERROR), array_merge(['Content-Type' => 'application/json', 'Accept' => 'application/json'], $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('PUT', $uri, http_build_query($data), array_merge(['Content-Type' => 'application/x-www-form-urlencoded'], $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function putJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('PUT', $uri, json_encode($data, JSON_THROW_ON_ERROR), array_merge(['Content-Type' => 'application/json', 'Accept' => 'application/json'], $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('PATCH', $uri, json_encode($data, JSON_THROW_ON_ERROR), array_merge(['Content-Type' => 'application/json', 'Accept' => 'application/json'], $headers));
    }

    /**
     * @param array<string, string> $headers
     */
    public function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->send('DELETE', $uri, null, $headers);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function deleteJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->send('DELETE', $uri, json_encode($data, JSON_THROW_ON_ERROR), array_merge(['Content-Type' => 'application/json', 'Accept' => 'application/json'], $headers));
    }

    // ── Dispatch ────────────────────────────────────────────────

    /**
     * Build and dispatch a request through the middleware pipeline.
     *
     * @param array<string, string> $headers
     */
    private function send(string $method, string $uri, ?string $body, array $headers): TestResponse
    {
        $request = $this->buildRequest($method, $uri, $body, $headers);
        $response = $this->dispatcher->handle($request);
        return new TestResponse($response);
    }

    /**
     * Build a PSR-7 ServerRequest with all configured headers, session, cookies, and auth.
     *
     * @param array<string, string> $headers
     */
    private function buildRequest(string $method, string $uri, ?string $body, array $headers): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest($method, (new UriFactory())->createUri($uri), $this->serverParams);

        // Merge default and per-request headers
        $allHeaders = array_merge($this->headers, $headers);
        foreach ($allHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Add body
        if ($body !== null) {
            $request = $request->withBody(Stream::createFromString($body));
        }

        // Add cookies
        if ($this->cookies !== []) {
            $cookieString = implode('; ', array_map(
                fn($c) => $c['name'] . '=' . $c['value'],
                $this->cookies
            ));
            $request = $request->withHeader('Cookie', $cookieString);
        }

        // Add session data as request attribute (middleware can pick it up)
        if ($this->session !== []) {
            $request = $request->withAttribute('__session_override', $this->session);
        }

        // Add authenticated user as request attribute
        if ($this->actingAsUser !== null) {
            $request = $request->withAttribute('auth.user', $this->actingAsUser);
            if ($this->guard !== null) {
                $request = $request->withAttribute('auth.guard', $this->guard);
            }
        }

        // Add excluded middleware as request attribute
        if ($this->excludedMiddleware !== []) {
            $request = $request->withAttribute('__excluded_middleware', $this->excludedMiddleware);
        }

        return $request;
    }
}
