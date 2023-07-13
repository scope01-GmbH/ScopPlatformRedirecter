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
        $sql = <<<SQL
        ALTER TABLE `scop_platform_redirecter_redirect` CHANGE `ignoreQueryParams` `queryParamsHandling` TINYINT DEFAULT 0;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
