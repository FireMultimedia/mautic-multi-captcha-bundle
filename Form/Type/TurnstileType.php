<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;

/**
 * <h1>Class TurnstileType</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class TurnstileType extends AbstractType {

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
        ])->add("size", ChoiceType::class, [
            "label"    => "strings.turnstile.settings.size",
            "required" => false,
            "data"     => $options["data"]["size"] ?? "normal",

            "choices" => [
                "strings.turnstile.settings.size.option.normal"   => "normal",
                "strings.turnstile.settings.size.option.flexible" => "flexible",
                "strings.turnstile.settings.size.option.compact"  => "compact"
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.turnstile.settings.size.tooltip"
            ]
        ])->add("theme", ChoiceType::class, [
            "label"    => "strings.turnstile.settings.theme",
            "required" => false,
            "data"     => $options["data"]["theme"] ?? "auto",

            "choices" => [
                "strings.turnstile.settings.theme.option.auto"  => "auto",
                "strings.turnstile.settings.theme.option.light" => "light",
                "strings.turnstile.settings.theme.option.dark"  => "dark"
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.turnstile.settings.theme.tooltip"
            ]
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return TurnstileIntegration::INTEGRATION_NAME;
    }

}
