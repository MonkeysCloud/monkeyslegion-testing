<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Concerns;

use MonkeysLegion\Auth\Contract\AuthenticatableInterface;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Authentication test helpers.
 *
 * Usage:
 *   $this->actingAs($user)->get('/dashboard')->assertOk();
 *   $this->actingAs($admin, 'api')->get('/api/admin')->assertOk();
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait InteractsWithAuth
{
    /**
     * Set the authenticated user for subsequent requests.
     *
     * @param AuthenticatableInterface $user
     * @param string|null $guard Guard name (e.g., 'web', 'api')
     */
    protected function actingAs(AuthenticatableInterface $user, ?string $guard = null): void
    {
        $this->client = $this->client->actingAs($user, $guard);
    }

    /**
     * Create a test user and authenticate as them.
     *
     * @param array<string, mixed> $attributes
     */
    protected function actingAsUser(array $attributes = []): AuthenticatableInterface
    {
        $user = $this->createTestUser($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create a simple test user.
     *
     * Override in your test case for custom user creation logic.
     *
     * @param array<string, mixed> $attributes
     */
    protected function createTestUser(array $attributes = []): AuthenticatableInterface
    {
        // Subclasses should override this to create real user entities.
        // This default returns a simple stub.
        return new class($attributes) implements AuthenticatableInterface {
            private array $attrs;

            public function __construct(array $attrs)
            {
                $this->attrs = array_merge([
                    'id' => 1,
                    'password' => 'hashed',
                    'token_version' => 1,
                    'remember_token' => null,
                ], $attrs);
            }

            public function getAuthIdentifier(): int|string
            {
                return $this->attrs['id'];
            }

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthPassword(): string
            {
                return $this->attrs['password'];
            }

            public function getTokenVersion(): int
            {
                return $this->attrs['token_version'];
            }

            public function getRememberToken(): ?string
            {
                return $this->attrs['remember_token'];
            }

            public function setRememberToken(?string $token): void
            {
                $this->attrs['remember_token'] = $token;
            }
        };
    }
}
