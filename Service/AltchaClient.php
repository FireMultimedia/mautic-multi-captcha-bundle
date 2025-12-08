<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use Psr\Log\LoggerInterface;

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
    private LoggerInterface $logger;
    private ?Altcha $altcha = null;

    /**
     * <h2>AltchaClient constructor.</h2>
     *
     * @param IntegrationHelper $integrationHelper
     * @param LoggerInterface $logger
     * 
     * @throws \RuntimeException if Altcha library is not installed
     * @throws \RuntimeException if HMAC key is not configured
     */
    public function __construct(IntegrationHelper $integrationHelper, LoggerInterface $logger) {
        $this->logger = $logger;

        // Check if Altcha library is available
        if (!class_exists(Altcha::class)) {
            $message = 'Altcha library not installed. Run: composer require altcha-org/altcha';
            $this->logger->error($message);
            throw new \RuntimeException($message);
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
            $message = 'Altcha HMAC key not configured';
            $this->logger->error($message);
            throw new \RuntimeException($message);
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
            $expiresDateTime = new \DateTime();
            $expiresDateTime->modify('+' . $expiresInSeconds . ' seconds');
            
            $options = new ChallengeOptions(
                maxNumber: $maxNumber,
                expires: $expiresDateTime
            );

            $challenge = $this->altcha->createChallenge($options);

            // Convert Challenge object to array
            return [
                'algorithm' => $challenge->algorithm,
                'challenge' => $challenge->challenge,
                'maxnumber' => $challenge->maxNumber,
                'salt' => $challenge->salt,
                'signature' => $challenge->signature
            ];
        } catch (\Exception $e) {
            $this->logger->error('Altcha challenge generation failed', [
                'exception' => $e->getMessage(),
                'maxNumber' => $maxNumber,
                'expires' => $expiresInSeconds
            ]);
            
            return [];
        }
    }

    /**
     * <h2>verify</h2>
     * 
     * Verifies an Altcha challenge payload.
     *
     * @param string $payload JSON string containing the challenge solution
     * 
     * @return bool True if the payload is valid, false otherwise
     */
    public function verify(string $payload): bool {
        try {
            $result = $this->altcha->verifySolution($payload, true);
            
            return $result === true;
        } catch (\JsonException $e) {
            $this->logger->error('Altcha payload JSON decode failed', [
                'exception' => $e->getMessage(),
                'payload' => $payload
            ]);
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Altcha verification failed', [
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

}
