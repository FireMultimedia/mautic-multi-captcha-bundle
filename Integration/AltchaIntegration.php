<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * <h1>Class AltchaIntegration</h1>
 *
 * ALTCHA is self-hosted by default: there is no third-party API to
 * authenticate against, so authentication type is "none". Two ways of
 * running it are supported side by side in the same settings form:
 *
 * - Self-hosted: just an "hmac_secret" that Mautic uses to sign/verify
 *   challenges on its own, with no outbound requests at all.
 * - ALTCHA Sentinel: a "sentinel_domain" (your Sentinel instance's base
 *   URL), a "sentinel_api_key", and a "sentinel_api_secret". Sentinel
 *   issues challenges directly to the visitor's browser; Mautic only
 *   verifies the resulting server signature locally (no outbound request).
 *
 * None of the four fields are added via getRequiredKeyFields() - that would
 * force ALL of them to be filled in simultaneously, making it impossible to
 * use just the self-hosted mode or just Sentinel. They are added as optional
 * fields via appendToForm() instead, and isConfigured() enforces the
 * "one full set or the other" rule at runtime.
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Integration
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaIntegration extends AbstractIntegration {

    public const INTEGRATION_NAME = "Altcha";

    /** {@inheritDoc} */
    public function getName(): string {
        return self::INTEGRATION_NAME;
    }

    /** {@inheritDoc} */
    public function getDisplayName(): string {
        return "ALTCHA";
    }

    /** {@inheritDoc} */
    public function getAuthenticationType(): string {
        return "none";
    }

    /**
     * Deliberately empty - see the class docblock. Fields are added via
     * appendToForm() so none of them are forced to be non-empty simultaneously.
     */
    public function getRequiredKeyFields(): array {
        return [];
    }

    /** {@inheritDoc} */
    public function getSecretKeys(): array {
        return [
            "hmac_secret",
            "sentinel_api_secret"
        ];
    }

    /**
     * Either the self-hosted HMAC secret OR the full set of Sentinel
     * credentials is required to count as configured.
     */
    public function isConfigured(): bool {
        $keys = $this->getKeys();

        $sentinelReady   = !empty($keys["sentinel_domain"]) && !empty($keys["sentinel_api_key"]) && !empty($keys["sentinel_api_secret"]);
        $selfHostedReady = !empty($keys["hmac_secret"]);

        return $sentinelReady || $selfHostedReady;
    }

    /** {@inheritDoc} */
    public function appendToForm(&$builder, $data, $formArea): void {
        if("keys" !== $formArea)
            return;

        $builder->add("hmac_secret", PasswordType::class, [
            "label"    => "strings.altcha.settings.hmac_secret",
            "required" => false,
            "data"     => $data["hmac_secret"] ?? "",

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"       => "form-control",
                "tooltip"     => "strings.altcha.settings.hmac_secret.notice",
                "placeholder" => "strings.altcha.settings.hmac_secret.placeholder"
            ]
        ])->add("sentinel_domain", UrlType::class, [
            "label"    => "strings.altcha.settings.sentinel_domain",
            "required" => false,
            "data"     => $data["sentinel_domain"] ?? "",

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"   => "form-control",
                "tooltip" => "strings.altcha.settings.sentinel_domain.notice"
            ]
        ])->add("sentinel_api_key", TextType::class, [
            "label"    => "strings.altcha.settings.sentinel_api_key",
            "required" => false,
            "data"     => $data["sentinel_api_key"] ?? "",

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"   => "form-control",
                "tooltip" => "strings.altcha.settings.sentinel_api_key.notice"
            ]
        ])->add("sentinel_api_secret", PasswordType::class, [
            "label"    => "strings.altcha.settings.sentinel_api_secret",
            "required" => false,
            "data"     => $data["sentinel_api_secret"] ?? "",

            "label_attr" => [
                "class" => "control-label"
            ],

            "attr" => [
                "class"       => "form-control",
                "tooltip"     => "strings.altcha.settings.sentinel_api_secret.notice",
                "placeholder" => "strings.altcha.settings.sentinel_api_secret.placeholder"
            ]
        ]);
    }

    /** {@inheritDoc} */
    public function getFormNotes($section): array {
        if(in_array($section, ["keys", "custom"], true)) {
            return [
                "strings.altcha.settings.notice",
                "info"
            ];
        }

        return parent::getFormNotes($section);
    }

}
