<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Range;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;

/**
 * <h1>Class AltchaType</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaType extends AbstractType {

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add("invisible", YesNoButtonGroupType::class, [
            "label" => "strings.altcha.settings.invisible",
            "data"  => $options["data"]["invisible"] ?? false,

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.altcha.settings.invisible.tooltip"
            ]
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return AltchaIntegration::INTEGRATION_NAME;
    }

}
