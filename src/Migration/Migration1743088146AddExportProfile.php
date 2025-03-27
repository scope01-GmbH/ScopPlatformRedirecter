<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class Migration1743088146AddExportProfile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1743088146;
    }

    public function update(Connection $connection): void
    {
        $importExportId = Uuid::randomHex();

        $enGbLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deDeLangId = $this->getLanguageIdByLocale($connection, 'de-DE');
        $defaultLangId = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);

        $sql = <<<SQL
INSERT INTO `import_export_profile` (`id`, `system_default`, `source_entity`, `file_type`, `delimiter`, `enclosure`, `type`, `mapping`, `created_at`, `technical_name`)
VALUES (:id, '1', 'scop_platform_redirecter_redirect', 'text/csv', ';', '\"', 'import-export', '[{\"key\":\"id\",\"mappedKey\":\"id\",\"position\":0},{\"key\":\"sourceURL\",\"mappedKey\":\"source_url\",\"position\":1},{\"key\":\"targetURL\",\"mappedKey\":\"target_url\",\"position\":2},{\"key\":\"httpCode\",\"mappedKey\":\"http_code\",\"position\":3},{\"key\":\"enabled\",\"mappedKey\":\"enabled\",\"position\":4},{\"key\":\"queryParamsHandling\",\"mappedKey\":\"query_params_handling\",\"position\":5},{\"key\":\"salesChannelId\",\"mappedKey\":\"sales_channel_id\",\"position\":6}]',:createdAt, 'default_scop_platform_redirecter_redirect');
SQL;

        $connection->executeStatement($sql, [
            'id' => Uuid::fromHexToBytes($importExportId),
            'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        if (!empty($enGbLangId)) {
            $connection->executeStatement('
            INSERT IGNORE INTO `import_export_profile_translation`
                (import_export_profile_id, language_id, label, created_at)
            VALUES
                (:importExportProfileId, :languageId, :label, :createdAt)
            ', [
                'importExportProfileId' => Uuid::fromHexToBytes($importExportId),
                'languageId' => $enGbLangId,
                'label' => 'Default redirect',
                'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if (!empty($deDeLangId)) {
            $connection->executeStatement('
            INSERT IGNORE INTO `import_export_profile_translation`
                (import_export_profile_id, language_id, label, created_at)
            VALUES
                (:importExportProfileId, :languageId, :label, :createdAt)
            ', [
                'importExportProfileId' => Uuid::fromHexToBytes($importExportId),
                'languageId' => $deDeLangId,
                'label' => 'Default Weiterleitung',
                'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if ($defaultLangId != $enGbLangId && $defaultLangId != $deDeLangId) {
            $connection->executeStatement('
            INSERT IGNORE INTO `import_export_profile_translation`
                (import_export_profile_id, language_id, label, created_at)
            VALUES
                (:importExportProfileId, :languageId, :label, :createdAt)
            ', [
                'importExportProfileId' => Uuid::fromHexToBytes($importExportId),
                'languageId' => $defaultLangId,
                'label' => 'Default redirect',
                'createdAt' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): ?string
    {
        $sql = <<<SQL
        SELECT `language`.`id`
        FROM `language`
        INNER JOIN `locale` ON `locale`.`id` = `language`.`locale_id`
        WHERE `locale`.`code` = :code
        SQL;

        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchOne();

        if (empty($languageId)) {
            return null;
        }

        return $languageId;
    }
}
