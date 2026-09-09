<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1780061000AddTargetLanguage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780061000;
    }

    public function update(Connection $connection): void
    {
        $languageColumns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'target_language_id'"
        );

        if (\count($languageColumns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD `target_language_id` BINARY(16) NULL AFTER `target_entity_id`'
            );
        }
    }
}
