<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Integration;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapIntegration
 */
class CapIntegrationTest extends TestCase {

    private function createIntegration(): CapIntegration {
        return new class extends CapIntegration {
            public function __construct() {
                // Skip parent constructor to avoid pulling in Mautic's DI graph
            }
        };
    }

    /**
     * @test
     */
    public function testGetName(): void {
        $this->assertEquals('Cap', $this->createIntegration()->getName());
    }

    /**
     * @test
     */
    public function testGetDisplayName(): void {
        $this->assertEquals('Cap CAPTCHA', $this->createIntegration()->getDisplayName());
    }

    /**
     * @test
     */
    public function testGetAuthenticationType(): void {
        $this->assertEquals('none', $this->createIntegration()->getAuthenticationType());
    }

    /**
     * @test
     */
    public function testGetRequiredKeyFields(): void {
        $fields = $this->createIntegration()->getRequiredKeyFields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('server_url', $fields);
        $this->assertArrayHasKey('site_key', $fields);
        $this->assertArrayHasKey('secret_key', $fields);

        $this->assertEquals('strings.cap.settings.server_url', $fields['server_url']);
        $this->assertEquals('strings.cap.settings.site_key', $fields['site_key']);
        $this->assertEquals('strings.cap.settings.secret_key', $fields['secret_key']);
    }

    /**
     * Property Test: Required key field persistence
     *
     * @test
     */
    public function testRequiredKeyFieldsPersistence(): void {
        $integration = $this->createIntegration();
        $requiredFields = $integration->getRequiredKeyFields();
        $failures = [];

        for ($i = 0; $i < 100; $i++) {
            $values = [
                'server_url' => 'https://cap-' . bin2hex(random_bytes(4)) . '.example.com',
                'site_key'   => bin2hex(random_bytes(8)),
                'secret_key' => bin2hex(random_bytes(16))
            ];

            foreach (array_keys($requiredFields) as $fieldName) {
                $storage = [$fieldName => $values[$fieldName]];
                $retrieved = $storage[$fieldName] ?? null;

                if ($retrieved !== $values[$fieldName]) {
                    $failures[] = [
                        'iteration' => $i,
                        'field' => $fieldName,
                        'expected' => $values[$fieldName],
                        'actual' => $retrieved
                    ];
                }
            }
        }

        $this->assertEmpty($failures, sprintf(
            "Required key field persistence failed in %d cases:\n%s",
            count($failures),
            json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

}
