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
    public const ERROR_INCORRECT_COLUMNS = 'incorrect_columns';
    public const ERROR_WRONG_ENCODING = 'wrong_encoding';
    public const ERROR_INVALID_FILE_TYPE = 'invalid_file_type';
    public const ERROR_FILE_OPEN = 'file_open_error';
    public const ERROR_FILE_READ = 'file_read_error';
    public const ERROR_EMPTY_FILE = 'empty_file';

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
