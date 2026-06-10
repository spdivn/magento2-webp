<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model\Converter;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Converter
 */
interface ConverterInterface
{
    public function convert(string $source, string $destination, int $quality): bool;
    public function isSupported(): bool;
}

