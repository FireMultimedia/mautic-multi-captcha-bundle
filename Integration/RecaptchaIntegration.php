<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * <h1>Class RecaptchaIntegration</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Integration
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class RecaptchaIntegration extends AbstractIntegration {

    public const INTEGRATION_NAME = "Recaptcha";

    /** {@inheritDoc} */
    public function getName() {
        return self::INTEGRATION_NAME;
    }

    /** {@inheritDoc} */
    public function getDisplayName() {
        return "Google reCAPTCHA";
    }

    /** {@inheritDoc} */
    public function getAuthenticationType() {
        return "none";
    }

    /** {@inheritDoc} */
    public function getRequiredKeyFields() {
        return [
            "site_key"   => "strings.recaptcha.settings.site_key",
            "secret_key" => "strings.recaptcha.settings.secret_key",
        ];
    }

    /** {@inheritDoc} */
    public function appendToForm(&$builder, $data, $formArea): void {
        if($formArea === "keys")
            $builder->add("version", ChoiceType::class, [
                "label"       => "strings.recaptcha.settings.version",
                "required"    => false,
                "placeholder" => false,
                "data"        => $data["version"] ?? "v2",

                "choices" => [
                    "strings.recaptcha.settings.version.option.v2" => "v2",
                    "strings.recaptcha.settings.version.option.v3" => "v3",
                ],

                "label_attr" => [
                    "class" => "control-label"
                ],

                "attr" => [
                    "class" => "form-control"
                ]
            ]);
    }

}
