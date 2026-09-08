<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use \JsonException;
use GuzzleHttp\Exception\GuzzleException;
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

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\CapType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Service\CapClient;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;

/**
 * <h1>Class CapFormSubscriber</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\EventListener
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class CapFormSubscriber implements EventSubscriberInterface {

    public const MODEL_NAME_KEY_LEAD = "lead.lead";

    private TranslatorInterface $translator;

    private bool $isConfigured = false;

    private ?string $serverUrl;
    private ?string $siteKey;

    /**
     * <h2>FormSubscriber constructor.</h2>
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param CapClient                $capClient
     * @param LeadModel                $leadModel
     * @param IntegrationHelper        $integrationHelper
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CapClient                $capClient,
        private readonly LeadModel                $leadModel,

        IntegrationHelper $integrationHelper
    ) {
        $integrationObject = $integrationHelper->getIntegrationObject(CapIntegration::INTEGRATION_NAME);

        $this->translator = $integrationObject->getTranslator();

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->serverUrl = isset($keys["server_url"]) ? rtrim($keys["server_url"], "/") : null;
            $this->siteKey = $keys["site_key"] ?? null;

            if($this->serverUrl && $this->siteKey && isset($keys["secret_key"]))
                $this->isConfigured = true;
        }
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            FormEvents::FORM_ON_BUILD          => ["onFormBuild", 0],
            CaptchaEvents::CAP_ON_FORM_VALIDATE => ["onFormValidate", 0]
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

        $event->addFormField("plugin.cap", [
            "label"      => "strings.cap.plugin.name",
            "formType"   => CapType::class,
            "template"   => "@MauticMultiCaptcha/Integration/cap.html.twig",
            "server_url" => $this->serverUrl,
            "site_key"   => $this->siteKey,

            "builderOptions" => [
                "addLeadFieldList" => false,
                "addIsRequired"    => false,
                "addDefaultValue"  => false,
                "addSaveResult"    => true
            ]
        ]);

        $event->addValidator("plugin.cap.validator", [
            "eventName" => CaptchaEvents::CAP_ON_FORM_VALIDATE,
            "fieldType" => "plugin.cap"
        ]);
    }

    /**
     * <h2>onFormValidate</h2>
     *
     * @param ValidationEvent $event
     *
     * @throws GuzzleException
     * @throws JsonException
     *
     * @return void
     */
    public function onFormValidate(ValidationEvent $event) {
        if(!$this->isConfigured)
            return;

        if($this->capClient->verify($_POST["cap-token"] ?? ""))
            return;

        $event->failedValidation($this->translator === null ? "Cap CAPTCHA was not successful." : $this->translator->trans("strings.cap.failure_message"));

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
