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
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\ValidationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AltchaFormSubscriber
 */
class AltchaFormSubscriberTest extends TestCase {

    /**
     * Property Test: Lead Cleanup After Failed Validation
     *
     * When ALTCHA verification fails and a new lead is created, the subscriber
     * must register a kernel.terminate listener that deletes the lead.
     *
     * @test
     */
    public function testLeadCleanupAfterFailedValidation(): void {
        $iterations = 100;
        $failures   = [];

        for ($i = 0; $i < $iterations; $i++) {
            $registeredListeners = [];

            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher->method('addListener')
                ->willReturnCallback(function($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                    $registeredListeners[] = ['event' => $eventName, 'listener' => $listener, 'priority' => $priority];
                });

            $altchaClient = $this->createMock(AltchaClient::class);
            $altchaClient->method('isConfigured')->willReturn(true);
            $altchaClient->method('verify')->willReturn(false);

            $leadDeleteCalled = false;
            $leadModel = $this->createMock(LeadModel::class);
            $leadModel->method('deleteEntity')->willReturnCallback(function() use (&$leadDeleteCalled) {
                $leadDeleteCalled = true;
            });

            $subscriber = $this->createSubscriber($eventDispatcher, $altchaClient, $leadModel);

            $validationEvent = new ValidationEvent(new Field(), 'invalid-payload');

            $subscriber->onFormValidate($validationEvent);

            if ($validationEvent->isValid()) {
                $failures[] = ['iteration' => $i, 'reason' => 'Validation did not fail'];
                continue;
            }

            // LEAD_POST_SAVE listener must have been registered
            $leadPostSaveListener = null;
            foreach ($registeredListeners as $l) {
                if ($l['event'] === LeadEvents::LEAD_POST_SAVE) {
                    $leadPostSaveListener = $l;
                    break;
                }
            }

            if ($leadPostSaveListener === null) {
                $failures[] = ['iteration' => $i, 'reason' => 'LEAD_POST_SAVE listener not registered'];
                continue;
            }

            if ($leadPostSaveListener['priority'] !== -255) {
                $failures[] = ['iteration' => $i, 'reason' => 'Wrong LEAD_POST_SAVE priority', 'actual' => $leadPostSaveListener['priority']];
                continue;
            }

            // Simulate LEAD_POST_SAVE with a new lead
            $registeredListeners = [];
            $lead = new Lead();
            $lead->setId(123);
            ($leadPostSaveListener['listener'])(new LeadEvent($lead, true));

            // kernel.terminate must be registered next
            $kernelTerminateListener = null;
            foreach ($registeredListeners as $l) {
                if ($l['event'] === 'kernel.terminate') {
                    $kernelTerminateListener = $l;
                    break;
                }
            }

            if ($kernelTerminateListener === null) {
                $failures[] = ['iteration' => $i, 'reason' => 'kernel.terminate listener not registered'];
                continue;
            }

            // Simulate kernel.terminate — lead must be deleted
            ($kernelTerminateListener['listener'])();

            if (!$leadDeleteCalled) {
                $failures[] = ['iteration' => $i, 'reason' => 'Lead not deleted after kernel.terminate'];
            }
        }

        $this->assertEmpty($failures, sprintf(
            "Lead cleanup after failed validation failed in %d/%d iterations:\n%s",
            count($failures), $iterations, json_encode($failures, JSON_PRETTY_PRINT)
        ));
    }

    /**
     * Test: Existing leads are NOT deleted after failed validation
     *
     * @test
     */
    public function testLeadCleanupSkipsExistingLeads(): void {
        $registeredListeners = [];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('addListener')
            ->willReturnCallback(function($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                $registeredListeners[] = ['event' => $eventName, 'listener' => $listener, 'priority' => $priority];
            });

        $altchaClient = $this->createMock(AltchaClient::class);
        $altchaClient->method('isConfigured')->willReturn(true);
        $altchaClient->method('verify')->willReturn(false);

        $leadDeleteCalled = false;
        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->method('deleteEntity')->willReturnCallback(function() use (&$leadDeleteCalled) {
            $leadDeleteCalled = true;
        });

        $subscriber = $this->createSubscriber($eventDispatcher, $altchaClient, $leadModel);

        $validationEvent = new ValidationEvent(new Field(), 'invalid-payload');
        $subscriber->onFormValidate($validationEvent);

        // Find LEAD_POST_SAVE listener
        $leadPostSaveListener = null;
        foreach ($registeredListeners as $l) {
            if ($l['event'] === LeadEvents::LEAD_POST_SAVE) {
                $leadPostSaveListener = $l;
                break;
            }
        }

        $this->assertNotNull($leadPostSaveListener);

        // Simulate with existing lead (isNew = false)
        $registeredListeners = [];
        $lead = new Lead();
        $lead->setId(456);
        ($leadPostSaveListener['listener'])(new LeadEvent($lead, false));

        $hasKernelTerminate = !empty(array_filter($registeredListeners, fn($l) => $l['event'] === 'kernel.terminate'));

        $this->assertFalse($hasKernelTerminate, 'kernel.terminate should not be registered for existing leads');
        $this->assertFalse($leadDeleteCalled, 'Existing lead must not be deleted');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createSubscriber(
        EventDispatcherInterface $eventDispatcher,
        AltchaClient $altchaClient,
        LeadModel $leadModel
    ): AltchaFormSubscriber {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('ALTCHA verification failed.');

        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')->willReturn(['hmac_secret' => 'test-key']);
        $integration->method('getTranslator')->willReturn($translator);

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObject')
            ->with(AltchaIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $request = $this->createMock(Request::class);
        $request->request = new \Symfony\Component\HttpFoundation\InputBag(['altcha' => 'invalid-payload']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        return new AltchaFormSubscriber(
            $eventDispatcher,
            $altchaClient,
            $leadModel,
            $requestStack,
            $integrationHelper
        );
    }

}
