<?php
namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration160198215Redirect extends MigrationStep
{

    public function getCreationTimestamp(): int
    {
        return 160198215;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `Redirect` (
        `id` BINARY(16) NOT NULL,
        `sourceURL` VARCHAR(255) NOT NULL,
        `targetURL` VARCHAR(255) NOT NULL,
        `httpCode` INT(3)
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {}
}