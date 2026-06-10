<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the "Converter Driver" select field in admin config.
 */
/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Config
 */
class ConverterDriver implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'gd',      'label' => __('GD (built-in PHP extension)')],
            ['value' => 'imagick', 'label' => __('Imagick (requires imagick PHP extension)')],
        ];
    }
}

