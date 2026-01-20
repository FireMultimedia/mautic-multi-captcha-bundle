<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Controller;

use MauticPlugin\MauticMultiCaptchaBundle\Controller\AltchaApiController;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AltchaApiController
 */
class AltchaApiControllerTest extends TestCase {

    /**
     * Test: API endpoint uses configured maxNumber from global settings
     * 
     * **Feature: ALTCHA-API, Configuration Usage**
     * **Validates: API uses global configuration values**
     * 
     * When maxNumber is configured globally, the API endpoint should use
     * this value when generating challenges.
     * 
     * @test
     */
    public function testApiUsesConfiguredMaxNumber(): void {
        $configuredMaxNumber = 75000;
        $configuredExpires = 180;

        // Mock AltchaClient
        $client = $this->createMock(AltchaClient::class);
        
        // Expect getConfiguration to be called and return configured values
        $client->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'maxNumber' => $configuredMaxNumber,
                'expires' => $configuredExpires
            ]);
        
        // Expect createChallenge to be called with configured values
        $client->expects($this->once())
            ->method('createChallenge')
            ->with($configuredMaxNumber, $configuredExpires)
            ->willReturn([
                'algorithm' => 'SHA-256',
                'challenge' => 'test-challenge',
                'salt' => 'test-salt',
                'signature' => 'test-signature',
                'maxnumber' => $configuredMaxNumber
            ]);

        $controller = new AltchaApiController($client);
        $request = new Request();

        $response = $controller->generateChallengeAction($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test: API endpoint uses default values when not configured
     * 
     * **Feature: ALTCHA-API, Default Fallback**
     * **Validates: API uses default values when configuration is missing**
     * 
     * When maxNumber and expires are not configured, the API endpoint should
     * use default fallback values (50000 and 120).
     * 
     * @test
     */
    public function testApiUsesDefaultValuesWhenNotConfigured(): void {
        $defaultMaxNumber = 50000;
        $defaultExpires = 120;

        // Mock AltchaClient
        $client = $this->createMock(AltchaClient::class);
        
        // Expect getConfiguration to be called and return null values
        $client->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'maxNumber' => null,
                'expires' => null
            ]);
        
        // Expect createChallenge to be called with default values
        $client->expects($this->once())
            ->method('createChallenge')
            ->with($defaultMaxNumber, $defaultExpires)
            ->willReturn([
                'algorithm' => 'SHA-256',
                'challenge' => 'test-challenge',
                'salt' => 'test-salt',
                'signature' => 'test-signature',
                'maxnumber' => $defaultMaxNumber
            ]);

        $controller = new AltchaApiController($client);
        $request = new Request();

        $response = $controller->generateChallengeAction($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test: API endpoint handles CORS preflight requests
     * 
     * **Feature: ALTCHA-API, CORS Support**
     * **Validates: API handles OPTIONS requests correctly**
     * 
     * When an OPTIONS request is received, the API should return
     * appropriate CORS headers with 204 status.
     * 
     * @test
     */
    public function testApiHandlesCorsPreflightRequest(): void {
        // Mock AltchaClient (should not be called for OPTIONS)
        $client = $this->createMock(AltchaClient::class);
        $client->expects($this->never())
            ->method('getConfiguration');
        $client->expects($this->never())
            ->method('createChallenge');

        $controller = new AltchaApiController($client);
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS']);

        $response = $controller->generateChallengeAction($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
    }

    /**
     * Test: API endpoint returns error when challenge generation fails
     * 
     * **Feature: ALTCHA-API, Error Handling**
     * **Validates: API handles challenge generation failures**
     * 
     * When challenge generation fails, the API should return
     * an appropriate error response.
     * 
     * @test
     */
    public function testApiReturnsErrorWhenChallengeGenerationFails(): void {
        // Mock AltchaClient
        $client = $this->createMock(AltchaClient::class);
        
        $client->method('getConfiguration')
            ->willReturn(['maxNumber' => 50000, 'expires' => 120]);
        
        // Simulate challenge generation failure
        $client->method('createChallenge')
            ->willReturn([]);

        $controller = new AltchaApiController($client);
        $request = new Request();

        $response = $controller->generateChallengeAction($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Failed to generate challenge', $data['error']);
    }

    /**
     * Test: API endpoint includes CORS headers in response
     * 
     * **Feature: ALTCHA-API, CORS Support**
     * **Validates: API includes CORS headers in successful responses**
     * 
     * When a challenge is successfully generated, the response should
     * include appropriate CORS headers.
     * 
     * @test
     */
    public function testApiIncludesCorsHeadersInResponse(): void {
        // Mock AltchaClient
        $client = $this->createMock(AltchaClient::class);
        
        $client->method('getConfiguration')
            ->willReturn(['maxNumber' => 50000, 'expires' => 120]);
        
        $client->method('createChallenge')
            ->willReturn([
                'algorithm' => 'SHA-256',
                'challenge' => 'test-challenge',
                'salt' => 'test-salt',
                'signature' => 'test-signature',
                'maxnumber' => 50000
            ]);

        $controller = new AltchaApiController($client);
        $request = new Request();

        $response = $controller->generateChallengeAction($request);

        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
    }

    /**
     * Property Test: API uses various configured values correctly
     * 
     * **Feature: ALTCHA-API, Configuration Flexibility**
     * **Validates: API correctly uses different configuration values**
     * 
     * For various valid configuration values, the API should correctly
     * pass them to the challenge generation.
     * 
     * Generator: Random maxNumber (1000-1000000), expires (10-300)
     * Iterations: 50
     * 
     * @test
     */
    public function testApiUsesVariousConfiguredValuesCorrectly(): void {
        $iterations = 50;
        $failures = [];

        for ($i = 0; $i < $iterations; $i++) {
            $maxNumber = rand(1000, 1000000);
            $expires = rand(10, 300);

            // Mock AltchaClient
            $client = $this->createMock(AltchaClient::class);
            
            $client->method('getConfiguration')
                ->willReturn([
                    'maxNumber' => $maxNumber,
                    'expires' => $expires
                ]);
            
            // Verify createChallenge is called with correct parameters
            $client->expects($this->once())
                ->method('createChallenge')
                ->with($maxNumber, $expires)
                ->willReturn([
                    'algorithm' => 'SHA-256',
                    'challenge' => 'test-challenge',
                    'salt' => 'test-salt',
                    'signature' => 'test-signature',
                    'maxnumber' => $maxNumber
                ]);

            $controller = new AltchaApiController($client);
            $request = new Request();

            try {
                $response = $controller->generateChallengeAction($request);
                
                if ($response->getStatusCode() !== Response::HTTP_OK) {
                    $failures[] = [
                        'iteration' => $i,
                        'maxNumber' => $maxNumber,
                        'expires' => $expires,
                        'status' => $response->getStatusCode()
                    ];
                }
            } catch (\Exception $e) {
                $failures[] = [
                    'iteration' => $i,
                    'maxNumber' => $maxNumber,
                    'expires' => $expires,
                    'exception' => $e->getMessage()
                ];
            }
        }

        $this->assertEmpty(
            $failures,
            sprintf(
                "API configuration usage failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

}
