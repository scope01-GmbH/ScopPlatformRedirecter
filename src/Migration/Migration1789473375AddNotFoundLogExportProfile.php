<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Scop\PlatformRedirecter\NotFoundLog\NotFoundLogDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

final class Migration1789473375AddNotFoundLogExportProfile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1789473375;
    }

    public function update(Connection $connection): void
    {
        $technicalName = 'default_' . NotFoundLogDefinition::ENTITY_NAME;
        $columns = [
            'url' => 'url',
            'hitCount' => 'hit_count',
            'lastHitAt' => 'last_hit_at',
            'salesChannelId' => 'sales_channel_id',
            'salesChannel.name' => 'sales_channel',
            'ignored' => 'ignored',
            'redirectId' => 'redirect_id',
            'referers' => 'referers',
        ];
        $mapping = [];
        foreach ($columns as $key => $mappedKey) {
            $mapping[] = ['key' => $key, 'mappedKey' => $mappedKey, 'position' => \count($mapping)];
        }

        $connection->transactional(function (Connection $connection) use ($technicalName, $mapping): void {
            if ($connection->fetchOne('SELECT 1 FROM import_export_profile WHERE technical_name = :name', ['name' => $technicalName])) {
                return;
            }

            $profileId = Uuid::randomBytes();
            $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            $connection->insert('import_export_profile', [
                'id' => $profileId,
                'technical_name' => $technicalName,
                'system_default' => 1,
                'source_entity' => NotFoundLogDefinition::ENTITY_NAME,
                'file_type' => 'text/csv',
                'type' => 'export',
                'delimiter' => ';',
                'enclosure' => '"',
                'mapping' => json_encode($mapping, \JSON_THROW_ON_ERROR),
                'config' => '{}',
                'created_at' => $now,
            ]);

            $languages = $connection->fetchAllKeyValue(
                'SELECT language.id, locale.code FROM language INNER JOIN locale ON language.locale_id = locale.id
                 WHERE language.id = :systemLanguage OR locale.code IN (\'de-DE\', \'en-GB\')',
                ['systemLanguage' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)],
            );
            foreach ($languages as $languageId => $locale) {
                $connection->insert('import_export_profile_translation', [
                    'import_export_profile_id' => $profileId,
                    'language_id' => $languageId,
                    'label' => $locale === 'de-DE' ? 'Standardprofil 404-Log' : 'Default 404 log',
                    'created_at' => $now,
                ]);
            }
        });
    }
}
