<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;

/**
 * <h1>Class AltchaExtension</h1>
 *
 * Exposes altcha_challenge() to Twig so the field template can build the
 * <altcha-widget>'s "challengeurl" attribute. Always returns a URL, never
 * inline JSON - the widget fetches a fresh challenge live on each page load,
 * which sidesteps Mautic's form HTML caching (forms.cached_html) that would
 * otherwise cause stale/expired challenges to be served to every visitor.
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Twig
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaExtension extends AbstractExtension {

    public function __construct(private readonly AltchaClient $altchaClient) {

    }

    /** {@inheritDoc} */
    public function getFunctions(): array {
        return [
            new TwigFunction("altcha_challenge", [$this, "createChallenge"])
        ];
    }

    /**
     * @param string $complexity    One of "low", "medium", "high". Ignored in Sentinel mode.
     * @param int    $expireSeconds Ignored in Sentinel mode.
     *
     * @return string|null Null when neither self-hosted nor Sentinel credentials are configured.
     */
    public function createChallenge(string $complexity = "medium", int $expireSeconds = 600): ?string {
        return $this->altchaClient->buildWidgetChallenge($complexity, $expireSeconds);
    }

}
