<?php declare(strict_types=1);

// Run against an installed Mautic with this plugin in its plugins directory.
// MAUTIC_ROOT=/path/to/mautic php -d memory_limit=1G Tests/integration-services.php
$root = realpath(getenv('MAUTIC_ROOT') ?: '');
if (!$root || !is_file($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "Set MAUTIC_ROOT to the Mautic project root containing vendor/autoload.php.\n");
    exit(1);
}

$applicationDir = is_dir($root . '/docroot/app') ? $root . '/docroot' : $root;
chdir($root);
defined('IN_MAUTIC_CONSOLE') or define('IN_MAUTIC_CONSOLE', 1);
defined('MAUTIC_ROOT_DIR') or define('MAUTIC_ROOT_DIR', $applicationDir);
require $applicationDir . '/app/config/bootstrap.php';

class MultiCaptchaTestKernel extends AppKernel
{
    public function __construct(private string $testRoot, private string $testCache)
    {
        parent::__construct('prod', false);
    }

    public function getProjectDir(): string
    {
        return $this->testRoot;
    }

    public function getCacheDir(): string
    {
        return $this->testCache;
    }
}

$cache = sys_get_temp_dir() . '/multicaptcha-services-' . bin2hex(random_bytes(8));
$kernel = new MultiCaptchaTestKernel($root, $cache);
$status = 0;
try {
    // A fresh container is essential: an existing prod cache can hide broken wiring.
    $kernel->boot();
    $container = $kernel->getContainer();
    foreach (['hcaptcha' => 'Hcaptcha', 'recaptcha' => 'Recaptcha', 'turnstile' => 'Turnstile'] as $id => $name) {
        $expected = 'MauticPlugin\\MauticMultiCaptchaBundle\\Integration\\' . $name . 'Integration';
        // Boot alone misses a literal class-name string injected instead of the helper.
        $integration = $container->get('mautic.integration.' . $id);
        if (!$integration instanceof $expected) {
            throw new RuntimeException('Unexpected integration class for ' . $id);
        }
        echo 'PASS ' . $id . PHP_EOL;
    }
    echo 'PASS Mautic ' . $kernel->getVersion() . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL);
    $status = 1;
} finally {
    $kernel->shutdown();
    (new Symfony\Component\Filesystem\Filesystem())->remove($cache);
}
exit($status);
