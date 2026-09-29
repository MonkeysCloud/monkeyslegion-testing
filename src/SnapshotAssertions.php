<?php
declare(strict_types=1);

namespace MonkeysLegion\Testing;

/**
 * MonKeysLegion Framework — Testing Package
 *
 * Snapshot testing — compare output against stored golden files.
 *
 * Snapshots are stored in tests/__snapshots__/ as .snap files.
 * Run with --update-snapshots to regenerate.
 *
 * Usage:
 *   $this->assertMatchesSnapshot($data);
 *   $this->assertMatchesJsonSnapshot($jsonData);
 *   $this->assertMatchesHtmlSnapshot($html);
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
trait SnapshotAssertions
{
    private ?string $snapshotDir = null;

    /**
     * Assert that the given data matches a stored snapshot.
     *
     * @param mixed $data Data to snapshot (will be var_export'd)
     * @param string $name Optional snapshot name (defaults to test method name)
     */
    protected function assertMatchesSnapshot(mixed $data, string $name = ''): void
    {
        $actual   = var_export($data, true);
        $filepath = $this->getSnapshotPath($name);

        if ($this->shouldUpdateSnapshots()) {
            $this->writeSnapshot($filepath, $actual);
            return;
        }

        if (!file_exists($filepath)) {
            $this->writeSnapshot($filepath, $actual);
            return;
        }

        $expected = file_get_contents($filepath);
        if ($expected !== $actual) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    "Snapshot mismatch for %s.\nExpected:\n%s\nActual:\n%s\n\nRun with --update-snapshots to regenerate.",
                    basename($filepath),
                    $expected,
                    $actual
                )
            );
        }
    }

    /**
     * Assert that JSON data matches a stored snapshot.
     *
     * @param mixed $data Data to snapshot (will be json_encode'd)
     * @param string $name Optional snapshot name
     */
    protected function assertMatchesJsonSnapshot(mixed $data, string $name = ''): void
    {
        $actual   = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $filepath = $this->getSnapshotPath($name, 'json');

        if ($this->shouldUpdateSnapshots() || !file_exists($filepath)) {
            $this->writeSnapshot($filepath, $actual);
            return;
        }

        $expected = file_get_contents($filepath);
        if ($expected !== $actual) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    "JSON snapshot mismatch for %s.\nExpected:\n%s\nActual:\n%s\n\nRun with --update-snapshots to regenerate.",
                    basename($filepath),
                    $expected,
                    $actual
                )
            );
        }
    }

    /**
     * Assert that HTML matches a stored snapshot.
     */
    protected function assertMatchesHtmlSnapshot(string $html, string $name = ''): void
    {
        $actual   = $html;
        $filepath = $this->getSnapshotPath($name, 'html');

        if ($this->shouldUpdateSnapshots() || !file_exists($filepath)) {
            $this->writeSnapshot($filepath, $actual);
            return;
        }

        $expected = file_get_contents($filepath);
        if ($expected !== $actual) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    "HTML snapshot mismatch for %s.\nRun with --update-snapshots to regenerate.",
                    basename($filepath)
                )
            );
        }
    }

    /**
     * Get the snapshot file path.
     */
    private function getSnapshotPath(string $name, string $extension = 'snap'): string
    {
        $dir = $this->getSnapshotDir();
        if ($name === '') {
            $name = $this->getTestName();
        }
        return $dir . '/' . $this->sanitizeName($name) . '.' . $extension;
    }

    /**
     * Get the snapshot directory, creating it if needed.
     */
    private function getSnapshotDir(): string
    {
        if ($this->snapshotDir !== null) {
            return $this->snapshotDir;
        }

        $dir = defined('ML_BASE_PATH')
            ? ML_BASE_PATH . '/tests/__snapshots__'
            : sys_get_temp_dir() . '/ml-snapshots';

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $this->snapshotDir = $dir;
        return $dir;
    }

    /**
     * Set a custom snapshot directory (for testing).
     */
    protected function setSnapshotDir(string $dir): void
    {
        $this->snapshotDir = $dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Check if the --update-snapshots flag is set.
     */
    private function shouldUpdateSnapshots(): bool
    {
        return in_array('--update-snapshots', $_SERVER['argv'] ?? [], true);
    }

    /**
     * Get the current test method name.
     */
    private function getTestName(): string
    {
        return $this->name() ?? 'unknown_test';
    }

    /**
     * Sanitize a name for use as a filename.
     */
    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) ?? $name;
    }

    /**
     * Write a snapshot file.
     */
    private function writeSnapshot(string $filepath, string $content): void
    {
        file_put_contents($filepath, $content);
    }
}
