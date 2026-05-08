<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1776090012Create404Log extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776090012;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `scop_platform_redirecter_404` (
            `id` BINARY(16) NOT NULL,
            `url` VARCHAR(500) NOT NULL,
            `sales_channel_id` BINARY(16) NULL,
            `hit_count` INT NOT NULL DEFAULT 1,
            `last_hit_at` DATETIME(3) NOT NULL,
            `redirect_id` BINARY(16) NULL,
            `ignored` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq.url_saleschannel` (`url`, `sales_channel_id`),
            CONSTRAINT `fk.scop_redirecter_404.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `fk.scop_redirecter_404.redirect_id` FOREIGN KEY (`redirect_id`)
                REFERENCES `scop_platform_redirecter_redirect` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }
}
