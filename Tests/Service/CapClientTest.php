<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Service;

use MauticPlugin\MauticMultiCaptchaBundle\Service\CapClient;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapClient
 *
 * CapClient never talks to a real self-hosted Cap Standalone instance here -
 * the underlying Guzzle client is swapped for a MockHandler so the HTTP
 * contract (URL, method, JSON body) can be verified without a network call.
 */
class CapClientTest extends TestCase {

    /**
     * @test
     */
    public function testVerifyReturnsTrueOnSuccessResponse(): void {
        $client = $this->createCapClient(
            ['server_url' => 'https://cap.example.com', 'secret_key' => 'top-secret'],
            new MockHandler([
                new Response(200, [], json_encode(['success' => true]))
            ])
        );

        $this->assertTrue($client->verify('sitekey:id:secret'));
    }

    /**
     * @test
     */
    public function testVerifyReturnsFalseOnUnsuccessfulResponse(): void {
        $client = $this->createCapClient(
            ['server_url' => 'https://cap.example.com', 'secret_key' => 'top-secret'],
            new MockHandler([
                new Response(200, [], json_encode(['success' => false, 'error' => 'Token not found']))
            ])
        );

        $this->assertFalse($client->verify('sitekey:id:bad-secret'));
    }

    /**
     * @test
     */
    public function testVerifyReturnsFalseWhenSuccessKeyIsMissing(): void {
        $client = $this->createCapClient(
            ['server_url' => 'https://cap.example.com', 'secret_key' => 'top-secret'],
            new MockHandler([
                new Response(200, [], json_encode(['error' => 'Invalid site key or secret']))
            ])
        );

        $this->assertFalse($client->verify('sitekey:id:secret'));
    }

    /**
     * @test
     */
    public function testVerifyPostsToSiteverifyWithTrailingSlashNormalized(): void {
        $history = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['success' => true]))
        ]));
        $handlerStack->push(\GuzzleHttp\Middleware::history($history));

        // server_url configured WITH a trailing slash - CapClient must normalize it
        $client = $this->createCapClient(
            ['server_url' => 'https://cap.example.com/', 'secret_key' => 'top-secret'],
            null,
            $handlerStack
        );

        $client->verify('sitekey:id:secret');

        $this->assertCount(1, $history);

        /** @var Request $request */
        $request = $history[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://cap.example.com/siteverify', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertEquals('top-secret', $body['secret']);
        $this->assertEquals('sitekey:id:secret', $body['response']);
    }

    /**
     * Property Test: verify() forwards secret and response verbatim
     *
     * @test
     */
    public function testVerifyForwardsSecretAndResponseVerbatim(): void {
        $failures = [];

        for ($i = 0; $i < 50; $i++) {
            $secretKey = bin2hex(random_bytes(16));
            $token = bin2hex(random_bytes(8)) . ':' . bin2hex(random_bytes(4)) . ':' . bin2hex(random_bytes(8));
            $success = (bool) rand(0, 1);

            $history = [];
            $handlerStack = HandlerStack::create(new MockHandler([
                new Response(200, [], json_encode(['success' => $success]))
            ]));
            $handlerStack->push(\GuzzleHttp\Middleware::history($history));

            $client = $this->createCapClient(
                ['server_url' => 'https://cap.example.com', 'secret_key' => $secretKey],
                null,
                $handlerStack
            );

            $result = $client->verify($token);

            $request = $history[0]['request'];
            $body = json_decode((string) $request->getBody(), true);

            if ($body['secret'] !== $secretKey || $body['response'] !== $token || $result !== $success) {
                $failures[] = [
                    'iteration' => $i,
                    'expectedSecret' => $secretKey,
                    'actualSecret' => $body['secret'],
                    'expectedToken' => $token,
                    'actualToken' => $body['response'],
                    'expectedResult' => $success,
                    'actualResult' => $result
                ];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "verify() forwarding failed in %d cases:\n%s",
            count($failures),
            json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Helper: Create a CapClient with mocked integration keys and an
     * injectable Guzzle handler/stack for network isolation.
     */
    private function createCapClient(array $keys, ?MockHandler $mockHandler = null, ?HandlerStack $handlerStack = null): CapClient {
        $integrationHelper = $this->createMock(IntegrationHelper::class);

        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')->willReturn($keys);

        $integrationHelper->method('getIntegrationObject')
            ->with(CapIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $stack = $handlerStack ?? HandlerStack::create($mockHandler);
        $httpClient = new GuzzleClient(['handler' => $stack]);

        return new CapClient($integrationHelper, $httpClient);
    }

}
