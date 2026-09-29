<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Tests\Unit\Fixtures;

use MonkeysLegion\Testing\Fixtures\FixtureLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FixtureLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ml-fixture-test-' . uniqid();
        @mkdir($this->tempDir, 0755, true);
        FixtureLoader::setBasePath($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function load_json_fixture(): void
    {
        $filepath = $this->tempDir . '/users.json';
        file_put_contents($filepath, json_encode([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]));

        $data = FixtureLoader::load('users.json');

        self::assertCount(2, $data);
        self::assertSame('John', $data[0]['name']);
        self::assertSame('Jane', $data[1]['name']);
    }

    #[Test]
    public function load_json_fixture_without_extension(): void
    {
        $filepath = $this->tempDir . '/config.json';
        file_put_contents($filepath, json_encode(['key' => 'value']));

        $data = FixtureLoader::load('config');

        self::assertSame(['key' => 'value'], $data);
    }

    #[Test]
    public function load_csv_fixture(): void
    {
        $filepath = $this->tempDir . '/data.csv';
        file_put_contents($filepath, "id,name,age\n1,John,30\n2,Jane,25\n");

        $data = FixtureLoader::loadCsv('data.csv');

        self::assertCount(2, $data);
        self::assertSame(['id' => '1', 'name' => 'John', 'age' => '30'], $data[0]);
        self::assertSame(['id' => '2', 'name' => 'Jane', 'age' => '25'], $data[1]);
    }

    #[Test]
    public function load_csv_fixture_without_extension(): void
    {
        $filepath = $this->tempDir . '/records.csv';
        file_put_contents($filepath, "a,b\n1,2\n");

        $data = FixtureLoader::loadCsv('records');

        self::assertCount(1, $data);
        self::assertSame(['a' => '1', 'b' => '2'], $data[0]);
    }

    #[Test]
    public function load_raw_fixture(): void
    {
        $filepath = $this->tempDir . '/raw.txt';
        file_put_contents($filepath, 'raw content here');

        $content = FixtureLoader::loadRaw('raw.txt');

        self::assertSame('raw content here', $content);
    }

    #[Test]
    public function load_sql_fixture_executes_statements(): void
    {
        $filepath = $this->tempDir . '/seed.sql';
        file_put_contents($filepath, "INSERT INTO test VALUES (1);\nINSERT INTO test VALUES (2);");

        // Use an anonymous class that has an execute() method
        $connection = new class {
            public int $callCount = 0;
            public function execute(string $sql, array $params = []): int
            {
                $this->callCount++;
                return 1;
            }
        };

        FixtureLoader::loadSql('seed.sql', $connection);

        self::assertSame(2, $connection->callCount);
    }

    #[Test]
    public function load_throws_on_missing_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fixture file not found');

        FixtureLoader::load('nonexistent.json');
    }
}
