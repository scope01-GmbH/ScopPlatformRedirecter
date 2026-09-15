<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1789473374AddTemporaryRedirectDates extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1789473374;
    }

    public function update(Connection $connection): void
    {
        $this->addColumnIfMissing($connection, 'active_from', 'target_sales_channel_id');
        $this->addColumnIfMissing($connection, 'active_until', 'active_from');
    }

    private function addColumnIfMissing(Connection $connection, string $column, string $after): void
    {
        $columns = $connection->fetchAllAssociative(
            \sprintf("SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE '%s'", $column)
        );

        if (\count($columns) === 0) {
            $connection->executeStatement(
                \sprintf('ALTER TABLE `scop_platform_redirecter_redirect` ADD `%s` DATETIME(3) NULL AFTER `%s`', $column, $after)
            );
        }
    }
}
