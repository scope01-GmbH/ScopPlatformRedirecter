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
