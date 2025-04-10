<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\HcaptchaIntegration;

/**
 * <h1>Class HcaptchaType</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class HcaptchaType extends AbstractType {

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
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return HcaptchaIntegration::INTEGRATION_NAME;
    }

}
