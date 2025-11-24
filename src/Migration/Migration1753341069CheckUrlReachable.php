<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1753341069CheckUrlReachable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1753341069;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `scop_platform_redirecter_redirect` ADD `brokenRedirect` tinyint(1) NULL AFTER `salesChannelId`;
SQL;
        $connection->executeStatement($sql);
    }
}
