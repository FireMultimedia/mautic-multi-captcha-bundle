<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\EventListener;

use MauticPlugin\MauticMultiCaptchaBundle\EventListener\AltchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\FormBundle\Event\ValidationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for AltchaFormSubscriber
 */
class AltchaFormSubscriberTest extends TestCase {

    /**
     * Property Test: Lead Cleanup After Failed Validation
     * 
     * **Feature: ALTCHA-integration, Property 7: Lead Cleanup After Failed Validation**
     * **Validates: Requirements 7.5**
     * 
     * For any form submission with invalid ALTCHA payload, if a lead was created
     * during processing, the system should automatically delete that lead after
     * validation failure.
     * 
     * This property verifies that:
     * 1. When validation fails, a listener is registered for LEAD_POST_SAVE
     * 2. The listener checks if the lead is new
     * 3. The listener registers a kernel.terminate listener to delete the lead
     * 4. The lead is deleted after the request completes
     * 
     * Generator: Random invalid payloads
     * Iterations: 100
     * 
     * @test
     */
    public function testLeadCleanupAfterFailedValidation(): void {
        $iterations = 100;
        $failures = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random invalid payload
            $invalidPayload = $this->generateInvalidPayload();

            // Track event listeners that were registered
            $registeredListeners = [];
            
            // Mock EventDispatcher to capture listener registrations
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher->method('addListener')
                ->willReturnCallback(function($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                    $registeredListeners[] = [
                        'event' => $eventName,
                        'listener' => $listener,
                        'priority' => $priority
                    ];
                });

            // Mock AltchaClient to always return false (invalid payload)
            $altchaClient = $this->createMock(AltchaClient::class);
            $altchaClient->method('verify')
                ->willReturn(false);

            // Mock LeadModel
            $leadModel = $this->createMock(LeadModel::class);
            $leadDeleteCalled = false;
            $leadModel->method('getEntity')
                ->willReturn($this->createMockLead());
            $leadModel->method('deleteEntity')
                ->willReturnCallback(function() use (&$leadDeleteCalled) {
                    $leadDeleteCalled = true;
                });

            // Create subscriber
            $subscriber = $this->createAltchaFormSubscriber(
                $eventDispatcher,
                $altchaClient,
                $leadModel
            );

            // Mock ValidationEvent
            $validationEvent = $this->createMock(ValidationEvent::class);
            $validationEvent->method('getValue')
                ->willReturn($invalidPayload);
            
            $validationFailedCalled = false;
            $validationEvent->method('failedValidation')
                ->willReturnCallback(function() use (&$validationFailedCalled) {
                    $validationFailedCalled = true;
                });

            // Call onFormValidate
            $subscriber->onFormValidate($validationEvent);

            // Verify validation failed
            if (!$validationFailedCalled) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Validation did not fail for invalid payload',
                    'payload' => $invalidPayload
                ];
                continue;
            }

            // Verify LEAD_POST_SAVE listener was registered
            $leadPostSaveListener = null;
            foreach ($registeredListeners as $listener) {
                if ($listener['event'] === LeadEvents::LEAD_POST_SAVE) {
                    $leadPostSaveListener = $listener;
                    break;
                }
            }

            if ($leadPostSaveListener === null) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'LEAD_POST_SAVE listener was not registered',
                    'registered_events' => array_column($registeredListeners, 'event')
                ];
                continue;
            }

            // Verify the priority is -255 (to run after other listeners)
            if ($leadPostSaveListener['priority'] !== -255) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'LEAD_POST_SAVE listener priority is incorrect',
                    'expected_priority' => -255,
                    'actual_priority' => $leadPostSaveListener['priority']
                ];
                continue;
            }

            // Simulate the LEAD_POST_SAVE event with a new lead
            // Create a real Lead entity since LeadEvent is final
            $lead = new Lead();
            $lead->setId(123);
            
            // Create a real LeadEvent (it's final, so we can't mock it)
            $leadEvent = new LeadEvent($lead, true); // true = isNew

            // Reset registered listeners to track kernel.terminate
            $registeredListeners = [];

            // Call the LEAD_POST_SAVE listener
            $leadPostSaveCallback = $leadPostSaveListener['listener'];
            $leadPostSaveCallback($leadEvent);

            // Verify kernel.terminate listener was registered
            $kernelTerminateListener = null;
            foreach ($registeredListeners as $listener) {
                if ($listener['event'] === 'kernel.terminate') {
                    $kernelTerminateListener = $listener;
                    break;
                }
            }

            if ($kernelTerminateListener === null) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'kernel.terminate listener was not registered',
                    'registered_events' => array_column($registeredListeners, 'event')
                ];
                continue;
            }

            // Simulate kernel.terminate event
            $kernelTerminateCallback = $kernelTerminateListener['listener'];
            $kernelTerminateCallback();

            // Verify lead was deleted
            if (!$leadDeleteCalled) {
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Lead was not deleted after kernel.terminate'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failures,
            sprintf(
                "Lead cleanup after failed validation failed in %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                json_encode($failures, JSON_PRETTY_PRINT)
            )
        );
    }

    /**
     * Unit Test: Verify LEAD_POST_SAVE listener is NOT registered for existing leads
     * 
     * @test
     */
    public function testLeadCleanupSkipsExistingLeads(): void {
        // Track event listeners
        $registeredListeners = [];
        
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('addListener')
            ->willReturnCallback(function($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                $registeredListeners[] = [
                    'event' => $eventName,
                    'listener' => $listener,
                    'priority' => $priority
                ];
            });

        $altchaClient = $this->createMock(AltchaClient::class);
        $altchaClient->method('verify')->willReturn(false);

        $leadModel = $this->createMock(LeadModel::class);
        $leadDeleteCalled = false;
        $leadModel->method('deleteEntity')
            ->willReturnCallback(function() use (&$leadDeleteCalled) {
                $leadDeleteCalled = true;
            });

        $subscriber = $this->createAltchaFormSubscriber(
            $eventDispatcher,
            $altchaClient,
            $leadModel
        );

        $validationEvent = $this->createMock(ValidationEvent::class);
        $validationEvent->method('getValue')->willReturn('invalid');
        $validationEvent->method('failedValidation');

        // Call onFormValidate
        $subscriber->onFormValidate($validationEvent);

        // Find LEAD_POST_SAVE listener
        $leadPostSaveListener = null;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === LeadEvents::LEAD_POST_SAVE) {
                $leadPostSaveListener = $listener;
                break;
            }
        }

        $this->assertNotNull($leadPostSaveListener, 'LEAD_POST_SAVE listener should be registered');

        // Simulate LEAD_POST_SAVE with existing lead (isNew = false)
        // Create a real Lead entity since LeadEvent is final
        $lead = new Lead();
        $lead->setId(456);
        
        // Create a real LeadEvent with isNew = false
        $leadEvent = new LeadEvent($lead, false);

        // Reset registered listeners
        $registeredListeners = [];

        // Call the listener
        $leadPostSaveCallback = $leadPostSaveListener['listener'];
        $leadPostSaveCallback($leadEvent);

        // Verify kernel.terminate was NOT registered
        $hasKernelTerminate = false;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === 'kernel.terminate') {
                $hasKernelTerminate = true;
                break;
            }
        }

        $this->assertFalse($hasKernelTerminate, 'kernel.terminate should not be registered for existing leads');
        $this->assertFalse($leadDeleteCalled, 'Lead should not be deleted for existing leads');
    }

    /**
     * Helper: Generate random invalid payload
     */
    private function generateInvalidPayload(): string {
        $types = ['empty', 'malformed_json', 'invalid_structure', 'random_data'];
        $type = $types[array_rand($types)];

        switch ($type) {
            case 'empty':
                return '';
            
            case 'malformed_json':
                return '{invalid json';
            
            case 'invalid_structure':
                return json_encode([
                    'foo' => 'bar',
                    'random' => rand(1, 1000)
                ]);
            
            case 'random_data':
            default:
                return base64_encode(random_bytes(32));
        }
    }

    /**
     * Helper: Create AltchaFormSubscriber with mocked dependencies
     */
    private function createAltchaFormSubscriber(
        EventDispatcherInterface $eventDispatcher,
        AltchaClient $altchaClient,
        LeadModel $leadModel
    ): AltchaFormSubscriber {
        // Mock IntegrationHelper
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        
        // Mock AbstractIntegration with translator
        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')
            ->willReturn(['hmac_key' => 'test-key']);
        
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturn('ALTCHA verification failed.');
        
        $integration->method('getTranslator')
            ->willReturn($translator);
        
        $integrationHelper->method('getIntegrationObject')
            ->with(AltchaIntegration::INTEGRATION_NAME)
            ->willReturn($integration);
        
        return new AltchaFormSubscriber(
            $eventDispatcher,
            $altchaClient,
            $leadModel,
            $integrationHelper
        );
    }

    /**
     * Helper: Create mock Lead entity
     */
    private function createMockLead(): Lead {
        return $this->createMock(Lead::class);
    }

}
