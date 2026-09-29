<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit;

use MonkeysLegion\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for the TestResponse assertion wrapper.
 *
 * Uses a simple mock ResponseInterface to avoid depending on the full framework.
 */
final class TestResponseTest extends TestCase
{
    private function makeResponse(int $status = 200, string $body = '', array $headers = []): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('rewind')->willReturnCallback(fn() => null);
        $stream->method('__toString')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($stream);

        $response->method('hasHeader')->willReturnCallback(fn($name) => isset($headers[$name]));
        $response->method('getHeaderLine')->willReturnCallback(fn($name) => $headers[$name] ?? '');

        return $response;
    }

    // ── Status Assertions ───────────────────────────────────────

    #[Test]
    public function assert_status_passes_on_match(): void
    {
        $response = new TestResponse($this->makeResponse(200));

        self::assertSame($response, $response->assertStatus(200));
    }

    #[Test]
    public function assert_ok_passes_on_200(): void
    {
        $response = new TestResponse($this->makeResponse(200));

        self::assertSame($response, $response->assertOk());
    }

    #[Test]
    public function assert_created_passes_on_201(): void
    {
        $response = new TestResponse($this->makeResponse(201));

        self::assertSame($response, $response->assertCreated());
    }

    #[Test]
    public function assert_no_content_passes_on_204(): void
    {
        $response = new TestResponse($this->makeResponse(204));

        self::assertSame($response, $response->assertNoContent());
    }

    #[Test]
    public function assert_not_found_passes_on_404(): void
    {
        $response = new TestResponse($this->makeResponse(404));

        self::assertSame($response, $response->assertNotFound());
    }

    #[Test]
    public function assert_unauthorized_passes_on_401(): void
    {
        $response = new TestResponse($this->makeResponse(401));

        self::assertSame($response, $response->assertUnauthorized());
    }

    #[Test]
    public function assert_forbidden_passes_on_403(): void
    {
        $response = new TestResponse($this->makeResponse(403));

        self::assertSame($response, $response->assertForbidden());
    }

    #[Test]
    public function assert_unprocessable_passes_on_422(): void
    {
        $response = new TestResponse($this->makeResponse(422));

        self::assertSame($response, $response->assertUnprocessable());
    }

    #[Test]
    public function assert_server_error_passes_on_500(): void
    {
        $response = new TestResponse($this->makeResponse(500));

        self::assertSame($response, $response->assertServerError());
    }

    #[Test]
    public function assert_status_fails_on_mismatch(): void
    {
        $response = new TestResponse($this->makeResponse(404));

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $response->assertStatus(200);
    }

    // ── Redirect Assertions ─────────────────────────────────────

    #[Test]
    public function assert_redirect_passes_on_302(): void
    {
        $response = new TestResponse($this->makeResponse(302, '', ['Location' => '/login']));

        self::assertSame($response, $response->assertRedirect());
    }

    #[Test]
    public function assert_redirect_to_specific_path(): void
    {
        $response = new TestResponse($this->makeResponse(302, '', ['Location' => '/dashboard']));

        self::assertSame($response, $response->assertRedirect('/dashboard'));
    }

    #[Test]
    public function assert_redirect_fails_on_wrong_location(): void
    {
        $response = new TestResponse($this->makeResponse(302, '', ['Location' => '/login']));

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $response->assertRedirect('/dashboard');
    }

    // ── Header Assertions ───────────────────────────────────────

    #[Test]
    public function assert_header_present(): void
    {
        $response = new TestResponse($this->makeResponse(200, '', ['X-Custom' => 'value']));

        self::assertSame($response, $response->assertHeader('X-Custom'));
    }

    #[Test]
    public function assert_header_with_value(): void
    {
        $response = new TestResponse($this->makeResponse(200, '', ['X-Custom' => 'value']));

        self::assertSame($response, $response->assertHeader('X-Custom', 'value'));
    }

    #[Test]
    public function assert_header_missing_passes_when_absent(): void
    {
        $response = new TestResponse($this->makeResponse(200));

        self::assertSame($response, $response->assertHeaderMissing('X-Custom'));
    }

    // ── JSON Assertions ─────────────────────────────────────────

    #[Test]
    public function assert_json_with_valid_json(): void
    {
        $response = new TestResponse($this->makeResponse(
            200,
            '{"data": {"name": "test"}}',
            ['Content-Type' => 'application/json']
        ));

        self::assertSame($response, $response->assertJson());
    }

    #[Test]
    public function assert_json_structure(): void
    {
        $response = new TestResponse($this->makeResponse(
            200,
            '{"data": {"name": "test", "email": "test@test.com"}}',
            ['Content-Type' => 'application/json']
        ));

        self::assertSame($response, $response->assertJsonStructure([
            'data' => ['name', 'email'],
        ]));
    }

    #[Test]
    public function assert_json_path(): void
    {
        $response = new TestResponse($this->makeResponse(
            200,
            '{"data": {"attributes": {"name": "John"}}}',
            ['Content-Type' => 'application/json']
        ));

        self::assertSame($response, $response->assertJsonPath('data.attributes.name', 'John'));
    }

    #[Test]
    public function assert_json_count(): void
    {
        $response = new TestResponse($this->makeResponse(
            200,
            '{"data": [{"id": 1}, {"id": 2}, {"id": 3}]}',
            ['Content-Type' => 'application/json']
        ));

        self::assertSame($response, $response->assertJsonCount(3, 'data'));
    }

    #[Test]
    public function json_throws_on_non_json_content_type(): void
    {
        $response = new TestResponse($this->makeResponse(200, 'not json', ['Content-Type' => 'text/html']));

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $response->assertJson();
    }

    // ── Content Assertions ──────────────────────────────────────

    #[Test]
    public function assert_see_finds_string_in_body(): void
    {
        $response = new TestResponse($this->makeResponse(200, '<h1>Hello World</h1>'));

        self::assertSame($response, $response->assertSee('Hello'));
    }

    #[Test]
    public function assert_dont_see_when_string_absent(): void
    {
        $response = new TestResponse($this->makeResponse(200, '<h1>Hello</h1>'));

        self::assertSame($response, $response->assertDontSee('Goodbye'));
    }

    #[Test]
    public function assert_see_text_strips_html_tags(): void
    {
        $response = new TestResponse($this->makeResponse(200, '<script>alert("xss")</script><h1>Safe Text</h1>'));

        self::assertSame($response, $response->assertSeeText('Safe Text'));
    }
}
