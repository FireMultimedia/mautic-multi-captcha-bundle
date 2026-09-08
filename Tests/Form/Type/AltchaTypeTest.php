<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Form\Type;

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\AltchaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AltchaType
 */
class AltchaTypeTest extends TestCase {

    private FormFactoryInterface $formFactory;

    protected function setUp(): void {
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new \Symfony\Component\Form\Extension\Validator\ValidatorExtension(
                Validation::createValidator()
            ))
            ->getFormFactory();
    }

    /**
     * Test: complexity field accepts all valid values
     *
     * @test
     */
    public function testComplexityFieldAcceptsValidValues(): void {
        foreach (['low', 'medium', 'high'] as $complexity) {
            $form = $this->formFactory->create(AltchaType::class, ['complexity' => $complexity]);
            $form->submit(['complexity' => $complexity, 'expire' => 600, 'auto' => 'onsubmit', 'display' => 'standard', 'hideFooter' => false, 'hideLogo' => false]);

            $this->assertTrue(
                $form->isValid(),
                "Form should be valid for complexity: {$complexity}"
            );
        }
    }

    /**
     * Test: display field accepts all valid values including invisible
     *
     * @test
     */
    public function testDisplayFieldAcceptsValidValues(): void {
        foreach (['standard', 'bar', 'floating', 'overlay', 'invisible'] as $display) {
            $form = $this->formFactory->create(AltchaType::class, ['display' => $display]);
            $form->submit(['complexity' => 'medium', 'expire' => 600, 'auto' => 'onsubmit', 'display' => $display, 'hideFooter' => false, 'hideLogo' => false]);

            $this->assertTrue(
                $form->isValid(),
                "Form should be valid for display mode: {$display}"
            );
        }
    }

    /**
     * Test: auto field accepts all valid values
     *
     * @test
     */
    public function testAutoFieldAcceptsValidValues(): void {
        foreach (['onload', 'onsubmit', 'off'] as $auto) {
            $form = $this->formFactory->create(AltchaType::class, ['auto' => $auto]);
            $form->submit(['complexity' => 'medium', 'expire' => 600, 'auto' => $auto, 'display' => 'standard', 'hideFooter' => false, 'hideLogo' => false]);

            $this->assertTrue(
                $form->isValid(),
                "Form should be valid for auto mode: {$auto}"
            );
        }
    }

    /**
     * Test: expire field accepts integer values
     *
     * @test
     */
    public function testExpireFieldAcceptsIntegerValues(): void {
        foreach ([30, 120, 600, 3600] as $expire) {
            $form = $this->formFactory->create(AltchaType::class, ['expire' => $expire]);
            $form->submit(['complexity' => 'medium', 'expire' => $expire, 'auto' => 'onsubmit', 'display' => 'standard', 'hideFooter' => false, 'hideLogo' => false]);

            $this->assertTrue(
                $form->isValid(),
                "Form should be valid for expire: {$expire}"
            );
        }
    }

}
