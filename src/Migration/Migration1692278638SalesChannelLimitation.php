<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1692278638SalesChannelLimitation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692278638;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'salesChannelId'"
        );
        if (\count($columns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD COLUMN `salesChannelId` BINARY(16) NULL DEFAULT NULL AFTER `queryParamsHandling`'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
