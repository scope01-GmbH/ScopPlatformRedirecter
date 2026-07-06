<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1689242277TransferQueryParamter extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1689242277;
    }

    public function update(Connection $connection): void
    {
        $existing = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'queryParamsHandling'"
        );
        if (\count($existing) > 0) {
            return;
        }

        $old = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'ignoreQueryParams'"
        );
        if (\count($old) > 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` CHANGE `ignoreQueryParams` `queryParamsHandling` TINYINT DEFAULT 0'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
