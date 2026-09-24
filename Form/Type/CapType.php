<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;

/**
 * <h1>Class CapType</h1>
 *
 * Cap always combines its proof-of-work and instrumentation challenges (both
 * are configured server-side on the self-hosted Cap Standalone instance), so
 * the only per-field option exposed here is how/when the widget solves:
 * manually on click, automatically while still visible, or automatically
 * while hidden (mirrors ALTCHA's invisible mode).
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Form\Type
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class CapType extends AbstractType {

    public const MODE_MANUAL      = "manual";
    public const MODE_AUTO        = "auto";
    public const MODE_AUTO_HIDDEN = "auto_hidden";

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add("mode", ChoiceType::class, [
            "label"    => "strings.cap.settings.mode",
            "required" => false,
            "data"     => $options["data"]["mode"] ?? self::MODE_MANUAL,

            "choices" => [
                "strings.cap.settings.mode.option.manual"      => self::MODE_MANUAL,
                "strings.cap.settings.mode.option.auto"        => self::MODE_AUTO,
                "strings.cap.settings.mode.option.auto_hidden" => self::MODE_AUTO_HIDDEN
            ],

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "tooltip" => "strings.cap.settings.mode.tooltip"
            ]
        ]);

        if(!empty($options["action"]))
            $builder->setAction($options["action"]);
    }

    /** {@inheritDoc} */
    public function getBlockPrefix() {
        return CapIntegration::INTEGRATION_NAME;
    }

}
