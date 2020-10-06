<?php

declare(strict_types = 1);
namespace Scop\PlatformRedirecter;

use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin;

class ScopPlatformRedirecter extends Plugin
{

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        if ($uninstallContext->keepUserData()) {
            return;
        }
    }
}