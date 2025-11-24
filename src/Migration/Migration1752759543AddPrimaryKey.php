<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1752759543AddPrimaryKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1752759543;
    }

    public function update(Connection $connection): void
    {
        $sql = "ALTER TABLE `scop_platform_redirecter_redirect` ADD PRIMARY KEY `id` (`id`);";
        $connection->executeStatement($sql);
    }
}
