<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;

/**
 * <h1>Class AltchaType</h1>
 *
 * Note: unlike the other CAPTCHA providers, ALTCHA does not set cookies or
 * load any third-party tracking script, so there is deliberately no
 * "explicit consent" toggle here - there is nothing to consent to.
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaType extends AbstractType {

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add("complexity", ChoiceType::class, [
            "label"    => "strings.altcha.settings.complexity",
            "required" => false,
            "data"     => $options["data"]["complexity"] ?? "medium",

            "choices" => [
                "strings.altcha.settings.complexity.option.low"    => "low",
                "strings.altcha.settings.complexity.option.medium" => "medium",
                "strings.altcha.settings.complexity.option.high"   => "high"
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.altcha.settings.complexity.tooltip"
            ]
        ])->add("expire", IntegerType::class, [
            "label"    => "strings.altcha.settings.expire",
            "required" => false,
            "data"     => isset($options["data"]["expire"]) ? (int) $options["data"]["expire"] : 600,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"   => "form-control",
                "tooltip" => "strings.altcha.settings.expire.tooltip"
            ]
        ])->add("auto", ChoiceType::class, [
            "label"    => "strings.altcha.settings.auto",
            "required" => false,
            "data"     => $options["data"]["auto"] ?? "onsubmit",

            "choices" => [
                "strings.altcha.settings.auto.option.onload"   => "onload",
                "strings.altcha.settings.auto.option.onsubmit" => "onsubmit",
                "strings.altcha.settings.auto.option.off"      => "off"
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.altcha.settings.auto.tooltip"
            ]
        ])->add("display", ChoiceType::class, [
            "label"    => "strings.altcha.settings.display",
            "required" => false,
            "data"     => $options["data"]["display"] ?? "standard",

            "choices" => [
                "strings.altcha.settings.display.option.standard"  => "standard",
                "strings.altcha.settings.display.option.bar"       => "bar",
                "strings.altcha.settings.display.option.floating"  => "floating",
                "strings.altcha.settings.display.option.overlay"   => "overlay",
                "strings.altcha.settings.display.option.invisible" => "invisible"
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.altcha.settings.display.tooltip"
            ]
        ])->add("hideFooter", YesNoButtonGroupType::class, [
            "label"    => "strings.altcha.settings.hide_footer",
            "required" => false,
            "data"     => $options["data"]["hideFooter"] ?? false,

            "label_attr" => [
                "class" => "control-label"
            ]
        ])->add("hideLogo", YesNoButtonGroupType::class, [
            "label"    => "strings.altcha.settings.hide_logo",
            "required" => false,
            "data"     => $options["data"]["hideLogo"] ?? false,

            "label_attr" => [
                "class" => "control-label"
            ]
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix(): string {
        return AltchaIntegration::INTEGRATION_NAME;
    }

}
