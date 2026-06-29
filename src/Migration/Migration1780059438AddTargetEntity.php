<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1780059438AddTargetEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780059438;
    }

    public function update(Connection $connection): void
    {
        $typeColumns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'target_entity_type'"
        );

        if (\count($typeColumns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD `target_entity_type` VARCHAR(64) NULL AFTER `product_id`'
            );
        }

        $idColumns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'target_entity_id'"
        );

        if (\count($idColumns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD `target_entity_id` BINARY(16) NULL AFTER `target_entity_type`'
            );
        }
    }
}
