<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Repairs installations where `scop_platform_redirecter_redirect` was created
 * without an explicit primary key (see Migration160198215Redirect). On MySQL
 * 8.0.30+ with `sql_generate_invisible_primary_key=ON` the server auto-adds a
 * Generated Invisible Primary Key column (`my_row_id`). That caused the earlier
 * Migration1752759543AddPrimaryKey to fail silently with "Multiple primary key
 * defined", leaving `id` without an index and later breaking the foreign key in
 * Migration1776090012Create404Log ("Missing index for constraint ...").
 *
 * This migration must run before Create404Log (timestamp 1776090012).
 */
class Migration1752759544FixRedirectPrimaryKey extends MigrationStep
{
    private const TABLE = 'scop_platform_redirecter_redirect';

    public function getCreationTimestamp(): int
    {
        return 1752759544;
    }

    public function update(Connection $connection): void
    {
        $primaryKeyColumns = $connection->fetchFirstColumn(
            'SELECT COLUMN_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND INDEX_NAME = \'PRIMARY\'',
            ['table' => self::TABLE]
        );

        // `id` is already the primary key -> nothing to do.
        if ($primaryKeyColumns === ['id']) {
            return;
        }

        $hasGeneratedInvisiblePk = \in_array('my_row_id', $primaryKeyColumns, true);

        if ($hasGeneratedInvisiblePk) {
            // Drop the auto-generated invisible PK column (this also drops the
            // PRIMARY KEY) and promote `id` to the real primary key.
            $connection->executeStatement(
                'ALTER TABLE `' . self::TABLE . '`
                 DROP COLUMN `my_row_id`,
                 ADD PRIMARY KEY (`id`)'
            );

            return;
        }

        if ($primaryKeyColumns !== []) {
            // Some other primary key exists; drop it before setting the correct one.
            $connection->executeStatement('ALTER TABLE `' . self::TABLE . '` DROP PRIMARY KEY');
        }

        $connection->executeStatement('ALTER TABLE `' . self::TABLE . '` ADD PRIMARY KEY (`id`)');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
