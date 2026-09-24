<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Cap widget template
 *
 * The main design decision behind this template is that the Cap widget must
 * stay fully self-hosted: no jsdelivr CDN calls for the widget script or the
 * WASM PoW solver, and no third-party captcha domains. These tests act as a
 * regression guard for that decision (mirrors AltchaTemplateTest's intent).
 */
class CapTemplateTest extends TestCase {

    private const TEMPLATE_PATH = __DIR__ . '/../../Resources/views/Integration/cap.html.twig';

    /**
     * @test
     */
    public function testTemplateFileExists(): void {
        $this->assertFileExists(self::TEMPLATE_PATH, 'Cap template file should exist');
    }

    /**
     * @test
     */
    public function testTemplateContainsWidget(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertStringContainsString('cap-widget', $content);
        $this->assertStringContainsString('data-cap-api-endpoint', $content);
    }

    /**
     * @test
     */
    public function testTemplateContainsErrorContainer(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertStringContainsString('mauticform-errormsg', $content);
    }

    /**
     * @test
     */
    public function testTemplateLoadsWidgetScriptFromLocalAssets(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertStringContainsString(
            'plugins/MauticMultiCaptchaBundle/Assets/js/cap.min.js',
            $content
        );
    }

    /**
     * @test
     */
    public function testTemplatePointsWasmSolverAtCorsEnabledRoute(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertStringContainsString('CAP_CUSTOM_WASM_URL', $content);

        // Must go through the CORS-enabled controller route, not the raw
        // static asset path - the widget fetches this via fetch(), which
        // enforces CORS, unlike its own <script> tag which does not.
        $this->assertStringContainsString('mautic_cap_api_wasm', $content);
        $this->assertStringNotContainsString(
            'plugins/MauticMultiCaptchaBundle/Assets/js/cap_wasm_bg.wasm',
            $content
        );
    }

    /**
     * Property Test: No third-party network calls
     *
     * @test
     */
    public function testTemplateNeverReferencesThirdPartyDomains(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $blockedDomains = [
            'cdn.jsdelivr.net',
            'trycap.dev',
            'google.com',
            'cloudflare.com',
            'hcaptcha.com',
        ];

        preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $content, $matches);

        $offendingUrls = [];
        foreach ($matches[0] as $url) {
            foreach ($blockedDomains as $blockedDomain) {
                if (stripos($url, $blockedDomain) !== false) {
                    $offendingUrls[] = $url;
                }
            }
        }

        $this->assertEmpty(
            $offendingUrls,
            'Template must not reference third-party domains: ' . implode(', ', $offendingUrls)
        );
    }

    /**
     * @test
     */
    public function testLocalCapScriptExists(): void {
        $this->assertFileExists(
            __DIR__ . '/../../Assets/js/cap.min.js',
            'Local Cap widget script should exist'
        );
    }

    /**
     * @test
     */
    public function testLocalWasmSolverExists(): void {
        $wasmPath = __DIR__ . '/../../Assets/js/cap_wasm_bg.wasm';

        $this->assertFileExists($wasmPath, 'Local Cap WASM solver should exist');

        // Verify it's a real WASM binary (magic bytes: \0asm)
        $bytes = file_get_contents($wasmPath, false, null, 0, 4);
        $this->assertEquals("\x00\x61\x73\x6d", $bytes, 'File should be a valid WASM binary');
    }

    /**
     * @test
     */
    public function testTemplateSupportsAutoSolveModes(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertStringContainsString("field.properties.mode", $content);
        $this->assertStringContainsString("customElements.whenDefined(\"cap-widget\")", $content);
        $this->assertStringContainsString(".solve()", $content);
    }

    /**
     * @test
     */
    public function testTemplateHidesWidgetOnlyInAutoHiddenMode(): void {
        $content = file_get_contents(self::TEMPLATE_PATH);

        $this->assertMatchesRegularExpression(
            '/<div class="mauticform-inner"\{% if hidden %\}[^}]*display:\s*none[^}]*\{% endif %\}>\s*<cap-widget/',
            $content,
            'The element wrapping <cap-widget> must be hidden in auto_hidden mode'
        );
    }

    /**
     * @test
     */
    public function testWidgetScriptAndWasmSolverVersionsAreCompatible(): void {
        $scriptContent = file_get_contents(__DIR__ . '/../../Assets/js/cap.min.js');

        $this->assertMatchesRegularExpression(
            '/@cap\.js\/wasm@[0-9]+\.[0-9]+\.[0-9]+/',
            $scriptContent,
            'cap.min.js should reference a pinned @cap.js/wasm version'
        );
    }

}
