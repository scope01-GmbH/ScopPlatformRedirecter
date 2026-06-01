<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1780060000RelaxTargetUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780060000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `scop_platform_redirecter_redirect`
            MODIFY `targetURL` VARCHAR(255) NULL DEFAULT '';
SQL;
        $connection->executeStatement($sql);
    }
}
