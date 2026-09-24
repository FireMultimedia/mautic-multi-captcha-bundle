<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Form\Type;

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\CapType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapType
 *
 * Cap always combines its proof-of-work and instrumentation challenges (both
 * are configured server-side on the Cap Standalone instance), so the only
 * per-field option is how/when the widget solves (mode).
 */
class CapTypeTest extends TestCase {

    private FormFactoryInterface $formFactory;

    protected function setUp(): void {
        $this->formFactory = Forms::createFormFactoryBuilder()->getFormFactory();
    }

    /**
     * @test
     */
    public function testFormHasOnlyModeField(): void {
        $form = $this->formFactory->create(CapType::class);

        $this->assertCount(1, $form->all());
        $this->assertTrue($form->has('mode'));
    }

    /**
     * @test
     */
    public function testModeDefaultsToManual(): void {
        $form = $this->formFactory->create(CapType::class, []);

        $this->assertEquals(CapType::MODE_MANUAL, $form->get('mode')->getData());
    }

    /**
     * Property Test: every documented mode value is a valid, submittable choice
     *
     * @test
     */
    public function testEveryModeChoiceIsSubmittable(): void {
        foreach ([CapType::MODE_MANUAL, CapType::MODE_AUTO, CapType::MODE_AUTO_HIDDEN] as $mode) {
            $form = $this->formFactory->create(CapType::class);

            $form->submit(['mode' => $mode]);

            $this->assertTrue($form->isValid(), "mode={$mode} should be a valid submission");
            $this->assertEquals($mode, $form->get('mode')->getData());
        }
    }

    /**
     * @test
     */
    public function testModeRespectsStoredValue(): void {
        $form = $this->formFactory->create(CapType::class, [
            'mode' => CapType::MODE_AUTO_HIDDEN
        ]);

        $this->assertEquals(CapType::MODE_AUTO_HIDDEN, $form->get('mode')->getData());
    }

    /**
     * @test
     */
    public function testBlockPrefixMatchesIntegrationName(): void {
        $type = new CapType();

        $this->assertEquals(CapIntegration::INTEGRATION_NAME, $type->getBlockPrefix());
    }

    /**
     * @test
     */
    public function testFormRespectsCustomAction(): void {
        $form = $this->formFactory->create(CapType::class, null, [
            'action' => '/some/custom/action'
        ]);

        $this->assertEquals('/some/custom/action', $form->getConfig()->getAction());
    }

}
