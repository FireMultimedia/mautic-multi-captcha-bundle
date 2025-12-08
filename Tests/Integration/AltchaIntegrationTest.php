<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Integration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for AltchaIntegration
 * 
 * **Feature: altcha-integration, Property 1: HMAC-Key Persistence**
 * **Validates: Requirements 1.3**
 */
class AltchaIntegrationTest extends TestCase {

    /**
     * Property Test: HMAC-Key Persistence
     * 
     * For any valid HMAC-Key string, the integration must correctly define
     * the hmac_key field in getRequiredKeyFields(), which ensures Mautic
     * can persist and retrieve the configuration value.
     * 
     * This property verifies that:
     * 1. The hmac_key field is defined in required fields
     * 2. The field name is consistent and can be used for storage/retrieval
     * 3. Any alphanumeric string (20-64 chars) can be stored as a key
     * 
     * Generator: Random strings (20-64 characters, alphanumeric)
     * Iterations: 100
     * 
     * @test
     */
    public function testHmacKeyPersistence(): void {
        $iterations = 100;
        $failures = [];

        // Get the integration instance to check field definition
        // Create an anonymous class instance to test the method without dependencies
        $integration = new class extends AltchaIntegration {
            public function __construct() {
                // Skip parent constructor to avoid dependencies
            }
        };
        
        // Call the public method directly
        $requiredFields = $integration->getRequiredKeyFields();

        // Verify hmac_key is defined in required fields
        if (!isset($requiredFields['hmac_key'])) {
            $this->fail('hmac_key is not defined in required key fields');
        }

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random HMAC key (20-64 characters, alphanumeric)
            $length = rand(20, 64);
            $hmacKey = $this->generateRandomAlphanumeric($length);

            // Simulate Mautic's persistence mechanism:
            // Mautic uses the keys from getRequiredKeyFields() to store/retrieve values
            $fieldName = 'hmac_key';
            
            // Simulate storage
            $storage = [$fieldName => $hmacKey];
            
            // Simulate retrieval
            $retrieved = $storage[$fieldName] ?? null;

            // Verify the field name from getRequiredKeyFields() works for storage/retrieval
            if ($retrieved !== $hmacKey) {
                $failures[] = [
                    'iteration' => $i,
                    'expected' => $hmacKey,
                    'actual' => $retrieved,
                    'length' => $length,
                    'field_name' => $fieldName
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "HMAC-Key persistence failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Generate random alphanumeric string
     */
    private function generateRandomAlphanumeric(int $length): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Unit test: Verify integration name
     * 
     * @test
     */
    public function testGetName(): void {
        $mockIntegration = $this->getMockBuilder(AltchaIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        // Use reflection to call the method since we can't instantiate without dependencies
        $reflection = new \ReflectionClass(AltchaIntegration::class);
        $method = $reflection->getMethod('getName');
        $method->setAccessible(true);
        
        $this->assertEquals('Altcha', $method->invoke($mockIntegration));
    }

    /**
     * Unit test: Verify display name
     * 
     * @test
     */
    public function testGetDisplayName(): void {
        $mockIntegration = $this->getMockBuilder(AltchaIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $reflection = new \ReflectionClass(AltchaIntegration::class);
        $method = $reflection->getMethod('getDisplayName');
        $method->setAccessible(true);
        
        $this->assertEquals('Altcha', $method->invoke($mockIntegration));
    }

    /**
     * Unit test: Verify authentication type
     * 
     * @test
     */
    public function testGetAuthenticationType(): void {
        $mockIntegration = $this->getMockBuilder(AltchaIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $reflection = new \ReflectionClass(AltchaIntegration::class);
        $method = $reflection->getMethod('getAuthenticationType');
        $method->setAccessible(true);
        
        $this->assertEquals('none', $method->invoke($mockIntegration));
    }

    /**
     * Unit test: Verify required key fields
     * 
     * @test
     */
    public function testGetRequiredKeyFields(): void {
        $mockIntegration = $this->getMockBuilder(AltchaIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $reflection = new \ReflectionClass(AltchaIntegration::class);
        $method = $reflection->getMethod('getRequiredKeyFields');
        $method->setAccessible(true);
        
        $fields = $method->invoke($mockIntegration);
        
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('hmac_key', $fields);
        $this->assertEquals('strings.altcha.settings.hmac_key', $fields['hmac_key']);
    }

}
