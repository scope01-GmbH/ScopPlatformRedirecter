<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1663601206IgnoreQueryParameter extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1663601206;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'ignoreQueryParams'"
        );
        if (\count($columns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD COLUMN `ignoreQueryParams` BOOLEAN DEFAULT false AFTER `enabled`'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
