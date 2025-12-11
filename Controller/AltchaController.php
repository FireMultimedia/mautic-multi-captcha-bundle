<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * <h1>Class AltchaController</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Controller
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaController extends CommonController
{
    /**
     * <h2>generateChallenge</h2>
     * 
     * Generates a new Altcha challenge via AJAX
     */
    public function generateChallengeAction(Request $request): JsonResponse
    {
        try {
            // Get AltchaClient service
            $altchaClient = $this->get('mautic.altcha.service.altcha_client');
            
            // Get parameters from request or use defaults
            $maxNumber = (int) $request->query->get('maxNumber', 50000);
            $expires = (int) $request->query->get('expires', 120);
            
            // Validate parameters
            $maxNumber = max(1000, min(1000000, $maxNumber));
            $expires = max(10, min(300, $expires));
            
            // Generate challenge
            $challenge = $altchaClient->createChallenge($maxNumber, $expires);
            
            if (empty($challenge)) {
                return new JsonResponse(['error' => 'Failed to generate challenge'], 500);
            }
            
            return new JsonResponse(['challenge' => $challenge]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Challenge generation failed'], 500);
        }
    }
}