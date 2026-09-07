<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticMultiCaptchaBundle\DependencyInjection\Compiler\EncryptionHelperAliasPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * <h1>Class MauticMultiCaptchaBundle</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class MauticMultiCaptchaBundle extends PluginBundleBase {

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new EncryptionHelperAliasPass());
    }

}
