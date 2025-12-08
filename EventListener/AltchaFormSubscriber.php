<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use \JsonException;
use Mautic\CoreBundle\Exception\BadConfigurationException;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\FormEvents;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\LeadEvents;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\AltchaType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;

/**
 * <h1>Class AltchaFormSubscriber</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\EventListener
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class AltchaFormSubscriber implements EventSubscriberInterface {

    public const MODEL_NAME_KEY_LEAD = "lead.lead";

    private TranslatorInterface $translator;

    private bool $isConfigured = false;

    /**
     * <h2>AltchaFormSubscriber constructor.</h2>
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param AltchaClient             $altchaClient
     * @param LeadModel                $leadModel
     * @param IntegrationHelper        $integrationHelper
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AltchaClient             $altchaClient,
        private readonly LeadModel                $leadModel,

        IntegrationHelper $integrationHelper
    ) {
        $integrationObject = $integrationHelper->getIntegrationObject(AltchaIntegration::INTEGRATION_NAME);

        $this->translator = $integrationObject->getTranslator();

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            if(isset($keys["hmac_key"]) && !empty($keys["hmac_key"]))
                $this->isConfigured = true;
        }
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            FormEvents::FORM_ON_BUILD               => ["onFormBuild", 0],
            CaptchaEvents::ALTCHA_ON_FORM_VALIDATE => ["onFormValidate", 0]
        ];
    }

    /**
     * <h2>onFormBuild</h2>
     *
     * @param FormBuilderEvent $event
     *
     * @throws BadConfigurationException
     *
     * @return void
     */
    public function onFormBuild(FormBuilderEvent $event): void {
        if(!$this->isConfigured)
            return;

        // Generate challenge for the form
        $challenge = $this->altchaClient->createChallenge(50000, 120);

        $event->addFormField("plugin.altcha", [
            "label"    => "strings.altcha.plugin.name",
            "formType" => AltchaType::class,
            "template" => "@MauticMultiCaptcha/Integration/altcha.html.twig",
            "challenge" => $challenge,

            "builderOptions" => [
                "addLeadFieldList" => false,
                "addIsRequired"    => false,
                "addDefaultValue"  => false,
                "addSaveResult"    => true
            ]
        ]);

        $event->addValidator("plugin.altcha.validator", [
            "eventName" => CaptchaEvents::ALTCHA_ON_FORM_VALIDATE,
            "fieldType" => "plugin.altcha"
        ]);
    }

    /**
     * <h2>onFormValidate</h2>
     *
     * @param ValidationEvent $event
     *
     * @throws JsonException
     *
     * @return void
     */
    public function onFormValidate(ValidationEvent $event) {
        if(!$this->isConfigured)
            return;

        // Get the payload from the form submission
        $payload = $event->getValue();

        if($this->altchaClient->verify($payload))
            return;

        $event->failedValidation($this->translator === null ? "Altcha verification failed." : $this->translator->trans("strings.altcha.failure_message"));

        // Add lead cleanup logic after failed validation
        $this->eventDispatcher->addListener(LeadEvents::LEAD_POST_SAVE, function(LeadEvent $event) {
            if(!$event->isNew())
                return;

            $leadId = $event->getLead();

            $this->eventDispatcher->addListener("kernel.terminate", function() use ($leadId) {
                $lead = $this->leadModel->getEntity($leadId);

                if($lead)
                    $this->leadModel->deleteEntity($lead);
            });
        }, -255);
    }

}
