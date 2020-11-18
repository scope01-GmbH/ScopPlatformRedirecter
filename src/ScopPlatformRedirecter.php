<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types = 1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license proprietär
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter;

use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin;
use Doctrine\DBAL\Connection;

/**
 * Shopware-Plugin ScopPlatformRedirecter
 */
class ScopPlatformRedirecter extends Plugin
{

    /**
     *
     * {@inheritdoc}
     * @see \Shopware\Core\Framework\Plugin::uninstall()
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /**
         *
         * @var Connection $connection
         */
        $connection = $this->container->get(Connection::class);

        $sql = <<<SQL
        DROP TABLE IF EXISTS `scop_platform_redirecter_redirect`;
SQL;

        $connection->executeUpdate($sql);
    }
}
