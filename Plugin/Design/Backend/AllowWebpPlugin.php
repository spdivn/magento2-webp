<?php

declare(strict_types=1);

namespace Spdivn\WebP\Plugin\Design\Backend;

/**
 * Appends 'webp' to the allowed extensions list for design config backend models
 * (Magento\Theme\Model\Design\Backend\{Image,Favicon,Logo}).
 *
 * These classes have a public getAllowedExtensions() method called both during
 * file upload (FileProcessor::save()) and during config save (beforeSave()).
 * The base implementations do not include 'webp', causing uploads to fail.
 */
class AllowWebpPlugin
{
    public function afterGetAllowedExtensions($subject, array $result): array
    {
        if (!in_array('webp', $result, true)) {
            $result[] = 'webp';
        }

        return $result;
    }
}
