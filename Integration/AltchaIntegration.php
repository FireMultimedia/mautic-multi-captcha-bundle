<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Range;

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
    public function appendToForm(&$builder, $data, $formArea): void {
        if($formArea === "keys") {
            $builder->add("maxNumber", NumberType::class, [
                "label" => "strings.altcha.settings.max_number",
                "required" => false,
                "data" => isset($data["maxNumber"]) ? (int) $data["maxNumber"] : 50000,

                "label_attr" => [
                    "class" => "control-label"
                ],

                "attr" => [
                    "class" => "form-control",
                    "tooltip" => "strings.altcha.settings.max_number.tooltip"
                ],

                "constraints" => [
                    new Range([
                        "min" => 1000,
                        "max" => 1000000,
                        "notInRangeMessage" => "Value must be between {{ min }} and {{ max }}"
                    ])
                ]
            ])->add("expires", NumberType::class, [
                "label" => "strings.altcha.settings.expires",
                "required" => false,
                "data" => isset($data["expires"]) ? (int) $data["expires"] : 120,

                "label_attr" => [
                    "class" => "control-label"
                ],

                "attr" => [
                    "class" => "form-control",
                    "tooltip" => "strings.altcha.settings.expires.tooltip"
                ],

                "constraints" => [
                    new Range([
                        "min" => 10,
                        "max" => 300,
                        "notInRangeMessage" => "Value must be between {{ min }} and {{ max }}"
                    ])
                ]
            ]);
        }
    }

}
