<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing;

use Psr\Http\Message\ResponseInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Wraps a PSR-7 ResponseInterface with fluent assertion methods.
 *
 * Usage:
 *   $this->get('/api/users')->assertOk()->assertJsonStructure(['data']);
 *   $this->postJson('/api/users', $data)->assertCreated();
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class TestResponse
{
    private ?array $decodedJson = null;

    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    /**
     * Get the underlying PSR-7 response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the response body as a string.
     */
    public function body(): string
    {
        $this->response->getBody()->rewind();
        return (string) $this->response->getBody();
    }

    /**
     * Get the status code.
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Get a header value.
     */
    public function header(string $name, string $default = ''): string
    {
        return $this->response->getHeaderLine($name) ?: $default;
    }

    /**
     * Decode the response body as JSON (cached).
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function json(): array
    {
        if ($this->decodedJson === null) {
            $body = $this->body();
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $this->decodedJson = is_array($decoded) ? $decoded : [];
        }
        return $this->decodedJson;
    }

    // ── Status Assertions ───────────────────────────────────────

    public function assertStatus(int $expected): self
    {
        $actual = $this->status();
        if ($actual !== $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected status %d, got %d. Body: %s', $expected, $actual, mb_substr($this->body(), 0, 500))
            );
        }
        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    public function assertAccepted(): self
    {
        return $this->assertStatus(202);
    }

    public function assertNoContent(): self
    {
        return $this->assertStatus(204);
    }

    public function assertBadRequest(): self
    {
        return $this->assertStatus(400);
    }

    public function assertUnauthorized(): self
    {
        return $this->assertStatus(401);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertMethodNotAllowed(): self
    {
        return $this->assertStatus(405);
    }

    public function assertUnprocessable(): self
    {
        return $this->assertStatus(422);
    }

    public function assertServerError(): self
    {
        $status = $this->status();
        if ($status < 500 || $status > 599) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected 5xx status, got %d.', $status)
            );
        }
        return $this;
    }

    // ── Redirect Assertions ─────────────────────────────────────

    public function assertRedirect(?string $to = null): self
    {
        $status = $this->status();
        if ($status < 300 || $status > 399) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected redirect status (3xx), got %d.', $status)
            );
        }

        if ($to !== null) {
            $location = $this->header('Location');
            if ($location !== $to) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    sprintf('Expected redirect to "%s", got "%s".', $to, $location)
                );
            }
        }

        return $this;
    }

    // ── Header Assertions ───────────────────────────────────────

    public function assertHeader(string $name, ?string $value = null): self
    {
        if (!$this->response->hasHeader($name)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Header "%s" not present in response.', $name)
            );
        }

        if ($value !== null && $this->header($name) !== $value) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Header "%s" expected "%s", got "%s".', $name, $value, $this->header($name))
            );
        }

        return $this;
    }

    public function assertHeaderMissing(string $name): self
    {
        if ($this->response->hasHeader($name)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Header "%s" was unexpectedly present in response.', $name)
            );
        }
        return $this;
    }

    public function assertContentType(string $expected): self
    {
        return $this->assertHeader('Content-Type', $expected);
    }

    // ── JSON Assertions ─────────────────────────────────────────

    public function assertJson(): self
    {
        $contentType = $this->header('Content-Type');
        if (!str_contains($contentType, 'application/json')) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected JSON content-type, got "%s".', $contentType)
            );
        }

        try {
            $this->json();
        } catch (\JsonException $e) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Response body is not valid JSON: %s', $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * @param array<string|int, mixed> $structure
     */
    public function assertJsonStructure(array $structure, ?array $data = null): self
    {
        $data ??= $this->json();

        foreach ($structure as $key => $value) {
            if (is_int($key)) {
                // Numeric key: just check the key exists
                if (!array_key_exists($value, $data)) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        sprintf('JSON structure key "%s" not found in response.', $value)
                    );
                }
            } else {
                if (!array_key_exists($key, $data)) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        sprintf('JSON structure key "%s" not found in response.', $key)
                    );
                }
                if (is_array($value) && is_array($data[$key])) {
                    $this->assertJsonStructure($value, $data[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Assert a specific value exists at a dot-notation path in the JSON.
     *
     * @param string $path Dot-notation path (e.g., 'data.attributes.name')
     * @param mixed $value Expected value (null = just check existence)
     */
    public function assertJsonPath(string $path, mixed $value = null): self
    {
        $data = $this->json();
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    sprintf('JSON path "%s" not found in response.', $path)
                );
            }
            $current = $current[$key];
        }

        if ($value !== null) {
            if ($current !== $value) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    sprintf('JSON path "%s" expected %s, got %s.', $path, var_export($value, true), var_export($current, true))
                );
            }
        }

        return $this;
    }

    /**
     * Assert that a JSON array at the given path has the expected count.
     */
    public function assertJsonCount(int $expected, ?string $path = null): self
    {
        $data = $this->json();

        if ($path !== null) {
            $keys = explode('.', $path);
            foreach ($keys as $key) {
                if (!is_array($current ?? $data) || !array_key_exists($key, $current ?? $data)) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        sprintf('JSON path "%s" not found.', $path)
                    );
                }
                $current = ($current ?? $data)[$key];
            }
            $data = $current;
        }

        if (!is_array($data)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                'JSON value is not an array, cannot assert count.'
            );
        }

        $actual = count($data);
        if ($actual !== $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected JSON count %d, got %d.', $expected, $actual)
            );
        }

        return $this;
    }

    /**
     * Assert the JSON response equals the expected array.
     *
     * @param array<string, mixed> $expected
     */
    public function assertJsonExact(array $expected): self
    {
        $actual = $this->json();
        if ($actual != $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf("JSON response mismatch.\nExpected: %s\nActual: %s", json_encode($expected), json_encode($actual))
            );
        }
        return $this;
    }

    // ── Content Assertions ──────────────────────────────────────

    public function assertSee(string $value): self
    {
        $body = $this->body();
        if (!str_contains($body, $value)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Response body does not contain "%s".', $value)
            );
        }
        return $this;
    }

    public function assertDontSee(string $value): self
    {
        $body = $this->body();
        if (str_contains($body, $value)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Response body unexpectedly contains "%s".', $value)
            );
        }
        return $this;
    }

    public function assertSeeText(string $value): self
    {
        $body = strip_tags($this->body());
        if (!str_contains($body, $value)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Response text does not contain "%s".', $value)
            );
        }
        return $this;
    }

    // ── Cookie Assertions ───────────────────────────────────────

    public function assertCookie(string $name): self
    {
        $cookies = $this->response->getHeader('Set-Cookie');
        $found = false;
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie, $name . '=')) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Cookie "%s" not set in response.', $name)
            );
        }
        return $this;
    }

    public function assertCookieExpired(string $name): self
    {
        $cookies = $this->response->getHeader('Set-Cookie');
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie, $name . '=') && (
                str_contains(strtolower($cookie), 'expires=thu, 01-jan-1970') ||
                str_contains(strtolower($cookie), 'max-age=0') ||
                str_contains(strtolower($cookie), 'expires=0')
            )) {
                return $this;
            }
        }
        throw new \PHPUnit\Framework\AssertionFailedError(
            sprintf('Cookie "%s" is not expired.', $name)
        );
    }

    // ── Session Assertions ──────────────────────────────────────

    /**
     * Assert that the session has a key with an optional value.
     *
     * @param array<string, mixed>|null $sessionData The session data (from container session)
     */
    public function assertSessionHas(string $key, mixed $value = null, ?array $sessionData = null): self
    {
        if ($sessionData === null) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                'Session data not available — pass session data or use a session-aware test case.'
            );
        }

        if (!array_key_exists($key, $sessionData)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Session key "%s" not present.', $key)
            );
        }

        if ($value !== null && $sessionData[$key] !== $value) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Session key "%s" expected %s, got %s.', $key, var_export($value, true), var_export($sessionData[$key], true))
            );
        }

        return $this;
    }

    public function assertSessionHasErrors(array $expectedErrors, ?array $sessionData = null): self
    {
        if ($sessionData === null || !isset($sessionData['errors'])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                'Session errors not available.'
            );
        }

        /** @var array<string, string> $errors */
        $errors = $sessionData['errors'];
        foreach ($expectedErrors as $field) {
            if (!array_key_exists($field, $errors)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    sprintf('Session error for field "%s" not present.', $field)
                );
            }
        }

        return $this;
    }
}
