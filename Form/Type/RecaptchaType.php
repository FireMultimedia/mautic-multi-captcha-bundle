<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\NumberType;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;

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
        $builder->add("scoreValidation", YesNoButtonGroupType::class, [
            "label" => "mautic.recaptcha.score.validation",
            "data"  => $options["data"]["scoreValidation"] ?? false,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "mautic.recaptcha.min.score.tooltip",
            ]
        ]);

        $builder->add("minScore", NumberType::class, [
                "label" => "mautic.recaptcha.min.score",
                "data"  => isset($options["data"]["minScore"]) ? (float) $options["data"]["minScore"] : 0.8,

                "label_attr" => [
                    "class" => "control-label"
                ],

                "attr" => [
                    "class"        => "form-control",
                    "data-show-on" => '{"formfield_properties_scoreValidation_1":"checked"}'
                ]
            ]
        );

        $builder->add("buttons", FormButtonsType::class, [
                "apply_text"     => false,
                "save_text"      => "mautic.core.form.submit",
                "cancel_onclick" => "javascript:void(0);",

                "cancel_attr" => [
                    "data-dismiss" => "modal",
                ]
            ]
        );

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return RecaptchaIntegration::INTEGRATION_NAME;
    }

}
