<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Twig;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * <h1>Class AltchaExtension</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Twig
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaExtension extends AbstractExtension
{
    public function __construct(
        private readonly AltchaClient $altchaClient
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('altcha_generate_challenge', [$this, 'generateChallenge']),
        ];
    }

    /**
     * <h2>generateChallenge</h2>
     * 
     * Generates a fresh Altcha challenge for use in templates
     *
     * @param int $maxNumber Maximum random number for the challenge
     * @param int $expires Challenge expiration time in seconds
     * 
     * @return array Challenge data or empty array on failure
     */
    public function generateChallenge(int $maxNumber = 50000, int $expires = 120): array
    {
        try {
            // Validate parameters
            $maxNumber = max(1000, min(1000000, $maxNumber));
            $expires = max(10, min(300, $expires));
            
            return $this->altchaClient->createChallenge($maxNumber, $expires);
        } catch (\Exception $e) {
            // Log error but don't break the template
            error_log('Altcha challenge generation failed in Twig extension: ' . $e->getMessage());
            return [];
        }
    }
}