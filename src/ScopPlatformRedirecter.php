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

namespace Scop\PlatformRedirecter;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

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

        // keep data for plugin
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /**
         *
         * @var Connection $connection
         */
        $connection = $this->container->get(Connection::class);

        $sql = "DROP TABLE IF EXISTS `scop_platform_redirecter_redirect`;";

        $connection->executeUpdate($sql);
    }
}
