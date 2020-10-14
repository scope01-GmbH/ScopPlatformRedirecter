<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */

declare(strict_types = 1);
namespace Scop\PlatformRedirecter;

use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin;

/**
 * Shopware-Plugin ScopPlatformRedirecter
 */
class ScopPlatformRedirecter extends Plugin
{

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\Plugin::uninstall()
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        if ($uninstallContext->keepUserData()) {
            return;
        }
    }
}