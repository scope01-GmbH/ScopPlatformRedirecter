<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1778889600AddRefererTo404Log extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1778889600;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_404` LIKE 'referers'"
        );

        if (\count($columns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_404` ADD COLUMN `referers` JSON NULL AFTER `last_hit_at`'
            );
        }
    }
}
