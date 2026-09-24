<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Controller;

use MauticPlugin\MauticMultiCaptchaBundle\Controller\ChallengeController;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ChallengeController
 */
class AltchaApiControllerTest extends TestCase {

    /**
     * Test: OPTIONS preflight returns 204 with correct CORS headers
     *
     * @test
     */
    public function testApiHandlesCorsPreflightRequest(): void {
        $client = $this->createMock(AltchaClient::class);
        $client->expects($this->never())->method('createChallengeForComplexity');

        $controller = new ChallengeController($client);
        $request    = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS']);

        $response = $controller->__invoke($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->headers->get('Access-Control-Allow-Headers'));
    }

    /**
     * Test: GET with default params calls createChallengeForComplexity with 'medium' + default expire
     *
     * @test
     */
    public function testApiUsesDefaultComplexityAndExpire(): void {
        $client = $this->createMock(AltchaClient::class);
        $client->expects($this->once())
            ->method('createChallengeForComplexity')
            ->with('medium', AltchaClient::DEFAULT_EXPIRE_SECONDS)
            ->willReturn([
                'algorithm' => 'SHA-256',
                'challenge' => 'test',
                'salt'      => 'test',
                'signature' => 'test',
                'maxNumber' => 100000,
            ]);

        $controller = new ChallengeController($client);
        $response   = $controller->__invoke(new Request());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test: GET with explicit complexity and expire passes them through
     *
     * @test
     */
    public function testApiPassesComplexityAndExpireFromQueryString(): void {
        $client = $this->createMock(AltchaClient::class);
        $client->expects($this->once())
            ->method('createChallengeForComplexity')
            ->with('high', 300)
            ->willReturn([
                'algorithm' => 'SHA-256',
                'challenge' => 'test',
                'salt'      => 'test',
                'signature' => 'test',
                'maxNumber' => 400000,
            ]);

        $controller = new ChallengeController($client);
        $request    = new Request(['complexity' => 'high', 'expire' => '300']);

        $response = $controller->__invoke($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test: returns 503 when client is not configured (createChallengeForComplexity returns null)
     *
     * @test
     */
    public function testApiReturnsErrorWhenNotConfigured(): void {
        $client = $this->createMock(AltchaClient::class);
        $client->method('createChallengeForComplexity')->willReturn(null);

        $controller = new ChallengeController($client);
        $response   = $controller->__invoke(new Request());

        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    /**
     * Test: successful response includes CORS headers and no-store cache control
     *
     * @test
     */
    public function testApiIncludesCorsAndCacheHeaders(): void {
        $client = $this->createMock(AltchaClient::class);
        $client->method('createChallengeForComplexity')->willReturn([
            'algorithm' => 'SHA-256',
            'challenge' => 'test',
            'salt'      => 'test',
            'signature' => 'test',
            'maxNumber' => 100000,
        ]);

        $controller = new ChallengeController($client);
        $response   = $controller->__invoke(new Request());

        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * Property test: expire is clamped between 30 and 3600 regardless of query param
     *
     * @test
     */
    public function testExpireIsClampedToSafeBounds(): void {
        $cases = [
            ['input' => '0',    'expected_min' => 30,   'expected_max' => 30],
            ['input' => '29',   'expected_min' => 30,   'expected_max' => 30],
            ['input' => '600',  'expected_min' => 600,  'expected_max' => 600],
            ['input' => '3600', 'expected_min' => 3600, 'expected_max' => 3600],
            ['input' => '9999', 'expected_min' => 3600, 'expected_max' => 3600],
        ];

        foreach ($cases as $case) {
            $capturedExpire = null;

            $client = $this->createMock(AltchaClient::class);
            $client->method('createChallengeForComplexity')
                ->willReturnCallback(function(string $complexity, int $expire) use (&$capturedExpire) {
                    $capturedExpire = $expire;
                    return ['algorithm' => 'SHA-256', 'challenge' => 'x', 'salt' => 'x', 'signature' => 'x', 'maxNumber' => 1];
                });

            $controller = new ChallengeController($client);
            $controller->__invoke(new Request(['expire' => $case['input']]));

            $this->assertGreaterThanOrEqual($case['expected_min'], $capturedExpire);
            $this->assertLessThanOrEqual($case['expected_max'], $capturedExpire);
        }
    }

}
