# MonKeysLegion Testing

Testing toolkit for the MonKeysLegion framework — HTTP testing DSL, database traits, fake subsystems, and snapshot assertions.

## Features

- **HTTP testing DSL** — `get()`, `post()`, `put()`, `delete()` with fluent assertions
- **TestResponse** — `assertStatus()`, `assertJson()`, `assertJsonPath()`, `assertHeader()`
- **Database traits** — `RefreshDatabase`, `DatabaseTransactions`, `InteractsWithDatabase`
- **Fake subsystems** — `QueueFake`, `MailFake`, `EventFake` with assertion methods
- **Snapshot testing** — `assertMatchesSnapshot()` for view/JSON/XML output
- **Fixture loader** — load test data from JSON/PHP fixture files
- **Pest adapter** — Pest PHP compatibility layer
- **Auth testing** — `actingAs()`, `InteractsWithAuth` trait

## Installation

```bash
composer require --dev monkeyscloud/monkeyslegion-testing
```

## Usage

```php
use MonkeysLegion\Testing\TestCase;
use MonkeysLegion\Testing\Concerns\RefreshDatabase;

final class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
        $response = $this->post('/api/users', [
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Bob');
    }
}
```

## License

MIT © MonKeysCloud
