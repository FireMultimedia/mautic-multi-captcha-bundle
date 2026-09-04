<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;

/**
 * <h1>Class ChallengeController</h1>
 *
 * A public (unauthenticated), read-only endpoint that the <altcha-widget>
 * fetches a fresh challenge from directly, in self-hosted mode.
 *
 * This exists because Mautic caches an entire form's RENDERED HTML in
 * `forms.cached_html` (see `Mautic\FormBundle\Model\FormModel::generateHtml()`
 * / `getContent()`) and only regenerates it when the form itself is saved -
 * not on every page view, and not every time preview is opened. A challenge
 * embedded directly into that cached HTML would be reused by every visitor
 * until the form is next saved, and would eventually expire while still
 * being served - which is exactly what caused "Verification failed. Try
 * again later." to appear on every attempt, deterministically, regardless
 * of what the challenge JSON itself contained. Pointing the widget's
 * "challengeurl" attribute at this endpoint instead (a URL, fetched live by
 * the visitor's browser at the moment the widget actually loads) sidesteps
 * that caching layer entirely - the same approach ALTCHA Sentinel already
 * uses, and what ALTCHA's own docs describe as the standard "server
 * integration" pattern for any dynamically-cached page.
 *
 * Deliberately a plain, invokable class (`__invoke()`), not a
 * Mautic\CoreBundle\Controller\FormController with a named *Action method -
 * Mautic's own plugin-config docs specifically show a bare `SomeClass::class`
 * reference (assuming an invokable controller) as the pattern for routes
 * registered under the "public" firewall, as opposed to the `[SomeClass,
 * 'methodName']` array pairing used for "main"/authenticated routes.
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\Controller
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class ChallengeController {

    /** Hard floor/ceiling on the requested expiry, regardless of what's passed in the query string. */
    private const MIN_EXPIRE_SECONDS = 30;
    private const MAX_EXPIRE_SECONDS = 3600;

    public function __construct(private readonly AltchaClient $altchaClient) {

    }

    public function __invoke(Request $request): JsonResponse {
        $complexity    = (string) $request->query->get("complexity", "medium");
        $expireSeconds = (int) $request->query->get("expire", (string) AltchaClient::DEFAULT_EXPIRE_SECONDS);
        $expireSeconds = max(self::MIN_EXPIRE_SECONDS, min(self::MAX_EXPIRE_SECONDS, $expireSeconds));

        $challenge = $this->altchaClient->createChallengeForComplexity($complexity, $expireSeconds);

        if($challenge === null) {
            return new JsonResponse(["error" => "ALTCHA is not configured for self-hosted challenges."], 503);
        }

        $response = new JsonResponse($challenge);

        // Never let any layer (browser, reverse proxy, CDN) cache this response -
        // a stale/reused challenge is exactly what this endpoint exists to prevent.
        $response->headers->set("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0");
        $response->headers->set("Pragma", "no-cache");

        // Mautic forms are routinely embedded on third-party domains - the
        // widget's fetch() call must be allowed cross-origin. The response
        // contains no sensitive or user-specific data.
        $response->headers->set("Access-Control-Allow-Origin", "*");

        return $response;
    }

}
