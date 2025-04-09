<?php declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;

return static function(ContainerConfigurator $configurator): void {
    $services = $configurator->services()
                             ->defaults()
                             ->autowire()
                             ->autoconfigure()
                             ->public();

    $services->load("MauticPlugin\\MauticMultiCaptchaBundle\\", "../")
             ->exclude(sprintf("../{%s}", implode(",", MauticCoreExtension::DEFAULT_EXCLUDES)));
};
