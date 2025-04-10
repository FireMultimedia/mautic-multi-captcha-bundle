<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Service;

use \JsonException;
use GuzzleHttp\Exception\GuzzleException;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\FormBundle\Entity\Field;
use Mautic\CoreBundle\Helper\ArrayHelper;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;

use GuzzleHttp\Client as GuzzleClient;

/**
 * <h1>Class TurnstileClient</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Service
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class TurnstileClient {

    public const VERIFICATION_URL = "https://challenges.cloudflare.com/turnstile/v0/siteverify";

    protected ?string $secretKey;

    /**
     * <h2>HcaptchaClient constructor.</h2>
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper) {
        $integrationObject = $integrationHelper->getIntegrationObject(TurnstileIntegration::INTEGRATION_NAME);

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->secretKey = $keys["secret_key"] ?? null;
        }
    }

    /**
     * <h2>verify</h2>
     *
     * @param string $token
     *
     * @throws GuzzleException
     * @throws JsonException
     *
     * @return bool
     */
    public function verify(string $token): bool {
        $client = new GuzzleClient([
            "timeout" => 10
        ]);

        $guzzleResponse = $client->post(self::VERIFICATION_URL, [
            "form_params" => [
                "secret"   => $this->secretKey,
                "response" => $token
            ]
        ]);

        $response = json_decode($guzzleResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_key_exists("success", $response) && $response["success"] === true;
    }

}
