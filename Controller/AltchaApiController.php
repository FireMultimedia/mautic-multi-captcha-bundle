<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * <h1>Class AltchaApiController</h1>
 * 
 * API Controller for ALTCHA challenge generation
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Controller
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaApiController extends CommonController
{
    private AltchaClient $altchaClient;

    public function __construct(AltchaClient $altchaClient)
    {
        $this->altchaClient = $altchaClient;
    }

    /**
     * <h2>generateChallengeAction</h2>
     * 
     * API endpoint to generate a fresh ALTCHA challenge as JSON
     * Uses default configuration values for security
     * 
     * @param Request $request
     * 
     * @return JsonResponse
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