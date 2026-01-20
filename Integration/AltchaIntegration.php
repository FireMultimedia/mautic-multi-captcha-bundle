<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * <h1>Class AltchaIntegration</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Integration
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaIntegration extends AbstractIntegration {

    public const INTEGRATION_NAME = "Altcha";

    /** {@inheritDoc} */
    public function getName() {
        return self::INTEGRATION_NAME;
    }

    /** {@inheritDoc} */
    public function getDisplayName() {
        return "ALTCHA";
    }

    /** {@inheritDoc} */
    public function getAuthenticationType() {
        return "none";
    }

    /** {@inheritDoc} */
    public function getRequiredKeyFields() {
        return [
            "hmac_key" => "strings.altcha.settings.hmac_key"
        ];
    }

    /** {@inheritDoc} */
    public function getAvailableFields() {
        return [
            "maxNumber" => [
                "label" => "strings.altcha.settings.max_number",
                "type" => "number",
                "required" => false
            ],
            "expires" => [
                "label" => "strings.altcha.settings.expires",
                "type" => "number",
                "required" => false
            ]
        ];
    }

}
