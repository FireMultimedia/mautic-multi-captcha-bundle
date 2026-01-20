<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\Form\Type;

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\AltchaType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for AltchaType
 */
class AltchaTypeTest extends TestCase {

    private FormFactoryInterface $formFactory;

    protected function setUp(): void {
        // Create form factory with validator
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new \Symfony\Component\Form\Extension\Validator\ValidatorExtension(
                Validation::createValidator()
            ))
            ->getFormFactory();
    }

    /**
     * Property Test: Invisible Field Validation
     * 
     * **Feature: ALTCHA-integration, Property 1: Invisible Field**
     * **Validates: Requirements 2.6**
     * 
     * For any boolean value, when setting the invisible field property,
     * the system should accept the value without validation errors.
     * 
     * @test
     */
    public function testInvisibleFieldValidation(): void {
        $testValues = [true, false, 0, 1, '0', '1'];
        
        foreach ($testValues as $value) {
            $form = $this->formFactory->create(AltchaType::class, [
                'invisible' => $value
            ]);

            $form->submit([
                'invisible' => $value
            ]);

            $this->assertTrue(
                $form->isValid(),
                sprintf('Form should be valid for invisible value: %s', var_export($value, true))
            );
        }
    }

}
