<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Controller;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AltchaApiController
 * 
 * Simple API Controller for ALTCHA challenge generation
 * No inheritance to avoid Mautic/Symfony DI conflicts
 */
class AltchaApiController
{
    private AltchaClient $altchaClient;

    public function __construct(AltchaClient $altchaClient)
    {
        $this->altchaClient = $altchaClient;
    }

    /**
     * API endpoint to generate a fresh ALTCHA challenge as JSON
     * Uses default configuration values for security
     */
    public function generateChallengeAction(Request $request): JsonResponse
    {
        // Handle CORS preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-Altcha-Spam-Filter, Cache-Control');
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->setStatusCode(204);
            return $response;
        }
        
        try {
            // Use secure default values - no user input accepted for security
            $maxNumber = 100000;  // Default difficulty
            $expires = 60;       // 1 minute default expiry
            
            // Generate challenge
            $challengeData = $this->altchaClient->createChallenge($maxNumber, $expires);
            
            if (empty($challengeData)) {
                return new JsonResponse([
                    'error' => 'Failed to generate challenge'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            // Return challenge data directly as expected by ALTCHA widget
            $response = new JsonResponse($challengeData);
            
            // Add CORS headers to response
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-Altcha-Spam-Filter, Cache-Control');
            
            return $response;
            
        } catch (\RuntimeException $e) {
            $response = new JsonResponse([
                'error' => 'ALTCHA not configured: ' . $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
            
            // Add CORS headers to error response
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-Altcha-Spam-Filter, Cache-Control');
            
            return $response;
            
        } catch (\Exception $e) {
            $response = new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
            // Add CORS headers to error response
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-Altcha-Spam-Filter, Cache-Control');
            
            return $response;
        }
    }
}