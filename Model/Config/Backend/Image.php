<?php

declare(strict_types=1);

namespace Spdivn\WebP\Model\Config\Backend;

class Image extends \Magento\Config\Model\Config\Backend\Image
{
    protected function _getAllowedExtensions(): array
    {
        return array_merge(parent::_getAllowedExtensions(), ['webp']);
    }
}
