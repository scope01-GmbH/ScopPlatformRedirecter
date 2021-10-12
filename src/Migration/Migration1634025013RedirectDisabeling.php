<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1634025013RedirectDisabeling extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1634025013;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `scop_platform_redirecter_redirect` ADD COLUMN `enabled` BOOLEAN DEFAULT true AFTER `httpCode`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
