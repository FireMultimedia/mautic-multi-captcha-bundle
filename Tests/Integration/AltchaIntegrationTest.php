<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Integration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AltchaIntegration
 */
class AltchaIntegrationTest extends TestCase {

    /**
     * Test: getName returns the integration name constant
     *
     * @test
     */
    public function testGetName(): void {
        $integration = $this->makeIntegration();
        $this->assertEquals('Altcha', $integration->getName());
    }

    /**
     * Test: getDisplayName returns the human-readable name
     *
     * @test
     */
    public function testGetDisplayName(): void {
        $integration = $this->makeIntegration();
        $this->assertEquals('ALTCHA', $integration->getDisplayName());
    }

    /**
     * Test: getAuthenticationType is "none" (self-hosted, no third-party OAuth)
     *
     * @test
     */
    public function testGetAuthenticationType(): void {
        $integration = $this->makeIntegration();
        $this->assertEquals('none', $integration->getAuthenticationType());
    }

    /**
     * Test: getRequiredKeyFields intentionally returns [] so that self-hosted and
     * Sentinel fields can be filled independently without Mautic forcing all of them.
     *
     * @test
     */
    public function testGetRequiredKeyFields(): void {
        $integration = $this->makeIntegration();
        $this->assertSame([], $integration->getRequiredKeyFields());
    }

    /**
     * Test: getSecretKeys includes hmac_secret and sentinel_api_secret so Mautic
     * masks them in the UI and encrypts them at rest.
     *
     * @test
     */
    public function testGetSecretKeys(): void {
        $integration = $this->makeIntegration();
        $secretKeys  = $integration->getSecretKeys();

        $this->assertContains('hmac_secret', $secretKeys);
        $this->assertContains('sentinel_api_secret', $secretKeys);
    }

    /**
     * Property Test: isConfigured returns true for any non-empty hmac_secret
     *
     * Generator: random alphanumeric strings (20-64 chars)
     * Iterations: 100
     *
     * @test
     */
    public function testHmacKeyPersistence(): void {
        $iterations = 100;
        $failures   = [];

        for ($i = 0; $i < $iterations; $i++) {
            $secret = $this->randomAlphanumeric(rand(20, 64));

            $integration = $this->makeIntegrationWithKeys(['hmac_secret' => $secret]);

            if (!$integration->isConfigured()) {
                $failures[] = ['iteration' => $i, 'secret_length' => strlen($secret)];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "isConfigured() returned false for a valid hmac_secret in %d/%d iterations:\n%s",
            count($failures), $iterations, json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Test: isConfigured returns false when no credentials are set
     *
     * @test
     */
    public function testIsNotConfiguredWithEmptyKeys(): void {
        $integration = $this->makeIntegrationWithKeys([]);
        $this->assertFalse($integration->isConfigured());
    }

    /**
     * Test: isConfigured returns true when all three Sentinel fields are present
     *
     * @test
     */
    public function testIsConfiguredWithSentinelCredentials(): void {
        $integration = $this->makeIntegrationWithKeys([
            'sentinel_domain'     => 'https://sentinel.example.com',
            'sentinel_api_key'    => 'key_abc',
            'sentinel_api_secret' => 'secret_xyz',
        ]);

        $this->assertTrue($integration->isConfigured());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Instantiate without parent constructor dependencies. */
    private function makeIntegration(): AltchaIntegration {
        return new class extends AltchaIntegration {
            public function __construct() {}
        };
    }

    /** Instantiate and stub getKeys() to return the given array. */
    private function makeIntegrationWithKeys(array $keys): AltchaIntegration {
        $integration = $this->getMockBuilder(AltchaIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getKeys'])
            ->getMock();
        $integration->method('getKeys')->willReturn($keys);
        return $integration;
    }

    private function randomAlphanumeric(int $length): string {
        $chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $result;
    }

}
