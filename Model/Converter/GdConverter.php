<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Converter;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Converter
 */
class GdConverter implements ConverterInterface
{
    public function isSupported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromjpeg');
    }

    public function convert(string $source, string $destination, int $quality): bool
    {
        try {
            $imageType = $this->detectImageType($source);

            if ($imageType === IMAGETYPE_JPEG) {
                $image = imagecreatefromjpeg($source);
                if ($image === false) {
                    return false;
                }
            } elseif ($imageType === IMAGETYPE_PNG) {
                $image = imagecreatefrompng($source);
                if ($image === false) {
                    return false;
                }
                imagealphablending($image, false);
                imagesavealpha($image, true);
            } else {
                return false;
            }

            $result = imagewebp($image, $destination, $quality);
            imagedestroy($image);

            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function detectImageType(string $source): int|false
    {
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($source);
            if ($type !== false) {
                return $type;
            }
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => IMAGETYPE_JPEG,
            'png'         => IMAGETYPE_PNG,
            default       => false,
        };
    }
}

