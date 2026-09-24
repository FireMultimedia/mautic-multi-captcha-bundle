<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * In Mautic 7 the mautic.helper.encryption service alias was removed.
 * The service is now registered only under its FQCN. This pass recreates
 * the alias so plugin config.php service definitions work across all versions.
 */
class EncryptionHelperAliasPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('mautic.helper.encryption')
            && $container->has(EncryptionHelper::class)
        ) {
            $container->setAlias('mautic.helper.encryption', EncryptionHelper::class)
                ->setPublic(true);
        }
    }
}
