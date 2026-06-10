<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\Storage;

use Magento\Cms\Model\Wysiwyg\Images\Storage;

/**
 * Prevents thumbnail creation failures from crashing WebP image uploads.
 *
 * resizeFile() is called after the file is already saved. When PHP GD lacks
 * WebP support, open() throws an exception that propagates up to the upload
 * controller's generic Exception handler → "Could not upload image." even
 * though the file was successfully saved.
 *
 * For WebP files, we catch any exception in resizeFile() and return false
 * (no thumbnail) so the upload completes successfully.
 */
class ResizeFilePlugin
{
    public function aroundResizeFile(
        Storage $subject,
        callable $proceed,
        string $source,
        bool $keepRatio = true
    ): bool|string {
        if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'webp') {
            return $proceed($source, $keepRatio);
        }

        try {
            return $proceed($source, $keepRatio);
        } catch (\Exception $e) {
            return false;
        }
    }
}
