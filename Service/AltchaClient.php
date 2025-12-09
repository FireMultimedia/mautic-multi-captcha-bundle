<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
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

    private ?string $hmacKey;
    private ?Altcha $altcha = null;

    /**
     * <h2>AltchaClient constructor.</h2>
     *
     * @param IntegrationHelper $integrationHelper
     * 
     * @throws \RuntimeException if Altcha library is not installed
     * @throws \RuntimeException if HMAC key is not configured
     */
    public function __construct(IntegrationHelper $integrationHelper) {
        // Check if Altcha library is available
        if (!class_exists(Altcha::class)) {
            throw new \RuntimeException('Altcha library not installed. Run: composer require altcha-org/altcha');
        }

        $integrationObject = $integrationHelper->getIntegrationObject(AltchaIntegration::INTEGRATION_NAME);

        if ($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();
            $this->hmacKey = $keys["hmac_key"] ?? null;
        } else {
            $this->hmacKey = null;
        }

        // Check if HMAC key is configured
        if (empty($this->hmacKey)) {
            throw new \RuntimeException('Altcha HMAC key not configured');
        }

        // Initialize Altcha instance
        $this->altcha = new Altcha($this->hmacKey);
    }

    /**
     * <h2>createChallenge</h2>
     * 
     * Generates a new Altcha challenge with the specified parameters.
     *
     * @param int $maxNumber Maximum random number for the challenge (1000-1000000)
     * @param int $expiresInSeconds Challenge expiration time in seconds (10-300)
     * 
     * @return array Challenge data containing algorithm, challenge, salt, signature, maxnumber, and expires
     */
    public function createChallenge(int $maxNumber, int $expiresInSeconds): array {
        try {
            $expiresDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $expiresDateTime->modify('+' . $expiresInSeconds . ' seconds');
            
            $options = new ChallengeOptions(
                maxNumber: $maxNumber,
                expires: $expiresDateTime
            );

            $challenge = $this->altcha->createChallenge($options);

            // Log the created challenge for debugging
            error_log('Altcha challenge created - Salt: ' . $challenge->salt);
            error_log('Altcha challenge created - Signature: ' . $challenge->signature);

            // Convert Challenge object to array
            return [
                'algorithm' => $challenge->algorithm,
                'challenge' => $challenge->challenge,
                'maxnumber' => $challenge->maxNumber,
                'salt' => $challenge->salt,
                'signature' => $challenge->signature
            ];
        } catch (\Exception $e) {
            error_log('Altcha challenge creation failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * <h2>verify</h2>
     * 
     * Verifies an Altcha challenge payload.
     *
     * @param string $payload JSON string or base64-encoded string containing the challenge solution
     * 
     * @return bool True if the payload is valid, false otherwise
     */
    public function verify(string $payload): bool {
        try {
            // Log the incoming payload for debugging
            error_log('Altcha verify - Raw payload: ' . $payload);
            
            // Check if payload is base64 encoded (from the widget)
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $payload)) {
                $decoded = base64_decode($payload, true);
                if ($decoded !== false && json_decode($decoded) !== null) {
                    error_log('Altcha verify - Decoded payload: ' . $decoded);
                    $payload = $decoded;
                }
            }
            
            // Parse the payload to check its structure
            $payloadData = json_decode($payload, true);
            if ($payloadData === null) {
                error_log('Altcha verify - Invalid JSON payload');
                return false;
            }
            
            error_log('Altcha verify - Payload data: ' . print_r($payloadData, true));
            
            // Check if required fields are present
            if (!isset($payloadData['algorithm']) || !isset($payloadData['challenge']) || 
                !isset($payloadData['number']) || !isset($payloadData['salt']) || 
                !isset($payloadData['signature'])) {
                error_log('Altcha verify - Missing required fields in payload');
                return false;
            }
            
            // Verify the solution with checkExpires=true
            $result = $this->altcha->verifySolution($payload, true);
            
            error_log('Altcha verify - Result: ' . ($result ? 'true' : 'false'));
            
            return $result === true;
        } catch (\JsonException $e) {
            error_log('Altcha verify - JsonException: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('Altcha verify - Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

}
