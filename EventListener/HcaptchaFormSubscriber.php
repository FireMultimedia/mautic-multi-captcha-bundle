<?php declare(strict_types=1);

namespace MauticPlugin\MauticMultiCaptchaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use \JsonException;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\CoreBundle\Exception\BadConfigurationException;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\FormEvents;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\LeadEvents;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\HcaptchaType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\HcaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Service\HcaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;

/**
 * <h1>Class HcaptchaFormSubscriber</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\EventListener
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class HcaptchaFormSubscriber implements EventSubscriberInterface {

    public const MODEL_NAME_KEY_LEAD = "lead.lead";

    private TranslatorInterface $translator;

    private bool $isConfigured = false;

    private ?string $siteKey;

    /**
     * <h2>HcaptchaFormSubscriber constructor.</h2>
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param HcaptchaClient           $hCaptchaClient
     * @param LeadModel                $leadModel
     * @param RequestStack             $requestStack
     * @param IntegrationHelper        $integrationHelper
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly HcaptchaClient           $hCaptchaClient,
        private readonly LeadModel                $leadModel,
        private readonly RequestStack             $requestStack,

        IntegrationHelper $integrationHelper
    ) {
        $integrationObject = $integrationHelper->getIntegrationObject(HcaptchaIntegration::INTEGRATION_NAME);

        $this->translator = $integrationObject->getTranslator();

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->siteKey = $keys["site_key"] ?? null;

            if($this->siteKey && isset($keys["secret_key"]))
                $this->isConfigured = true;
        }
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            FormEvents::FORM_ON_BUILD                => ["onFormBuild", 0],
            CaptchaEvents::HCAPTCHA_ON_FORM_VALIDATE => ["onFormValidate", 0]
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

        $event->addFormField("plugin.hcaptcha", [
            "label"    => "strings.hcaptcha.plugin.name",
            "formType" => HcaptchaType::class,
            "template" => "@MauticMultiCaptcha/Integration/hcaptcha.html.twig",
            "site_key" => $this->siteKey,

            "stringBag" => [
                "accept_cookies"              => $this->translator->trans("strings.hcaptcha.accept_cookies"),
                "accept_cookies.notice"       => $this->translator->trans("strings.accept_cookies.notice"),
                "accept_cookies.notice.value" => $this->translator->trans("strings.hcaptcha.accept_cookies.notice.value")
            ],

            "builderOptions" => [
                "addLeadFieldList" => false,
                "addIsRequired"    => false,
                "addDefaultValue"  => false,
                "addSaveResult"    => true
            ]
        ]);

        $event->addValidator("plugin.hcaptcha.validator", [
            "eventName" => CaptchaEvents::HCAPTCHA_ON_FORM_VALIDATE,
            "fieldType" => "plugin.hcaptcha"
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

        if($this->hCaptchaClient->verify($_POST["h-captcha-response"] ?? "", $this->requestStack->getCurrentRequest()?->getClientIp()))
            return;

        $event->failedValidation($this->translator === null ? "hCaptcha was not successful." : $this->translator->trans("strings.hcaptcha.failure_message"));

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
