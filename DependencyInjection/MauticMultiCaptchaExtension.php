<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\DependencyInjection;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Component\DependencyInjection\Extension\Extension;

use \Exception;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use Symfony\Component\Config\FileLocator;

/**
 * <h1>Class MauticMultiCaptchaExtension</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\DependencyInjection
 */
class MauticMultiCaptchaExtension extends Extension {

    /**
     * <h2>load</h2>
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void {
        // In Mautic 7 the mautic.helper.encryption alias was removed; the service
        // is now only registered under its FQCN. Re-create the alias so the legacy
        // plugin config.php service definitions keep working across all versions.
        if (!$container->has('mautic.helper.encryption')
            && $container->has(\Mautic\CoreBundle\Helper\EncryptionHelper::class)
        ) {
            $container->setAlias('mautic.helper.encryption', \Mautic\CoreBundle\Helper\EncryptionHelper::class)
                ->setPublic(true);
        }
    }

}
