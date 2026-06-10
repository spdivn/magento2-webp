<?php
declare(strict_types=1);

namespace Spdivn\WebP\Cron;

use Spdivn\WebP\Logger\WebpConverterLogger;
use Spdivn\WebP\Model\Config\ImageConverter as ImageConverterConfig;
use Spdivn\WebP\Model\Service\ConvertToWebpService;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Cron
 *
 * Scheduled cron job: runs WebP image conversion based on admin configuration.
 *
 * Mirrors the behaviour of the spdivn:images:convert-to-webp CLI command,
 * reading all parameters from System > Config > Spdivn > WebP / Image Converter.
 */
class ConvertToWebp
{
    public function __construct(
        private readonly ImageConverterConfig $config,
        private readonly ConvertToWebpService $service,
        private readonly WebpConverterLogger  $logger
    ) {}

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            $this->logger->info('[WebpConverter] Cron is disabled — skipping.');
            return;
        }

        $path          = $this->config->getPath();
        $driver        = $this->config->getDriver();
        $quality       = $this->config->getQuality();
        $keepOriginals = $this->config->isKeepOriginals();
        $recursive     = $this->config->isRecursive();

        if ($path === '') {
            $this->logger->warning('[WebpConverter] No path configured. Cron aborted.');
            return;
        }

        $this->logger->info(sprintf(
            '[WebpConverter] Starting — path: %s, driver: %s, quality: %d, keepOriginals: %s, recursive: %s',
            $path,
            $driver,
            $quality,
            $keepOriginals ? 'yes' : 'no',
            $recursive ? 'yes' : 'no'
        ));

        try {
            $result = $this->service->execute($path, $driver, $quality, $keepOriginals, $recursive);

            if ($result['error'] !== null) {
                $this->logger->error('[WebpConverter] ' . $result['error']);
                return;
            }

            $this->logger->info(sprintf(
                '[WebpConverter] Done — files: %d, converted: %d, failed: %d, saved: %s, DB rows updated: %d',
                $result['files'],
                $result['converted'],
                $result['failed'],
                $this->service->formatBytes($result['bytesSaved']),
                $result['dbUpdated']
            ));

            if ($result['failed'] > 0) {
                $this->logger->warning(sprintf('[WebpConverter] %d file(s) failed to convert.', $result['failed']));
            }
        } catch (\Throwable $e) {
            $this->logger->critical(sprintf(
                '[WebpConverter] Unexpected error: %s — %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }
}


