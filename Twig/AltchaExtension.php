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
     * Uses fixed, secure parameters to prevent manipulation
     *
     * @return array Challenge data or empty array on failure
     */
    public function generateChallenge(): array
    {
        try {
            // Use fixed, secure parameters - not user-configurable for security
            $maxNumber = 50000; // Fixed complexity
            $expires = 120;     // Fixed 2-minute expiration
            
            return $this->altchaClient->createChallenge($maxNumber, $expires);
        } catch (\Exception $e) {
            // Log error but don't break the template
            error_log('Altcha challenge generation failed in Twig extension: ' . $e->getMessage());
            return [];
        }
    }
}