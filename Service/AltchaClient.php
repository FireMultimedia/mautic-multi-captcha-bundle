<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use \RuntimeException;
use \JsonException;
use \Exception;

use \DateTimeImmutable;
use \DateInterval;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;

use AltchaOrg\Altcha\Hasher\Algorithm;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;

/**
 * <h1>Class AltchaClient</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Service
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaClient {

    private ?Altcha $altcha = null;
    private ?AbstractIntegration $integrationObject = null;

    /**
     * <h2>AltchaClient constructor.</h2>
     *
     * @param IntegrationHelper $integrationHelper
     * 
     * @throws RuntimeException if ALTCHA library is not installed
     * @throws RuntimeException if HMAC key is not configured
     */
    public function __construct(IntegrationHelper $integrationHelper) {
        if(!class_exists(Altcha::class))
            throw new RuntimeException("ALTCHA library not installed. Run: composer require altcha-org/altcha");

        $this->integrationObject = $integrationHelper->getIntegrationObject(AltchaIntegration::INTEGRATION_NAME);

        if ($this->integrationObject instanceof AbstractIntegration) {
            $keys = $this->integrationObject->getKeys();

            $hmacKey = $keys["hmac_key"] ?? null;
        } else {
            $hmacKey = null;
        }

        if(empty($hmacKey))
            throw new RuntimeException("ALTCHA HMAC key not configured");

        $this->altcha = new Altcha($hmacKey);
    }

    /**
     * <h2>getConfiguration</h2>
     * 
     * Retrieves the ALTCHA configuration settings from the integration.
     *
     * @return array Configuration array with maxNumber and expires values
     */
    public function getConfiguration(): array {
        if (!$this->integrationObject instanceof AbstractIntegration) {
            return [];
        }

        $keys = $this->integrationObject->getKeys();

        return [
            'maxNumber' => isset($keys['maxNumber']) ? (int) $keys['maxNumber'] : null,
            'expires' => isset($keys['expires']) ? (int) $keys['expires'] : null
        ];
    }

    /**
     * <h2>createChallenge</h2>
     * 
     * Generates a new ALTCHA challenge with the specified parameters.
     *
     * @param int $maxNumber Maximum random number for the challenge (1000-1000000)
     * @param int $expiresInSeconds Challenge expiration time in seconds (10-300)
     * 
     * @return array Challenge data containing algorithm, challenge, salt, signature, maxnumber, and expires
     */
    public function createChallenge(int $maxNumber, int $expiresInSeconds): array {
        try {
            // Create challenge with expiration time
            $expires = new DateTimeImmutable();

            $expires = $expires->add(new DateInterval("PT{$expiresInSeconds}S"));
            
            $challenge = $this->altcha->createChallenge(new ChallengeOptions(Algorithm::SHA256, $maxNumber, $expires));

            // Convert Challenge object to array
            return [
                "algorithm" => $challenge->algorithm,
                "challenge" => $challenge->challenge,
                "maxnumber" => $challenge->maxNumber,
                "salt"      => $challenge->salt,
                "signature" => $challenge->signature
            ];
        } catch(Exception $e) {
            error_log(sprintf(
                "ALTCHA challenge creation failed: %s | Trace: %s",
                $e->getMessage(),
                $e->getTraceAsString())
            );

            return [];
        }
    }

    /**
     * <h2>verify</h2>
     *   Verifies an ALTCHA challenge payload.
     *
     * @param string $payload JSON string or base64-encoded string containing the challenge solution
     * 
     * @return bool True if the payload is valid, false otherwise
     */
    public function verify(string $payload): bool {
        try {
            // Ensure payload is base64 encoded (required by ALTCHA library)
            // If it's already base64, use it as-is, if it's JSON, encode it to base64
            $decoded = base64_decode($payload, true);

            /** @noinspection JsonEncodingApiUsageInspection supposed to fail in order to fail condition */
            if($decoded === false || json_decode($decoded) === null) {
                // Not valid base64 or not valid JSON after decoding, try to parse as JSON directly
                $payloadData = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

                if($payloadData === null)
                    return false;

                // It's JSON, encode it to base64
                $payload = base64_encode($payload);
            }
            
            // Verify the solution with checkExpires=true
            $result = $this->altcha->verifySolution($payload, true);
            
            return $result === true;
        } catch(JsonException $e) {
            return false;
        } catch(Exception $e) {
            return false;
        }
    }

}
