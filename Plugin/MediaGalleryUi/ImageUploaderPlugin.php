<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\MediaGalleryUi;

use Magento\MediaGalleryUi\Ui\Component\ImageUploader;

/**
 * Adds WebP to the Media Gallery UI uploader's client-side config.
 *
 * The component uses private constants for ACCEPT_FILE_TYPES and
 * ALLOWED_EXTENSIONS and sets them in prepare(). After prepare() runs,
 * we update the stored config data to include webp.
 */
class ImageUploaderPlugin
{
    public function afterPrepare(ImageUploader $subject): void
    {
        $config = (array) $subject->getData('config');

        if (isset($config['allowedExtensions'])) {
            $config['allowedExtensions'] = rtrim($config['allowedExtensions']) . ' webp';
        }

        if (isset($config['acceptFileTypes'])) {
            $config['acceptFileTypes'] = '/(\.|\/)(gif|jpe?g|png|webp)$/i';
        }

        $subject->setData('config', $config);
    }
}
