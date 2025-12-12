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
        try {
            // Use secure default values - no user input accepted for security
            $maxNumber = 100000;  // Default difficulty
            $expires = 300;       // 5 minutes default expiry
            
            // Generate challenge
            $challengeData = $this->altchaClient->createChallenge($maxNumber, $expires);
            
            if (empty($challengeData)) {
                return new JsonResponse([
                    'error' => 'Failed to generate challenge'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            // Return challenge data directly as expected by ALTCHA widget
            return new JsonResponse($challengeData);
            
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'error' => 'ALTCHA not configured: ' . $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}