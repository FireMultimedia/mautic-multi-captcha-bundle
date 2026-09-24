<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CapAssetController
 *
 * Serves the self-hosted Cap WASM proof-of-work solver with CORS headers.
 *
 * Mautic forms are typically embedded on a different origin than the Mautic
 * instance itself. The Cap widget script loads fine cross-origin via a plain
 * <script> tag (not subject to CORS), but it fetches its WASM binary via
 * fetch(), which the browser blocks unless the response carries an
 * Access-Control-Allow-Origin header - something a plain static file server
 * does not add for arbitrary plugin assets. No inheritance to avoid
 * Mautic/Symfony DI conflicts, matching ChallengeController's approach.
 */
class CapAssetController
{
    private const WASM_PATH = __DIR__ . '/../Assets/js/cap_wasm_bg.wasm';

    public function wasmAction(Request $request): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->setStatusCode(204);

            return $response;
        }

        if (!is_file(self::WASM_PATH)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse(self::WASM_PATH);
        $response->headers->set('Content-Type', 'application/wasm');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }
}
