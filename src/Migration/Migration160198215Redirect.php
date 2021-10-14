<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration160198215Redirect extends MigrationStep
{

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\Migration\MigrationStep::getCreationTimestamp()
     */
    public function getCreationTimestamp(): int
    {
        return 160198215;
    }

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\Migration\MigrationStep::update()
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `scop_platform_redirecter_redirect` (
        `id` BINARY(16) NOT NULL,
        `sourceURL` VARCHAR(255) NOT NULL,
        `targetURL` VARCHAR(255) NOT NULL,
        `httpCode` INT(3),
        `created_at` DATETIME(3) NOT NULL,
        `updated_at` DATETIME(3) NULL
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\Migration\MigrationStep::updateDestructive()
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
