<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\MediaStorage;

use Magento\MediaStorage\Model\File\Validator\Image;

/**
 * Extends the MIME-type image validator to accept WebP files.
 *
 * The parent validator keeps a private $imageMimeTypes map that does not
 * include image/webp. When the base result is false, we verify the file is
 * actually a valid WebP using getimagesize() (returns IMAGETYPE_WEBP = 18)
 * and return true if so.
 */
class ImageValidatorPlugin
{
    public function afterIsValid(
        Image $subject,
        bool $result,
        mixed $filePath
    ): bool {
        if ($result) {
            return true;
        }

        $imageInfo = @getimagesize((string) $filePath);

        return $imageInfo !== false && ($imageInfo[2] ?? 0) === IMAGETYPE_WEBP;
    }
}
