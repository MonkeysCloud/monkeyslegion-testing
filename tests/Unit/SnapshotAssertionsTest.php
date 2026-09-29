<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit;

use MonkeysLegion\Testing\SnapshotAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SnapshotAssertionsTest extends TestCase
{
    use SnapshotAssertions;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ml-snapshot-test-' . uniqid();
        @mkdir($this->tempDir, 0755, true);
        $this->setSnapshotDir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp snapshots
        $files = glob($this->tempDir . '/*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function assert_matches_snapshot_creates_file_on_first_run(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        $this->assertMatchesSnapshot($data, 'test_snapshot');

        $snapFile = $this->tempDir . '/test_snapshot.snap';
        self::assertFileExists($snapFile);
        self::assertStringContainsString("'name' => 'John'", file_get_contents($snapFile));
    }

    #[Test]
    public function assert_matches_snapshot_passes_on_identical_data(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        // First run creates the snapshot
        $this->assertMatchesSnapshot($data, 'identical_snapshot');

        // Second run with identical data should pass
        $this->assertMatchesSnapshot($data, 'identical_snapshot');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_matches_json_snapshot_creates_json_file(): void
    {
        $data = ['name' => 'John', 'items' => [1, 2, 3]];

        $this->assertMatchesJsonSnapshot($data, 'json_snapshot');

        $snapFile = $this->tempDir . '/json_snapshot.json';
        self::assertFileExists($snapFile);
        self::assertJson(file_get_contents($snapFile));
    }

    #[Test]
    public function assert_matches_json_snapshot_passes_on_identical_data(): void
    {
        $data = ['name' => 'John', 'items' => [1, 2, 3]];

        $this->assertMatchesJsonSnapshot($data, 'json_identical');

        // Second run should pass
        $this->assertMatchesJsonSnapshot($data, 'json_identical');
        self::assertTrue(true);
    }

    #[Test]
    public function assert_matches_html_snapshot_creates_html_file(): void
    {
        $html = '<h1>Hello World</h1>';

        $this->assertMatchesHtmlSnapshot($html, 'html_snapshot');

        $snapFile = $this->tempDir . '/html_snapshot.html';
        self::assertFileExists($snapFile);
        self::assertStringContainsString('Hello World', file_get_contents($snapFile));
    }

    #[Test]
    public function assert_matches_html_snapshot_passes_on_identical_data(): void
    {
        $html = '<h1>Hello</h1>';

        $this->assertMatchesHtmlSnapshot($html, 'html_identical');

        // Second run should pass
        $this->assertMatchesHtmlSnapshot($html, 'html_identical');
        self::assertTrue(true);
    }

    #[Test]
    public function snapshot_uses_test_method_name_when_no_name_given(): void
    {
        $this->assertMatchesSnapshot(['test' => true]);

        // The snapshot file should be named after the test method
        $expectedName = $this->name();
        $snapFile = $this->tempDir . '/' . $expectedName . '.snap';
        self::assertFileExists($snapFile);
    }
}
