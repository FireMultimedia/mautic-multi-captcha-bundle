<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Service;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for AltchaClient
 */
class AltchaClientTest extends TestCase {

    /**
     * Test: isConfigured returns true when hmac_secret is set
     *
     * @test
     */
    public function testIsConfiguredWithSelfHostedSecret(): void {
        $client = $this->createAltchaClient();
        $this->assertTrue($client->isConfigured());
        $this->assertTrue($client->hasSelfHostedSecret());
        $this->assertFalse($client->usesSentinel());
    }

    /**
     * Test: isConfigured returns false when no credentials are set
     *
     * @test
     */
    public function testIsNotConfiguredWhenNoCredentials(): void {
        $client = $this->createAltchaClientWithConfig([]);
        $this->assertFalse($client->isConfigured());
        $this->assertFalse($client->hasSelfHostedSecret());
        $this->assertFalse($client->usesSentinel());
    }

    /**
     * Test: usesSentinel returns true when all three Sentinel fields are set
     *
     * @test
     */
    public function testUsesSentinelWhenAllSentinelFieldsSet(): void {
        $client = $this->createAltchaClientWithConfig([
            'sentinel_domain'     => 'https://sentinel.example.com',
            'sentinel_api_key'    => 'key_abc123',
            'sentinel_api_secret' => 'secret_xyz',
        ]);

        $this->assertTrue($client->isConfigured());
        $this->assertTrue($client->usesSentinel());
        $this->assertFalse($client->hasSelfHostedSecret());
    }

    /**
     * Property Test: Challenge Structure Completeness
     *
     * For any valid parameters, the returned challenge must contain all required fields.
     *
     * @test
     */
    public function testChallengeStructureCompleteness(): void {
        $iterations = 100;
        $failures   = [];
        $client     = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            $maxNumber = rand(1000, 1000000);
            $expires   = rand(10, 300);
            $challenge = $client->createChallenge($maxNumber, $expires);

            $requiredFields = ['algorithm', 'challenge', 'salt', 'signature', 'maxNumber'];
            $missingFields  = array_filter($requiredFields, fn($f) => !array_key_exists($f, $challenge));

            if (!empty($missingFields)) {
                $failures[] = [
                    'iteration'     => $i,
                    'missing_fields' => array_values($missingFields),
                ];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "Challenge structure completeness failed in %d/%d iterations:\n%s",
            count($failures), $iterations, json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Property Test: Valid Payload Acceptance
     *
     * A correctly solved challenge must be accepted by verify().
     *
     * @test
     */
    public function testValidPayloadAcceptance(): void {
        $iterations = 10; // intentionally small — solving PoW is slow
        $failures   = [];
        $client     = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            $maxNumber = rand(1000, 5000); // small for speed
            $expires   = rand(60, 300);
            $challenge = $client->createChallenge($maxNumber, $expires);

            if (empty($challenge)) {
                $failures[] = ['iteration' => $i, 'reason' => 'Challenge generation failed'];
                continue;
            }

            $solution = $this->solveChallenge($challenge);

            if ($solution === null) {
                $failures[] = ['iteration' => $i, 'reason' => 'Could not solve challenge'];
                continue;
            }

            $payload = base64_encode(json_encode([
                'algorithm' => $challenge['algorithm'],
                'challenge' => $challenge['challenge'],
                'number'    => $solution,
                'salt'      => $challenge['salt'],
                'signature' => $challenge['signature'],
            ]));

            if (!$client->verify($payload)) {
                $failures[] = ['iteration' => $i, 'reason' => 'Valid payload was rejected'];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "Valid payload acceptance failed in %d/%d iterations:\n%s",
            count($failures), $iterations, json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Property Test: Invalid Payload Rejection
     *
     * Tampered payloads must always be rejected.
     *
     * @test
     */
    public function testInvalidPayloadRejection(): void {
        $iterations = 100;
        $failures   = [];
        $client     = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            $challenge        = $client->createChallenge(rand(1000, 5000), rand(60, 300));
            $manipulationType = rand(1, 4);

            switch ($manipulationType) {
                case 1:
                    $payloadData = ['algorithm' => $challenge['algorithm'], 'challenge' => $challenge['challenge'], 'number' => rand(0, 999999), 'salt' => $challenge['salt'], 'signature' => $challenge['signature']];
                    break;
                case 2:
                    $payloadData = ['algorithm' => $challenge['algorithm'], 'challenge' => $challenge['challenge'], 'number' => 0, 'salt' => $challenge['salt'], 'signature' => bin2hex(random_bytes(32))];
                    break;
                case 3:
                    $payloadData = ['algorithm' => $challenge['algorithm'], 'challenge' => base64_encode(random_bytes(32)), 'number' => 0, 'salt' => $challenge['salt'], 'signature' => $challenge['signature']];
                    break;
                default:
                    $payloadData = ['algorithm' => $challenge['algorithm'], 'challenge' => $challenge['challenge'], 'number' => 0, 'salt' => bin2hex(random_bytes(16)), 'signature' => $challenge['signature']];
            }

            if ($client->verify(base64_encode(json_encode($payloadData)))) {
                $failures[] = ['iteration' => $i, 'manipulation_type' => $manipulationType];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "Invalid payload rejection failed in %d/%d iterations:\n%s",
            count($failures), $iterations, json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAltchaClient(): AltchaClient {
        return $this->createAltchaClientWithConfig([
            'hmac_secret' => 'test-hmac-secret-for-testing-purposes-12345',
        ]);
    }

    private function createAltchaClientWithConfig(array $keys): AltchaClient {
        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')->willReturn($keys);

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObject')
            ->with(AltchaIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $router = $this->createMock(UrlGeneratorInterface::class);

        return new AltchaClient($integrationHelper, $router);
    }

    private function solveChallenge(array $challenge): ?int {
        $altcha    = new \AltchaOrg\Altcha\V1\Altcha('test-hmac-secret-for-testing-purposes-12345');
        $algorithm = \AltchaOrg\Altcha\V1\Hasher\Algorithm::from($challenge['algorithm']);
        $solution  = $altcha->solveChallenge($challenge['challenge'], $challenge['salt'], $algorithm, $challenge['maxNumber']);

        return $solution ? $solution->number : null;
    }

}
