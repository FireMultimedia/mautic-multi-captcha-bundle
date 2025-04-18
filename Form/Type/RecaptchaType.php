<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;

use Symfony\Component\Form\Extension\Core\Type\NumberType;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\RecaptchaIntegration;

/**
 * <h1>Class RecaptchaType</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class RecaptchaType extends AbstractType {

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add("explicitConsent", YesNoButtonGroupType::class, [
            "label" => "strings.settings.explicit_consent",
            "data"  => $options["data"]["explicitConsent"] ?? true,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.settings.explicit_consent.tooltip"
            ]
        ])->add("spacer", YesNoButtonGroupType::class, [
            "attr" => [
                "class"        => "form-control",
                "data-show-on" => '{"formfield_properties_spacer_1":"checked"}'
            ]
        ])->add("scoreValidation", YesNoButtonGroupType::class, [
            "label" => "strings.recaptcha.settings.score_validation",
            "data"  => $options["data"]["scoreValidation"] ?? true,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.recaptcha.settings.score_validation.tooltip"
            ]
        ])->add("minScore", NumberType::class, [
            "label" => "strings.recaptcha.settings.min_score",
            "data"  => isset($options["data"]["minScore"]) ? (float) $options["data"]["minScore"] : 0.8,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"        => "form-control",
                "data-show-on" => '{"formfield_properties_scoreValidation_1":"checked"}'
            ]
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return RecaptchaIntegration::INTEGRATION_NAME;
    }

}
