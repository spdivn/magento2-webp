<?php

declare(strict_types=1);

namespace Spdivn\WebP\Model\Adapter;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Phrase;

/**
 * Extends Magento's GD2 image adapter to support WebP images.
 *
 * The parent class has a private static $_callbacks map that only covers
 * GIF, JPEG, PNG, XBM, WBMP. WebP requires PHP 7.0+ GD functions:
 * imagecreatefromwebp() and imagewebp(). Since $_callbacks and
 * _getCallback() are private, we intercept open(), save(), and getImage()
 * to handle IMAGETYPE_WEBP before delegating non-WebP cases to the parent.
 */
class Gd2 extends \Magento\Framework\Image\Adapter\Gd2
{
    public function open($filename): void
    {
        // Detect type via actual file content, not extension
        $imageInfo = @getimagesize($filename) ?: [];

        if (($imageInfo[2] ?? 0) !== IMAGETYPE_WEBP) {
            parent::open($filename);
            return;
        }

        $this->openWebp($filename);
    }

    public function save($destination = null, $newName = null): void
    {
        if ($this->_fileType !== IMAGETYPE_WEBP) {
            parent::save($destination, $newName);
            return;
        }

        $fileName = $this->_prepareDestination($destination, $newName);
        imagewebp($this->_imageHandler, $fileName, $this->quality());
    }

    public function getImage(): string
    {
        if ($this->_fileType !== IMAGETYPE_WEBP) {
            return parent::getImage();
        }

        ob_start();
        imagewebp($this->_imageHandler);
        return (string) ob_get_clean();
    }

    /**
     * Open a WebP file, mirroring parent::open() but using imagecreatefromwebp().
     *
     * Cannot call parent::open() for WebP: it internally calls the private
     * _getCallback('create') which throws for IMAGETYPE_WEBP (not in callbacks map).
     * imageDestroy() is also private in parent — we replicate its behaviour inline.
     */
    private function openWebp(string $filename): void
    {
        if (!function_exists('imagecreatefromwebp')) {
            throw new \InvalidArgumentException('Unsupported image format.');
        }

        if (!file_exists($filename)) {
            throw new FileSystemException(new Phrase('File "%1" does not exist.', [$filename]));
        }
        if (filesize($filename) === 0) {
            throw new \InvalidArgumentException('Wrong file');
        }

        $this->_fileName = $filename;
        $this->_reset();
        $this->getMimeType();
        $this->_getFileAttributes();

        if ($this->_isMemoryLimitReached()) {
            throw new \OverflowException('Memory limit has been reached.');
        }

        // Replicate private imageDestroy() from parent
        if ($this->_imageHandler !== null) {
            imagedestroy($this->_imageHandler);
            $this->_imageHandler = null;
        }

        $handler = imagecreatefromwebp($filename);
        if ($handler === false) {
            throw new \InvalidArgumentException(
                sprintf('Could not open WebP image. File: %s', $filename)
            );
        }

        $this->_imageHandler = $handler;
    }
}
