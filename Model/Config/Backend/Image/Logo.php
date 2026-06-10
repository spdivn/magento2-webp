<?php

declare(strict_types=1);

namespace Spdivn\WebP\Model\Config\Backend\Image;

class Logo extends \Magento\Config\Model\Config\Backend\Image\Logo
{
    protected function _getAllowedExtensions(): array
    {
        return array_merge(parent::_getAllowedExtensions(), ['webp']);
    }
}
