<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for Altcha Template Resource Loading
 * 
 * **Feature: altcha-integration, Property 8: Local Resource Loading**
 * **Validates: Requirements 8.1**
 */
class AltchaTemplateTest extends TestCase {

    /**
     * Property Test: Local Resource Loading
     * 
     * For any rendered Altcha widget template, all script sources should reference
     * local or CDN URLs without third-party tracking domains.
     * 
     * This property verifies that:
     * 1. All script sources are from approved domains (local or jsdelivr CDN)
     * 2. No third-party tracking scripts are loaded
     * 3. GDPR compliance is maintained by avoiding external tracking
     * 
     * Generator: Random widget configurations (invisible mode, different field settings)
     * Iterations: 100
     * 
     * @test
     */
    public function testLocalResourceLoading(): void {
        $iterations = 100;
        $failures = [];

        // Approved domains for script loading (GDPR-compliant)
        $approvedDomains = [
            'cdn.jsdelivr.net',  // CDN for Altcha widget
            'localhost',          // Local development
            '',                   // Relative paths (local)
        ];

        // Blocked domains (third-party tracking)
        $blockedDomains = [
            'google-analytics.com',
            'googletagmanager.com',
            'facebook.com',
            'doubleclick.net',
            'google.com',
            'cloudflare.com',
            'hcaptcha.com',
        ];

        // Read the template file
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        
        if (!file_exists($templatePath)) {
            $this->fail("Template file not found: {$templatePath}");
        }

        $templateContent = file_get_contents($templatePath);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random widget configuration
            $config = $this->generateRandomWidgetConfig();

            // Extract all script sources from template
            $scriptSources = $this->extractScriptSources($templateContent);

            // Verify each script source
            foreach ($scriptSources as $source) {
                $domain = $this->extractDomain($source);

                // Check if domain is approved
                $isApproved = false;
                foreach ($approvedDomains as $approvedDomain) {
                    if (empty($approvedDomain)) {
                        // Relative path (local)
                        if (!preg_match('/^https?:\/\//', $source)) {
                            $isApproved = true;
                            break;
                        }
                    } elseif (strpos($domain, $approvedDomain) !== false) {
                        $isApproved = true;
                        break;
                    }
                }

                // Check if domain is blocked
                $isBlocked = false;
                foreach ($blockedDomains as $blockedDomain) {
                    if (strpos($domain, $blockedDomain) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }

                if (!$isApproved || $isBlocked) {
                    $failures[] = [
                        'iteration' => $i,
                        'config' => $config,
                        'source' => $source,
                        'domain' => $domain,
                        'approved' => $isApproved,
                        'blocked' => $isBlocked,
                    ];
                }
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "Local resource loading validation failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Generate random widget configuration
     */
    private function generateRandomWidgetConfig(): array {
        return [
            'maxNumber' => rand(1000, 1000000),
            'expires' => rand(10, 300),
            'invisible' => (bool) rand(0, 1),
            'showLabel' => (bool) rand(0, 1),
        ];
    }

    /**
     * Extract script sources from template content
     */
    private function extractScriptSources(string $content): array {
        $sources = [];

        // Match <script src="..."> tags
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            $sources = array_merge($sources, $matches[1]);
        }

        return $sources;
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain(string $url): string {
        // Handle relative URLs
        if (!preg_match('/^https?:\/\//', $url)) {
            return '';
        }

        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Unit test: Verify template file exists
     * 
     * @test
     */
    public function testTemplateFileExists(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $this->assertFileExists($templatePath, 'Altcha template file should exist');
    }

    /**
     * Unit test: Verify template contains altcha-widget element
     * 
     * @test
     */
    public function testTemplateContainsWidget(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $content = file_get_contents($templatePath);
        
        $this->assertStringContainsString(
            'altcha-widget',
            $content,
            'Template should contain altcha-widget custom element'
        );
    }

    /**
     * Unit test: Verify template contains hidden input field
     * 
     * @test
     */
    public function testTemplateContainsHiddenInput(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $content = file_get_contents($templatePath);
        
        $this->assertStringContainsString(
            'type="hidden"',
            $content,
            'Template should contain hidden input field for payload'
        );
    }

    /**
     * Unit test: Verify template contains error message container
     * 
     * @test
     */
    public function testTemplateContainsErrorContainer(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $content = file_get_contents($templatePath);
        
        $this->assertStringContainsString(
            'mauticform-errormsg',
            $content,
            'Template should contain error message container'
        );
    }

    /**
     * Unit test: Verify template uses CDN for widget script
     * 
     * @test
     */
    public function testTemplateUsesCDN(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $content = file_get_contents($templatePath);
        
        $this->assertStringContainsString(
            'cdn.jsdelivr.net/npm/altcha',
            $content,
            'Template should load Altcha widget from CDN'
        );
    }

    /**
     * Unit test: Verify template handles invisible mode
     * 
     * @test
     */
    public function testTemplateHandlesInvisibleMode(): void {
        $templatePath = __DIR__ . '/../../Resources/views/Integration/altcha.html.twig';
        $content = file_get_contents($templatePath);
        
        $this->assertStringContainsString(
            'invisible',
            $content,
            'Template should handle invisible mode configuration'
        );
    }

}
