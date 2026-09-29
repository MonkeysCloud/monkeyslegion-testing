<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Fakes;

use MonkeysLegion\Mail\Message;
use MonkeysLegion\Mail\TransportInterface;
use PHPUnit\Framework\AssertionFailedError;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * In-memory fake mail transport that records sent messages for assertion.
 *
 * Usage:
 *   $this->fakeMail();
 *   $mailer->send($message);
 *   MailFake::assertSent(WelcomeMail::class);
 *   MailFake::assertSentTo('user@example.com', WelcomeMail::class);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class MailFake implements TransportInterface
{
    /** @var list<Message> */
    private array $sent = [];

    /** @var list<string> */
    private array $recipients = [];

    public function send(Message $m): void
    {
        $this->sent[] = $m;

        // Extract recipients (getTo() returns a string)
        $to = $m->getTo();
        if (is_string($to) && $to !== '') {
            $this->recipients[] = $to;
        }
    }

    public function getName(): string
    {
        return 'fake';
    }

    /**
     * Get all sent messages.
     *
     * @return list<Message>
     */
    public function getSent(): array
    {
        return $this->sent;
    }

    /**
     * Get all recipient email addresses.
     *
     * @return list<string>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    // ── Assertions ──────────────────────────────────────────────

    /**
     * Assert that at least one message was sent.
     *
     * @param callable|null $callback Optional filter: fn(Message $m): bool
     */
    public static function assertSent(?callable $callback = null): void
    {
        $instance = self::instance();
        $messages = $instance->sent;

        if ($callback !== null) {
            $messages = array_filter($messages, $callback);
        }

        if ($messages === []) {
            throw new AssertionFailedError('Failed asserting that a message was sent.');
        }
    }

    /**
     * Assert that no message was sent.
     */
    public static function assertNotSent(): void
    {
        $instance = self::instance();
        if ($instance->sent !== []) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that no messages were sent. Got %d.', count($instance->sent))
            );
        }
    }

    /**
     * Assert that a message was sent to the given recipient.
     */
    public static function assertSentTo(string $email, ?callable $callback = null): void
    {
        $instance = self::instance();

        if (!in_array($email, $instance->recipients, true)) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that a message was sent to "%s".', $email)
            );
        }

        if ($callback !== null) {
            $matching = array_filter($instance->sent, $callback);
            if ($matching === []) {
                throw new AssertionFailedError(
                    sprintf('Failed asserting that a message matching the callback was sent to "%s".', $email)
                );
            }
        }
    }

    /**
     * Assert the total number of messages sent.
     */
    public static function assertSentCount(int $expected): void
    {
        $instance = self::instance();
        $actual = count($instance->sent);

        if ($actual !== $expected) {
            throw new AssertionFailedError(
                sprintf('Failed asserting that %d message(s) were sent. Got %d.', $expected, $actual)
            );
        }
    }

    /**
     * Assert nothing was sent.
     */
    public static function assertNothingSent(): void
    {
        self::assertNotSent();
    }

    // ── Internal helpers ────────────────────────────────────────

    private static ?MailFake $instance = null;

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('MailFake not initialized. Call fakeMail() first.');
        }
        return self::$instance;
    }
}
