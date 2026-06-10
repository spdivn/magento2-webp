<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Service;

use Spdivn\WebP\Model\Converter\ConverterInterface;
use Spdivn\WebP\Model\DbReferenceUpdater;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Service
 *
 * Core service for converting JPG/PNG images to WebP.
 *
 * Used by both the CLI command (ConvertToWebpCommand) and the scheduled cron job (ConvertToWebp).
 */
class ConvertToWebpService
{
    /**
     * @param ConverterInterface[] $converters  Keyed by driver name, e.g. ['gd' => ..., 'imagick' => ...]
     * @param DbReferenceUpdater   $dbReferenceUpdater
     */
    public function __construct(
        private readonly DbReferenceUpdater $dbReferenceUpdater,
        private readonly array $converters = []
    ) {}

    /**
     * Execute conversion on all JPG/PNG files found in $path.
     *
     * @param string $path          Absolute path to directory.
     * @param string $driverName    'gd' or 'imagick'.
     * @param int    $quality       WebP quality 0–100.
     * @param bool   $keepOriginals Keep original files after conversion.
     * @param bool   $recursive     Scan subdirectories recursively.
     * @param bool   $updateDb      Whether to update DB references after conversion.
     *
     * @return array{files: int, converted: int, failed: int, bytesSaved: int, dbUpdated: int, error: string|null}
     */
    /**
     * @param callable|null $progressCallback Optional callback called after each file is processed.
     *     Signature: function(int $processed, int $total): void
     */
    public function execute(
        string $path,
        string $driverName,
        int $quality,
        bool $keepOriginals,
        bool $recursive,
        bool $updateDb = true,
        ?callable $progressCallback = null
    ): array {
        $result = [
            'files'      => 0,
            'converted'  => 0,
            'failed'     => 0,
            'bytesSaved' => 0,
            'dbUpdated'  => 0,
            'error'      => null,
        ];

        if (!is_dir($path)) {
            $result['error'] = sprintf('Path "%s" does not exist or is not a directory.', $path);
            return $result;
        }

        if ($quality < 0 || $quality > 100) {
            $result['error'] = 'Quality must be between 0 and 100.';
            return $result;
        }

        if (!isset($this->converters[$driverName])) {
            $result['error'] = sprintf(
                'Unknown driver "%s". Available: %s.',
                $driverName,
                implode(', ', array_keys($this->converters))
            );
            return $result;
        }

        /** @var ConverterInterface $converter */
        $converter = $this->converters[$driverName];

        if (!$converter->isSupported()) {
            $result['error'] = sprintf('Driver "%s" is not supported on this server.', $driverName);
            return $result;
        }

        $files          = $this->findImageFiles($path, $recursive);
        $result['files'] = count($files);

        if (empty($files)) {
            return $result;
        }

        $conversions = [];

        $processed = 0;
        foreach ($files as $sourcePath) {
            $destPath     = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
            $originalSize = is_file($sourcePath) ? (int) filesize($sourcePath) : 0;

            if ($converter->convert($sourcePath, $destPath, $quality)) {
                $webpSize             = is_file($destPath) ? (int) filesize($destPath) : 0;
                $result['bytesSaved'] += max(0, $originalSize - $webpSize);
                $conversions[]         = ['old' => $sourcePath, 'new' => $destPath];

                if (!$keepOriginals) {
                    // Suppress errors intentionally — file deletion failure is non-fatal
                    // phpcs:ignore Generic.PHP.NoSilencedErrors
                    if (!unlink($sourcePath)) {
                        // If unlink fails we still count the conversion as successful
                    }
                }

                $result['converted']++;
            } else {
                $result['failed']++;
            }
            // Advance progress callback once per processed file
            $processed++;
            if (is_callable($progressCallback)) {
                // Call with number of processed files and total count
                $progressCallback($processed, $result['files']);
            }
        }

        if ($updateDb && !empty($conversions)) {
            $result['dbUpdated'] = $this->dbReferenceUpdater->update($conversions);
        }

        return $result;
    }

    /**
     * Return all .jpg/.jpeg/.png files in the given directory.
     *
     * @return string[]
     */
    public function findImageFiles(string $path, bool $recursive): array
    {
        $files    = [];
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            )
            : new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $realPath = $file->getRealPath();
                if ($realPath !== false) {
                    $files[] = $realPath;
                }
            }
        }

        return $files;
    }

    /**
     * Format bytes as human-readable string.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $exp   = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return sprintf('%.1f %s', $bytes / (1024 ** $exp), $units[$exp]);
    }
}



