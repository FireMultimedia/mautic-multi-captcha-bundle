<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use \JsonException;
use GuzzleHttp\Exception\GuzzleException;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;

/**
 * <h1>Class CapClient</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Service
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class CapClient {

    private ?string $serverUrl;
    private ?string $secretKey;

    private GuzzleClientInterface $httpClient;

    /**
     * <h2>CapClient constructor.</h2>
     *
     * @param IntegrationHelper          $integrationHelper
     * @param GuzzleClientInterface|null $httpClient Injectable for testing; a real Guzzle client is used when omitted.
     */
    public function __construct(IntegrationHelper $integrationHelper, ?GuzzleClientInterface $httpClient = null) {
        $integrationObject = $integrationHelper->getIntegrationObject(CapIntegration::INTEGRATION_NAME);

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->serverUrl = isset($keys["server_url"]) ? rtrim($keys["server_url"], "/") : null;
            $this->secretKey = $keys["secret_key"] ?? null;
        }

        $this->httpClient = $httpClient ?? new GuzzleClient([
            "timeout" => 10
        ]);
    }

    /**
     * <h2>verify</h2>
     *
     * Verifies a redeemed Cap token against the self-hosted Cap Standalone
     * instance's /siteverify endpoint. The site key does not need to be sent
     * separately, it is embedded in the redeemed token itself.
     *
     * @param string $token
     *
     * @throws GuzzleException
     * @throws JsonException
     *
     * @return bool
     */
    public function verify(string $token): bool {
        $guzzleResponse = $this->httpClient->post("{$this->serverUrl}/siteverify", [
            "json" => [
                "secret"   => $this->secretKey,
                "response" => $token
            ]
        ]);

        $response = json_decode($guzzleResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_key_exists("success", $response) && $response["success"] === true;
    }

}
