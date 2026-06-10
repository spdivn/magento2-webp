<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\FileUploader;

use Magento\Framework\File\Uploader;

/**
 * Adds image/webp to any checkMimeType() call that validates image MIME types.
 *
 * Some controllers (e.g. Magento\PageBuilder\Controller\Adminhtml\ContentType\Image\Upload)
 * call checkMimeType() with a hardcoded list that excludes image/webp. This plugin
 * detects lists that already contain at least one image/* MIME type and injects
 * image/webp, so WebP files pass the check without requiring vendor edits.
 */
class CheckMimeTypePlugin
{
    public function aroundCheckMimeType(
        Uploader $subject,
        callable $proceed,
        array $validTypes = []
    ): bool {
        if (!empty($validTypes) && $this->isImageMimeTypeList($validTypes)) {
            $validTypes[] = 'image/webp';
        }

        return $proceed($validTypes);
    }

    private function isImageMimeTypeList(array $types): bool
    {
        foreach ($types as $type) {
            if (str_starts_with((string) $type, 'image/')) {
                return true;
            }
        }

        return false;
    }
}
