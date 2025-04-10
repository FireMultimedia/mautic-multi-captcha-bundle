<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle;

/**
 * <h1>Class CaptchaEvents</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * /
 */
final class CaptchaEvents {
    public const HCAPTCHA_ON_FORM_VALIDATE  = "mautic.plugin.hcaptcha.on_form_validate";
    public const RECAPTCHA_ON_FORM_VALIDATE = "mautic.plugin.recaptcha.on_form_validate";
    public const TURNSTILE_ON_FORM_VALIDATE = "mautic.plugin.turnstile.on_form_validate";
}
