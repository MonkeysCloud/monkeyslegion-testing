<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing\Fixtures;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Load test fixtures from JSON, SQL, or CSV files.
 *
 * Usage:
 *   $data = FixtureLoader::load('users.json');        // → array
 *   FixtureLoader::loadSql('seed.sql', $connection);   // → execute SQL
 *   $data = FixtureLoader::loadCsv('data.csv');        // → array<array>
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class FixtureLoader
{
    private static string $basePath = '';

    /**
     * Set the base path for fixture files.
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    /**
     * Load a JSON fixture file and return it as an array.
     *
     * @param string $name Fixture name (e.g., 'users.json' or 'users')
     * @return array<string, mixed>|list<mixed>
     */
    public static function load(string $name): array
    {
        $filepath = self::resolvePath($name, 'json');
        $content  = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException("Fixture file not found: {$filepath}");
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Load and execute a SQL fixture file.
     *
     * @param string $name Fixture name (e.g., 'seed.sql')
     * @param object $connection ConnectionInterface instance
     */
    public static function loadSql(string $name, object $connection): void
    {
        $filepath = self::resolvePath($name, 'sql');
        $content  = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException("Fixture file not found: {$filepath}");
        }

        // Split by semicolons (simple split — doesn't handle edge cases like
        // semicolons inside string literals, but sufficient for test fixtures)
        $statements = array_filter(
            array_map('trim', explode(';', $content)),
            fn($s) => $s !== ''
        );

        foreach ($statements as $sql) {
            $connection->execute($sql);
        }
    }

    /**
     * Load a CSV fixture file and return it as an array of associative arrays.
     *
     * @param string $name Fixture name (e.g., 'data.csv')
     * @return list<array<string, string>>
     */
    public static function loadCsv(string $name): array
    {
        $filepath = self::resolvePath($name, 'csv');
        $content  = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException("Fixture file not found: {$filepath}");
        }

        $lines  = str_getcsv($content, "\n");
        if ($lines === []) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $rows    = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) {
                continue; // Skip malformed rows
            }
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    /**
     * Load a fixture file as raw string content.
     */
    public static function loadRaw(string $name): string
    {
        $filepath = self::resolvePath($name, '');
        $content  = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException("Fixture file not found: {$filepath}");
        }
        return $content;
    }

    /**
     * Resolve a fixture path, adding extension if missing.
     */
    private static function resolvePath(string $name, string $extension): string
    {
        // If the name already has an extension, use it as-is
        if (pathinfo($name, PATHINFO_EXTENSION) !== '') {
            $path = self::getBasePath() . '/' . $name;
        } else {
            $path = self::getBasePath() . '/' . $name . ($extension !== '' ? '.' . $extension : '');
        }

        return $path;
    }

    /**
     * Get the base path for fixtures.
     */
    private static function getBasePath(): string
    {
        if (self::$basePath !== '') {
            return self::$basePath;
        }

        return defined('ML_BASE_PATH')
            ? ML_BASE_PATH . '/tests/Fixtures'
            : sys_get_temp_dir() . '/ml-fixtures';
    }
}
