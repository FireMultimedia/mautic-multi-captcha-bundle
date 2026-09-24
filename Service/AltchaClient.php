<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use AltchaOrg\Altcha\V1\Altcha;
use AltchaOrg\Altcha\V1\ChallengeOptions;
use AltchaOrg\Altcha\V1\Hasher\Algorithm;

use AltchaOrg\Altcha\ServerSignature;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;

/**
 * <h1>Class AltchaClient</h1>
 *
 * Supports two mutually exclusive ways of running ALTCHA:
 *
 * 1. Self-hosted (default): Mautic generates and signs its own challenge
 *    with a local HMAC secret, and verifies the solution locally too. No
 *    outbound HTTP request is ever made - privacy-friendly (no cookies,
 *    no external requests, no tracking).
 *
 * 2. ALTCHA Sentinel: challenges are issued directly by a Sentinel instance.
 *    The widget points at Sentinel's /v1/challenge endpoint, and Mautic only
 *    verifies the server signature locally using the API key's secret.
 *    Still no outbound network call on verification.
 *
 * If both a self-hosted HMAC secret and Sentinel credentials are filled in,
 * Sentinel takes precedence.
 *
 * IMPORTANT: the widget's "challengeurl" attribute always points at a URL -
 * never an inline JSON challenge. Mautic caches the entire rendered form HTML
 * in `forms.cached_html` and only regenerates it when the form is saved, not
 * on every page view. A challenge baked into that cached HTML would be reused
 * by every visitor until the form's next save, going stale and causing
 * "Verification failed. Try again later." for everyone. A URL sidesteps this
 * entirely since the widget fetches a fresh challenge live each time it loads.
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Service
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaClient {

    public const DEFAULT_MAX_NUMBER    = 100000;
    public const DEFAULT_EXPIRE_SECONDS = 600;

    /** Maps the field's "complexity" property to a proof-of-work maxNumber. */
    private const COMPLEXITY_MAP = [
        "low"    => 50000,
        "medium" => 100000,
        "high"   => 400000
    ];

    private ?string $hmacSecret = null;

    private ?string $sentinelDomain    = null;
    private ?string $sentinelApiKey    = null;
    private ?string $sentinelApiSecret = null;

    public function __construct(
        IntegrationHelper $integrationHelper,
        private readonly UrlGeneratorInterface $router
    ) {
        $integrationObject = $integrationHelper->getIntegrationObject(AltchaIntegration::INTEGRATION_NAME);

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->hmacSecret = $keys["hmac_secret"] ?? null;

            $this->sentinelDomain    = $keys["sentinel_domain"] ?? null;
            $this->sentinelApiKey    = $keys["sentinel_api_key"] ?? null;
            $this->sentinelApiSecret = $keys["sentinel_api_secret"] ?? null;
        }
    }

    public function usesSentinel(): bool {
        return !empty($this->sentinelDomain) && !empty($this->sentinelApiKey) && !empty($this->sentinelApiSecret);
    }

    public function hasSelfHostedSecret(): bool {
        return !empty($this->hmacSecret);
    }

    public function isConfigured(): bool {
        return $this->usesSentinel() || $this->hasSelfHostedSecret();
    }

    public function buildSentinelChallengeUrl(): string {
        $domain = rtrim((string) $this->sentinelDomain, "/");

        return $domain . "/v1/challenge?apiKey=" . rawurlencode((string) $this->sentinelApiKey);
    }

    /**
     * Generates a fresh signed challenge. Must be called on every request
     * (via ChallengeController) - never cache or reuse a challenge.
     *
     * @return array{algorithm: string, challenge: string, salt: string, signature: string, maxNumber: int}
     */
    public function createChallenge(int $maxNumber = self::DEFAULT_MAX_NUMBER, int $expireSeconds = self::DEFAULT_EXPIRE_SECONDS): array {
        $altcha = new Altcha((string) $this->hmacSecret);

        $challenge = $altcha->createChallenge(new ChallengeOptions(
            algorithm:  Algorithm::SHA256,
            maxNumber:  $maxNumber,
            expires:    new \DateTimeImmutable("+{$expireSeconds} seconds")
        ));

        return [
            "algorithm" => $challenge->algorithm,
            "challenge" => $challenge->challenge,
            "salt"      => $challenge->salt,
            "signature" => $challenge->signature,
            // Must be camelCase - widgets >= v1.4.0 require "maxNumber",
            // not "maxnumber". A lowercase key makes the widget treat the
            // challenge as malformed and fail immediately client-side.
            "maxNumber" => $challenge->maxNumber
        ];
    }

    public function createChallengeForComplexity(string $complexity, int $expireSeconds = self::DEFAULT_EXPIRE_SECONDS): ?array {
        if(!$this->hasSelfHostedSecret())
            return null;

        $maxNumber = self::COMPLEXITY_MAP[$complexity] ?? self::COMPLEXITY_MAP["medium"];

        return $this->createChallenge($maxNumber, $expireSeconds);
    }

    public function buildSelfHostedChallengeUrl(string $complexity, int $expireSeconds): string {
        return $this->router->generate("mautic_altcha_challenge", [
            "complexity" => $complexity,
            "expire"     => $expireSeconds
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Returns the URL the widget's "challengeurl" attribute should contain.
     * Always a URL (never inline JSON) - see class docblock for why.
     *
     * @return string|null Null when neither mode is configured yet.
     */
    public function buildWidgetChallenge(string $complexity = "medium", int $expireSeconds = self::DEFAULT_EXPIRE_SECONDS): ?string {
        if($this->usesSentinel())
            return $this->buildSentinelChallengeUrl();

        if(!$this->hasSelfHostedSecret())
            return null;

        return $this->buildSelfHostedChallengeUrl($complexity, $expireSeconds);
    }

    /**
     * Verifies the base64-encoded payload the <altcha-widget> submits.
     *
     * - Self-hosted: local HMAC signature + expiry + proof-of-work check. No network call.
     * - Sentinel: verifies the server signature locally using the API key's secret. No network call.
     */
    public function verify(string $payload): bool {
        if("" === trim($payload))
            return false;

        if($this->usesSentinel()) {
            try {
                $result = ServerSignature::verifyServerSignature($payload, (string) $this->sentinelApiSecret);

                return (bool) $result->verified;
            } catch(\Throwable) {
                return false;
            }
        }

        if(!$this->hasSelfHostedSecret())
            return false;

        $altcha = new Altcha((string) $this->hmacSecret);

        try {
            return $altcha->verifySolution($payload, true);
        } catch(\Throwable) {
            return false;
        }
    }

}
