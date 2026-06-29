<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1753955200AddProductId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1753955200;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'product_id'"
        );

        if (\count($columns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD `product_id` BINARY(16) NULL AFTER `brokenRedirect`'
            );
        }
    }
}
