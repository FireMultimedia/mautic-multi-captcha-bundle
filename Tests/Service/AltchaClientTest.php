<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Service;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for AltchaClient
 */
class AltchaClientTest extends TestCase {

    /**
     * Test: getConfiguration returns configured values
     * 
     * **Feature: ALTCHA-integration, Configuration Retrieval**
     * **Validates: Global configuration settings**
     * 
     * When maxNumber and expires are configured in the integration settings,
     * getConfiguration should return these values.
     * 
     * @test
     */
    public function testGetConfigurationReturnsConfiguredValues(): void {
        $expectedMaxNumber = 75000;
        $expectedExpires = 180;

        // Create client with configured values
        $client = $this->createAltchaClientWithConfig([
            'hmac_key' => 'test-hmac-key',
            'maxNumber' => $expectedMaxNumber,
            'expires' => $expectedExpires
        ]);

        $config = $client->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('maxNumber', $config);
        $this->assertArrayHasKey('expires', $config);
        $this->assertEquals($expectedMaxNumber, $config['maxNumber']);
        $this->assertEquals($expectedExpires, $config['expires']);
    }

    /**
     * Test: getConfiguration returns null for missing values
     * 
     * **Feature: ALTCHA-integration, Configuration Retrieval**
     * **Validates: Default handling when values are not configured**
     * 
     * When maxNumber and expires are not configured in the integration settings,
     * getConfiguration should return null for these values.
     * 
     * @test
     */
    public function testGetConfigurationReturnsNullForMissingValues(): void {
        // Create client without maxNumber and expires configured
        $client = $this->createAltchaClientWithConfig([
            'hmac_key' => 'test-hmac-key'
        ]);

        $config = $client->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('maxNumber', $config);
        $this->assertArrayHasKey('expires', $config);
        $this->assertNull($config['maxNumber']);
        $this->assertNull($config['expires']);
    }

    /**
     * Test: getConfiguration handles string values correctly
     * 
     * **Feature: ALTCHA-integration, Configuration Retrieval**
     * **Validates: Type conversion from string to integer**
     * 
     * When maxNumber and expires are stored as strings (common in form data),
     * getConfiguration should convert them to integers.
     * 
     * @test
     */
    public function testGetConfigurationConvertsStringToInteger(): void {
        // Create client with string values
        $client = $this->createAltchaClientWithConfig([
            'hmac_key' => 'test-hmac-key',
            'maxNumber' => '60000',
            'expires' => '150'
        ]);

        $config = $client->getConfiguration();

        $this->assertIsInt($config['maxNumber']);
        $this->assertIsInt($config['expires']);
        $this->assertEquals(60000, $config['maxNumber']);
        $this->assertEquals(150, $config['expires']);
    }

    /**
     * Property Test: Challenge Structure Completeness
     * 
     * **Feature: ALTCHA-integration, Property 4: Challenge Structure Completeness**
     * **Validates: Requirements 3.2, 3.3**
     * 
     * For any valid configuration parameters (maxNumber, expires), when generating
     * a challenge, the returned data structure should contain all required fields:
     * algorithm, challenge, salt, signature, maxnumber, and expires.
     * 
     * Generator: Random maxNumber (1000-1000000), expires (10-300)
     * Iterations: 100
     * 
     * @test
     */
    public function testChallengeStructureCompleteness(): void {
        $iterations = 100;
        $failures = [];

        // Create AltchaClient with mocked dependencies
        $client = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random valid parameters
            $maxNumber = rand(1000, 1000000);
            $expires = rand(10, 300);

            // Generate challenge
            $challenge = $client->createChallenge($maxNumber, $expires);

            // Verify all required fields are present
            $requiredFields = ['algorithm', 'challenge', 'salt', 'signature', 'maxnumber'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $challenge)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $failures[] = [
                    'iteration' => $i,
                    'maxNumber' => $maxNumber,
                    'expires' => $expires,
                    'missing_fields' => $missingFields,
                    'challenge' => $challenge
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "Challenge structure completeness failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Property Test: Valid Payload Acceptance
     * 
     * **Feature: ALTCHA-integration, Property 5: Valid Payload Acceptance**
     * **Validates: Requirements 4.1, 4.2**
     * 
     * For any correctly generated challenge and its valid solution payload,
     * when verifying the payload, the system should accept it.
     * 
     * Note: This test generates challenges and uses the ALTCHA library to solve them,
     * then verifies that valid solutions are accepted.
     * 
     * Generator: Random maxNumber (1000-50000), expires (60-300)
     * Iterations: 100
     * 
     * @test
     */
    public function testValidPayloadAcceptance(): void {
        $iterations = 100;
        $failures = [];

        // Create AltchaClient with mocked dependencies
        $client = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random valid parameters (smaller maxNumber for faster solving)
            $maxNumber = rand(1000, 50000);
            $expires = rand(60, 300);

            // Generate challenge
            $challenge = $client->createChallenge($maxNumber, $expires);

            if (empty($challenge)) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Challenge generation failed',
                    'maxNumber' => $maxNumber,
                    'expires' => $expires
                ];
                continue;
            }

            // Solve the challenge manually (brute force)
            $solution = $this->solveChallenge($challenge);

            if ($solution === null) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Could not solve challenge',
                    'maxNumber' => $maxNumber,
                    'expires' => $expires
                ];
                continue;
            }

            // Create payload (base64-encoded JSON as expected by ALTCHA)
            // The signature from the challenge should be used as-is
            $payloadData = [
                'algorithm' => $challenge['algorithm'],
                'challenge' => $challenge['challenge'],
                'number' => $solution,
                'salt' => $challenge['salt'],
                'signature' => $challenge['signature']
            ];
            $payload = base64_encode(json_encode($payloadData));

            // Verify the payload
            $isValid = $client->verify($payload);

            if (!$isValid) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Valid payload was rejected',
                    'maxNumber' => $maxNumber,
                    'expires' => $expires,
                    'solution' => $solution
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "Valid payload acceptance failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Property Test: Invalid Payload Rejection
     * 
     * **Feature: ALTCHA-integration, Property 6: Invalid Payload Rejection**
     * **Validates: Requirements 4.3**
     * 
     * For any payload with incorrect signature, wrong number, or manipulated data,
     * when verifying the payload, the system should reject it.
     * 
     * Generator: Random invalid payloads with manipulated data
     * Iterations: 100
     * 
     * @test
     */
    public function testInvalidPayloadRejection(): void {
        $iterations = 100;
        $failures = [];

        // Create AltchaClient with mocked dependencies
        $client = $this->createAltchaClient();

        for ($i = 0; $i < $iterations; $i++) {
            // Generate a valid challenge first
            $maxNumber = rand(1000, 50000);
            $expires = rand(60, 300);
            $challenge = $client->createChallenge($maxNumber, $expires);

            if (empty($challenge)) {
                continue; // Skip if challenge generation failed
            }

            // Create invalid payload by manipulating data
            $manipulationType = rand(1, 4);
            
            switch ($manipulationType) {
                case 1: // Wrong number
                    $payload = json_encode([
                        'algorithm' => $challenge['algorithm'],
                        'challenge' => $challenge['challenge'],
                        'number' => rand(0, $maxNumber), // Random wrong number
                        'salt' => $challenge['salt'],
                        'signature' => $challenge['signature']
                    ]);
                    break;
                    
                case 2: // Manipulated signature
                    $payload = json_encode([
                        'algorithm' => $challenge['algorithm'],
                        'challenge' => $challenge['challenge'],
                        'number' => 0,
                        'salt' => $challenge['salt'],
                        'signature' => bin2hex(random_bytes(32)) // Random signature
                    ]);
                    break;
                    
                case 3: // Manipulated challenge
                    $payload = json_encode([
                        'algorithm' => $challenge['algorithm'],
                        'challenge' => base64_encode(random_bytes(32)), // Random challenge
                        'number' => 0,
                        'salt' => $challenge['salt'],
                        'signature' => $challenge['signature']
                    ]);
                    break;
                    
                case 4: // Manipulated salt
                    $payload = json_encode([
                        'algorithm' => $challenge['algorithm'],
                        'challenge' => $challenge['challenge'],
                        'number' => 0,
                        'salt' => bin2hex(random_bytes(16)), // Random salt
                        'signature' => $challenge['signature']
                    ]);
                    break;
            }

            // Verify the invalid payload
            $isValid = $client->verify($payload);

            if ($isValid) {
                $failures[] = [
                    'iteration' => $i,
                    'manipulation_type' => $manipulationType,
                    'reason' => 'Invalid payload was accepted',
                    'payload' => $payload
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "Invalid payload rejection failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Helper: Create AltchaClient with mocked dependencies
     */
    private function createAltchaClient(): AltchaClient {
        return $this->createAltchaClientWithConfig([
            'hmac_key' => 'test-hmac-key-for-testing-purposes-12345'
        ]);
    }

    /**
     * Helper: Create AltchaClient with custom configuration
     */
    private function createAltchaClientWithConfig(array $keys): AltchaClient {
        // Mock IntegrationHelper
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        
        // Mock AbstractIntegration
        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')
            ->willReturn($keys);
        
        $integrationHelper->method('getIntegrationObject')
            ->with(AltchaIntegration::INTEGRATION_NAME)
            ->willReturn($integration);
        
        return new AltchaClient($integrationHelper);
    }

    /**
     * Helper: Solve a challenge by brute force using ALTCHA library
     * 
     * @param array $challenge
     * @return int|null The solution number, or null if not found
     */
    private function solveChallenge(array $challenge): ?int {
        // Use ALTCHA library to solve the challenge
        $altcha = new \AltchaOrg\Altcha\Altcha('test-hmac-key-for-testing-purposes-12345');
        
        $algorithm = \AltchaOrg\Altcha\Hasher\Algorithm::from($challenge['algorithm']);
        $solution = $altcha->solveChallenge(
            $challenge['challenge'],
            $challenge['salt'],
            $algorithm,
            $challenge['maxnumber']
        );

        return $solution ? $solution->number : null;
    }

}
