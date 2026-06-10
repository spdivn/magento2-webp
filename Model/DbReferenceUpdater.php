<?php
declare(strict_types=1);

namespace Spdivn\WebP\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * @copyright (c) 2026, Spdivn
 * @package Spdivn/WebP
 * @subpackage Model
 */
class DbReferenceUpdater
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {}

    /**
     * Updates database references when images are renamed from .jpg/.png to .webp
     *
     * @param array $conversions Array of ['old' => '/path/file.jpg', 'new' => '/path/file.webp']
     * @return int Total number of DB rows updated across all tables
     */
    public function update(array $conversions): int
    {
        $connection        = $this->resourceConnection->getConnection();
        $totalAffectedRows = 0;

        foreach ($conversions as $conversion) {
            $oldPath = $conversion['old'] ?? '';
            $newPath = $conversion['new'] ?? '';

            if (empty($oldPath) || empty($newPath)) {
                continue;
            }

            $oldFilename = basename($oldPath);
            $newFilename = basename($newPath);

            if ($oldFilename === $newFilename) {
                continue;
            }

            $escapedFilename = addcslashes($oldFilename, '%_\\');
            $likePattern     = "%{$escapedFilename}%";

            // CMS blocks
            $cmsBlockTable = $this->resourceConnection->getTableName('cms_block');
            $totalAffectedRows += $connection->query(
                "UPDATE `{$cmsBlockTable}` SET `content` = REPLACE(`content`, :old, :new) WHERE `content` LIKE :like",
                ['old' => $oldFilename, 'new' => $newFilename, 'like' => $likePattern]
            )->rowCount();

            // CMS pages
            $cmsPageTable = $this->resourceConnection->getTableName('cms_page');
            $totalAffectedRows += $connection->query(
                "UPDATE `{$cmsPageTable}` SET `content` = REPLACE(`content`, :old, :new) WHERE `content` LIKE :like",
                ['old' => $oldFilename, 'new' => $newFilename, 'like' => $likePattern]
            )->rowCount();

            // Category images (varchar EAV)
            $categoryVarcharTable = $this->resourceConnection->getTableName('catalog_category_entity_varchar');
            $totalAffectedRows += $connection->query(
                "UPDATE `{$categoryVarcharTable}` SET `value` = REPLACE(`value`, :old, :new) WHERE `value` LIKE :like",
                ['old' => $oldFilename, 'new' => $newFilename, 'like' => $likePattern]
            )->rowCount();
        }

        return $totalAffectedRows;
    }
}

