<?php

declare(strict_types=1);

namespace Spdivn\WebP\Model\Config\Backend\Image;

class Favicon extends \Magento\Config\Model\Config\Backend\Image\Favicon
{
    protected function _getAllowedExtensions(): array
    {
        return array_merge(parent::_getAllowedExtensions(), ['webp']);
    }
}
