<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Controller;

use MauticPlugin\MauticMultiCaptchaBundle\Controller\CapAssetController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapAssetController
 *
 * Covers the fix for a cross-origin fetch() failure: the Cap widget script
 * loads fine cross-origin via a <script> tag, but it fetches its WASM PoW
 * solver via fetch(), which enforces CORS. This controller serves that
 * binary with an explicit Access-Control-Allow-Origin header instead of
 * relying on the plugin's raw static asset path.
 */
class CapAssetControllerTest extends TestCase {

    /**
     * @test
     */
    public function testWasmActionReturnsBinaryWithCorsHeader(): void {
        $controller = new CapAssetController();
        $response = $controller->wasmAction(new Request());

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('application/wasm', $response->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function testWasmActionServesTheActualBundledFile(): void {
        $controller = new CapAssetController();
        $response = $controller->wasmAction(new Request());

        $expectedPath = realpath(__DIR__ . '/../../Assets/js/cap_wasm_bg.wasm');

        $this->assertEquals($expectedPath, realpath($response->getFile()->getPathname()));
    }

    /**
     * @test
     */
    public function testWasmActionHandlesCorsPreflightRequest(): void {
        $controller = new CapAssetController();
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS']);

        $response = $controller->wasmAction($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
    }

}
