<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * <h1>Class TurnstileIntegration</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Integration
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class TurnstileIntegration extends AbstractIntegration {

    public const INTEGRATION_NAME = "Turnstile";

    /** {@inheritDoc} */
    public function getName() {
        return self::INTEGRATION_NAME;
    }

    /** {@inheritDoc} */
    public function getDisplayName() {
        return "Cloudflare Turnstile";
    }

    /** {@inheritDoc} */
    public function getAuthenticationType() {
        return "none";
    }

    /** {@inheritDoc} */
    public function getRequiredKeyFields() {
        return [
            "site_key"   => "strings.turnstile.settings.site_key",
            "secret_key" => "strings.turnstile.settings.secret_key",
        ];
    }

}
