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
     * Property Test: maxNumber Range Validation
     * 
     * **Feature: altcha-integration, Property 2: maxNumber Range Validation**
     * **Validates: Requirements 2.4**
     * 
     * For any integer value, when setting the maxNumber field property,
     * the system should accept values between 1000 and 1000000 (inclusive)
     * and reject all other values.
     * 
     * Generator: Random integers (-1000000 to 2000000)
     * Iterations: 100
     * 
     * @test
     */
    public function testMaxNumberRangeValidation(): void {
        $iterations = 100;
        $failures = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random integer in extended range
            $value = rand(-1000000, 2000000);
            
            // Determine expected validity
            $shouldBeValid = ($value >= 1000 && $value <= 1000000);

            // Create form with the value
            $form = $this->formFactory->create(AltchaType::class, [
                'maxNumber' => $value,
                'expires' => 120,
                'invisible' => false
            ]);

            // Submit the form
            $form->submit([
                'maxNumber' => $value,
                'expires' => 120,
                'invisible' => 0
            ]);

            // Check if form is valid
            $isValid = $form->isValid();
            
            // Check for validation errors on maxNumber field
            $maxNumberField = $form->get('maxNumber');
            $hasErrors = count($maxNumberField->getErrors()) > 0;

            // Verify the validation result matches expectation
            if ($shouldBeValid && $hasErrors) {
                $failures[] = [
                    'iteration' => $i,
                    'value' => $value,
                    'expected' => 'valid',
                    'actual' => 'invalid',
                    'errors' => $this->getErrorMessages($maxNumberField)
                ];
            } elseif (!$shouldBeValid && !$hasErrors) {
                $failures[] = [
                    'iteration' => $i,
                    'value' => $value,
                    'expected' => 'invalid',
                    'actual' => 'valid'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "maxNumber range validation failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Property Test: Expires Range Validation
     * 
     * **Feature: altcha-integration, Property 3: Expires Range Validation**
     * **Validates: Requirements 2.5**
     * 
     * For any integer value, when setting the expires field property,
     * the system should accept values between 10 and 300 (inclusive)
     * and reject all other values.
     * 
     * Generator: Random integers (0 to 500)
     * Iterations: 100
     * 
     * @test
     */
    public function testExpiresRangeValidation(): void {
        $iterations = 100;
        $failures = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random integer in extended range
            $value = rand(0, 500);
            
            // Determine expected validity
            $shouldBeValid = ($value >= 10 && $value <= 300);

            // Create form with the value
            $form = $this->formFactory->create(AltchaType::class, [
                'maxNumber' => 50000,
                'expires' => $value,
                'invisible' => false
            ]);

            // Submit the form
            $form->submit([
                'maxNumber' => 50000,
                'expires' => $value,
                'invisible' => 0
            ]);

            // Check if form is valid
            $isValid = $form->isValid();
            
            // Check for validation errors on expires field
            $expiresField = $form->get('expires');
            $hasErrors = count($expiresField->getErrors()) > 0;

            // Verify the validation result matches expectation
            if ($shouldBeValid && $hasErrors) {
                $failures[] = [
                    'iteration' => $i,
                    'value' => $value,
                    'expected' => 'valid',
                    'actual' => 'invalid',
                    'errors' => $this->getErrorMessages($expiresField)
                ];
            } elseif (!$shouldBeValid && !$hasErrors) {
                $failures[] = [
                    'iteration' => $i,
                    'value' => $value,
                    'expected' => 'invalid',
                    'actual' => 'valid'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "expires range validation failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Helper: Extract error messages from a form field
     */
    private function getErrorMessages($field): array {
        $messages = [];
        foreach ($field->getErrors() as $error) {
            $messages[] = $error->getMessage();
        }
        return $messages;
    }

}
