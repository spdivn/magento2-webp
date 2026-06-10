<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Reads all Image Converter admin configuration values.
 *
 * Config paths: spdivn_webp/general/* and spdivn_webp/conversion/*
 */
/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Config
 */
class ImageConverter
{
    public const XML_PATH_ENABLED         = 'spdivn_webp/general/active';
    public const XML_PATH_CRON_EXPRESSION = 'spdivn_webp/general/cron_expression';
    public const XML_PATH_PATH            = 'spdivn_webp/conversion/path';
    public const XML_PATH_DRIVER          = 'spdivn_webp/conversion/driver';
    public const XML_PATH_QUALITY         = 'spdivn_webp/conversion/quality';
    public const XML_PATH_KEEP_ORIGINALS  = 'spdivn_webp/conversion/keep_originals';
    public const XML_PATH_RECURSIVE       = 'spdivn_webp/conversion/recursive';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DirectoryList $directoryList
    ) {}

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Resolve path: if it is absolute leave it as-is, otherwise resolve relative to Magento root (BP).
     */
    public function getPath(): string
    {
        $configured = trim((string) $this->scopeConfig->getValue(self::XML_PATH_PATH));

        if ($configured === '') {
            return '';
        }

        // Absolute path: starts with / or a Windows drive letter
        if (str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $configured)) {
            return rtrim($configured, '/\\');
        }

        // Relative path: resolve against Magento root
        return rtrim($this->directoryList->getRoot() . '/' . ltrim($configured, '/'), '/');
    }

    public function getDriver(): string
    {
        return (string) ($this->scopeConfig->getValue(self::XML_PATH_DRIVER) ?? 'gd');
    }

    public function getQuality(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_QUALITY) ?? 80);
    }

    public function isKeepOriginals(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_KEEP_ORIGINALS);
    }

    public function isRecursive(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_RECURSIVE);
    }
}

