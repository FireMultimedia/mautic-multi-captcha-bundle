<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\Tests\EventListener;

use MauticPlugin\MauticMultiCaptchaBundle\EventListener\CapFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\Service\CapClient;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapFormSubscriber
 *
 * Unlike ALTCHA (whose token is bound to the field's own form value via
 * ValidationEvent::getValue()), the Cap widget's redeemed token arrives as
 * a fixed-name hidden field ("cap-token") submitted alongside the rest of
 * the Mautic form, so these tests read/write $_POST directly.
 */
class CapFormSubscriberTest extends TestCase {

    protected function tearDown(): void {
        unset($_POST['cap-token']);
    }

    /**
     * @test
     */
    public function testSubscribedEvents(): void {
        $events = CapFormSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(FormEvents::FORM_ON_BUILD, $events);
        $this->assertArrayHasKey(CaptchaEvents::CAP_ON_FORM_VALIDATE, $events);
    }

    /**
     * @test
     */
    public function testOnFormBuildAddsFieldWhenFullyConfigured(): void {
        $subscriber = $this->createSubscriber(
            $this->createMock(CapClient::class),
            $this->createMock(LeadModel::class),
            [
                'server_url' => 'https://cap.example.com',
                'site_key'   => 'site-key-123',
                'secret_key' => 'secret-abc'
            ]
        );

        $event = new FormBuilderEvent($this->createMock(SymfonyTranslatorInterface::class));
        $subscriber->onFormBuild($event);

        $fields = $event->getFormFields();
        $this->assertArrayHasKey('plugin.cap', $fields);
        $this->assertEquals('https://cap.example.com', $fields['plugin.cap']['server_url']);
        $this->assertEquals('site-key-123', $fields['plugin.cap']['site_key']);

        // getValidators() organises by fieldType, not by key
        $validators = $event->getValidators();
        $this->assertArrayHasKey('plugin.cap', $validators);
        $this->assertEquals(CaptchaEvents::CAP_ON_FORM_VALIDATE, $validators['plugin.cap']);
    }

    /**
     * Property Test: onFormBuild is a no-op when any required key is missing
     *
     * @test
     */
    public function testOnFormBuildSkipsFieldWhenNotFullyConfigured(): void {
        $incompleteConfigs = [
            [],
            ['server_url' => 'https://cap.example.com'],
            ['server_url' => 'https://cap.example.com', 'site_key' => 'site-key-123'],
            ['site_key' => 'site-key-123', 'secret_key' => 'secret-abc'],
        ];

        foreach ($incompleteConfigs as $keys) {
            $subscriber = $this->createSubscriber(
                $this->createMock(CapClient::class),
                $this->createMock(LeadModel::class),
                $keys
            );

            $event = new FormBuilderEvent($this->createMock(SymfonyTranslatorInterface::class));
            $subscriber->onFormBuild($event);

            $this->assertEmpty($event->getFormFields());
            // getValidators() always returns at least ['form' => []] - confirm no fieldType validators added
            $validators = $event->getValidators();
            $this->assertArrayNotHasKey('plugin.cap', $validators);
        }
    }

    /**
     * @test
     */
    public function testOnFormValidatePassesWhenTokenVerifies(): void {
        $_POST['cap-token'] = 'sitekey:id:secret';

        $capClient = $this->createMock(CapClient::class);
        $capClient->expects($this->once())
            ->method('verify')
            ->with('sitekey:id:secret')
            ->willReturn(true);

        $subscriber = $this->createSubscriber($capClient, $this->createMock(LeadModel::class));

        $event = new ValidationEvent(new Field(), 'sitekey:id:secret');
        $subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }

    /**
     * @test
     */
    public function testOnFormValidateTreatsMissingTokenAsEmptyString(): void {
        unset($_POST['cap-token']);

        $capClient = $this->createMock(CapClient::class);
        $capClient->expects($this->once())
            ->method('verify')
            ->with('')
            ->willReturn(false);

        $subscriber = $this->createSubscriber($capClient, $this->createMock(LeadModel::class));

        $event = new ValidationEvent(new Field(), '');
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
    }

    /**
     * @test
     */
    public function testOnFormValidateIsNoOpWhenNotConfigured(): void {
        $capClient = $this->createMock(CapClient::class);
        $capClient->expects($this->never())->method('verify');

        $subscriber = $this->createSubscriber($capClient, $this->createMock(LeadModel::class), []);

        $event = new ValidationEvent(new Field(), '');
        $subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }

    /**
     * Property Test: Lead cleanup after failed Cap validation
     *
     * @test
     */
    public function testLeadCleanupAfterFailedValidation(): void {
        $_POST['cap-token'] = 'sitekey:id:bad-secret';

        $registeredListeners = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('addListener')
            ->willReturnCallback(function ($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                $registeredListeners[] = ['event' => $eventName, 'listener' => $listener, 'priority' => $priority];
            });

        $capClient = $this->createMock(CapClient::class);
        $capClient->method('verify')->willReturn(false);

        $leadModel = $this->createMock(LeadModel::class);
        $leadDeleteCalled = false;
        $leadModel->method('getEntity')->willReturn($this->createMock(Lead::class));
        $leadModel->method('deleteEntity')
            ->willReturnCallback(function () use (&$leadDeleteCalled) {
                $leadDeleteCalled = true;
            });

        $subscriber = $this->createSubscriber($capClient, $leadModel, [
            'server_url' => 'https://cap.example.com',
            'site_key'   => 'site-key-123',
            'secret_key' => 'secret-abc'
        ], $eventDispatcher);

        $validationEvent = new ValidationEvent(new Field(), 'sitekey:id:bad-secret');
        $subscriber->onFormValidate($validationEvent);

        $this->assertFalse($validationEvent->isValid());

        $leadPostSaveListener = null;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === LeadEvents::LEAD_POST_SAVE) {
                $leadPostSaveListener = $listener;
                break;
            }
        }

        $this->assertNotNull($leadPostSaveListener, 'LEAD_POST_SAVE listener should be registered');
        $this->assertEquals(-255, $leadPostSaveListener['priority']);

        $lead = new Lead();
        $lead->setId(123);
        $leadEvent = new LeadEvent($lead, true);

        $registeredListeners = [];
        ($leadPostSaveListener['listener'])($leadEvent);

        $kernelTerminateListener = null;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === 'kernel.terminate') {
                $kernelTerminateListener = $listener;
                break;
            }
        }

        $this->assertNotNull($kernelTerminateListener, 'kernel.terminate listener should be registered');

        ($kernelTerminateListener['listener'])();

        $this->assertTrue($leadDeleteCalled, 'Lead should be deleted after kernel.terminate');
    }

    /**
     * @test
     */
    public function testLeadCleanupSkipsExistingLeads(): void {
        $_POST['cap-token'] = 'sitekey:id:bad-secret';

        $registeredListeners = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('addListener')
            ->willReturnCallback(function ($eventName, $listener, $priority = 0) use (&$registeredListeners) {
                $registeredListeners[] = ['event' => $eventName, 'listener' => $listener, 'priority' => $priority];
            });

        $capClient = $this->createMock(CapClient::class);
        $capClient->method('verify')->willReturn(false);

        $leadModel = $this->createMock(LeadModel::class);
        $leadDeleteCalled = false;
        $leadModel->method('deleteEntity')
            ->willReturnCallback(function () use (&$leadDeleteCalled) {
                $leadDeleteCalled = true;
            });

        $subscriber = $this->createSubscriber($capClient, $leadModel, [
            'server_url' => 'https://cap.example.com',
            'site_key'   => 'site-key-123',
            'secret_key' => 'secret-abc'
        ], $eventDispatcher);

        $validationEvent = new ValidationEvent(new Field(), 'sitekey:id:bad-secret');
        $subscriber->onFormValidate($validationEvent);

        $leadPostSaveListener = null;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === LeadEvents::LEAD_POST_SAVE) {
                $leadPostSaveListener = $listener;
                break;
            }
        }

        $this->assertNotNull($leadPostSaveListener);

        $lead = new Lead();
        $lead->setId(456);
        $leadEvent = new LeadEvent($lead, false); // not new

        $registeredListeners = [];
        ($leadPostSaveListener['listener'])($leadEvent);

        $hasKernelTerminate = false;
        foreach ($registeredListeners as $listener) {
            if ($listener['event'] === 'kernel.terminate') {
                $hasKernelTerminate = true;
            }
        }

        $this->assertFalse($hasKernelTerminate);
        $this->assertFalse($leadDeleteCalled);
    }

    /**
     * Helper: Create a CapFormSubscriber with mocked dependencies.
     */
    private function createSubscriber(
        CapClient $capClient,
        LeadModel $leadModel,
        array $keys = ['server_url' => 'https://cap.example.com', 'site_key' => 'site-key-123', 'secret_key' => 'secret-abc'],
        ?EventDispatcherInterface $eventDispatcher = null
    ): CapFormSubscriber {
        $integrationHelper = $this->createMock(IntegrationHelper::class);

        $integration = $this->createMock(AbstractIntegration::class);
        $integration->method('getKeys')->willReturn($keys);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Cap CAPTCHA verification failed.');
        $integration->method('getTranslator')->willReturn($translator);

        $integrationHelper->method('getIntegrationObject')
            ->with(CapIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        return new CapFormSubscriber(
            $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
            $capClient,
            $leadModel,
            $integrationHelper
        );
    }

}
