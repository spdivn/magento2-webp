<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\FileUploader;

/**
 * Allows .webp through the extension-whitelist check on both
 * Magento\Framework\File\Uploader and
 * Magento\MediaStorage\Model\File\Uploader (which overrides the method).
 *
 * The check returns true when the extension is already allowed, so we only
 * intervene when the original result is false and the extension is webp.
 */
class AllowWebpExtensionPlugin
{
    public function afterCheckAllowedExtension(
        $subject,
        bool $result,
        string $extension
    ): bool {
        return $result ?: strtolower($extension) === 'webp';
    }
}
