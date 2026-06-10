<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Converter;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Converter
 */
class ImagickConverter implements ConverterInterface
{
    public function isSupported(): bool
    {
        return extension_loaded('imagick') && class_exists(\Imagick::class);
    }

    public function convert(string $source, string $destination, int $quality): bool
    {
        try {
            $imagick = new \Imagick($source);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($quality);
            $imagick->writeImage($destination);
            $imagick->destroy();

            return true;
        } catch (\ImagickException|\Exception $e) {
            return false;
        }
    }
}

