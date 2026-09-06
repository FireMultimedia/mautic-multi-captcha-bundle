<?php declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\CoreBundle\Helper\EncryptionHelper;

return static function(ContainerConfigurator $configurator): void {
    $services = $configurator->services()
                             ->defaults()
                             ->autowire()
                             ->autoconfigure()
                             ->public();

    // Legacy config.php treats FQCN arguments as strings, so use a local service alias.
    $services->alias("mautic.multicaptcha.helper.encryption", EncryptionHelper::class);

    $services->load("MauticPlugin\\MauticMultiCaptchaBundle\\", "../")
             ->exclude(sprintf("../{%s}", implode(",", MauticCoreExtension::DEFAULT_EXCLUDES)));
};
