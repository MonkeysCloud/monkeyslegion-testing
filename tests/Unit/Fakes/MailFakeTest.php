<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit\Fakes;

use MonkeysLegion\Mail\Message;
use MonkeysLegion\Testing\Fakes\MailFake;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MailFakeTest extends TestCase
{
    private MailFake $mail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mail = new MailFake();
        MailFake::setInstance($this->mail);
    }

    protected function tearDown(): void
    {
        MailFake::reset();
        parent::tearDown();
    }

    #[Test]
    public function send_records_message(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getTo')->willReturn('test@example.com');

        $this->mail->send($message);

        MailFake::assertSent();
        MailFake::assertSentCount(1);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_not_sent_when_nothing_sent(): void
    {
        MailFake::assertNotSent();
        self::assertTrue(true);
    }

    #[Test]
    public function assert_sent_to_specific_recipient(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getTo')->willReturn('admin@example.com');

        $this->mail->send($message);

        MailFake::assertSentTo('admin@example.com');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_sent_count(): void
    {
        $msg1 = $this->createMock(Message::class);
        $msg1->method('getTo')->willReturn('user1@test.com');
        $msg2 = $this->createMock(Message::class);
        $msg2->method('getTo')->willReturn('user2@test.com');

        $this->mail->send($msg1);
        $this->mail->send($msg2);

        MailFake::assertSentCount(2);
        self::assertTrue(true);
    }

    #[Test]
    public function assert_sent_with_callback_filter(): void
    {
        $msg = $this->createMock(Message::class);
        $msg->method('getTo')->willReturn('user@test.com');
        $msg->method('getSubject')->willReturn('Welcome');

        $this->mail->send($msg);

        MailFake::assertSent(fn($m) => $m->getSubject() === 'Welcome');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_nothing_sent(): void
    {
        MailFake::assertNothingSent();
        self::assertTrue(true);
    }

    #[Test]
    public function get_name_returns_fake(): void
    {
        self::assertSame('fake', $this->mail->getName());
    }
}
