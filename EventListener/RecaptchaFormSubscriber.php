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

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\RecaptchaType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\RecaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Service\RecaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;

/**
 * <h1>Class RecaptchaFormSubscriber</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\EventListener
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class RecaptchaFormSubscriber implements EventSubscriberInterface {

    public const MODEL_NAME_KEY_LEAD = "lead.lead";

    protected EventDispatcherInterface $eventDispatcher;

    protected RecaptchaClient $recaptchaClient;

    private LeadModel $leadModel;

    private TranslatorInterface $translator;

    private bool $recaptchaIsConfigured = false;

    private ?string $version;

    protected ?string $siteKey;

    protected ?string $secretKey;

    /**
     * <h2>FormSubscriber constructor.</h2>
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param IntegrationHelper        $integrationHelper
     * @param RecaptchaClient          $recaptchaClient
     * @param LeadModel                $leadModel
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        IntegrationHelper        $integrationHelper,
        RecaptchaClient          $recaptchaClient,
        LeadModel                $leadModel
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->recaptchaClient = $recaptchaClient;
        $this->leadModel       = $leadModel;

        $integrationObject = $integrationHelper->getIntegrationObject(RecaptchaIntegration::INTEGRATION_NAME);

        $this->translator = $integrationObject->getTranslator();

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->siteKey   = $keys["site_key"] ?? null;
            $this->secretKey = $keys["secret_key"] ?? null;
            $this->version   = $keys["version"] ?? null;

            if($this->siteKey && $this->secretKey)
                $this->recaptchaIsConfigured = true;
        }
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            FormEvents::FORM_ON_BUILD       => ["onFormBuild", 0],
            CaptchaEvents::ON_FORM_VALIDATE => ["onFormValidate", 0],
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
        if(!$this->recaptchaIsConfigured)
            return;

        $event->addFormField("plugin.recaptcha", [
            "label"    => "mautic.plugin.actions.recaptcha",
            "formType" => RecaptchaType::class,
            "template" => "@MauticMultiCaptcha/Integration/recaptcha.html.twig",
            "site_key" => $this->siteKey,
            "version"  => $this->version,

            "stringBag" => [
                "accept_cookies"              => $this->translator->trans("mautic.recaptcha.accept_cookies"),
                "accept_cookies.notice"       => $this->translator->trans("mautic.recaptcha.accept_cookies.notice"),
                "accept_cookies.notice.value" => $this->translator->trans("mautic.recaptcha.accept_cookies.notice.value")
            ],

            "builderOptions" => [
                "addLeadFieldList" => false,
                "addIsRequired"    => false,
                "addDefaultValue"  => false,
                "addSaveResult"    => true,
            ]
        ]);

        $event->addValidator("plugin.recaptcha.validator", [
            "eventName" => CaptchaEvents::ON_FORM_VALIDATE,
            "fieldType" => "plugin.recaptcha",
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
        if(!$this->recaptchaIsConfigured)
            return;

        if(!$this->recaptchaClient->verify($event->getValue(), $event->getField())) {
            $event->failedValidation($this->translator === null ? "reCAPTCHA was not successful." : $this->translator->trans("mautic.integration.recaptcha.failure_message"));

            return;
        }

        $this->eventDispatcher->addListener(LeadEvents::LEAD_POST_SAVE, function(LeadEvent $event) {
            if($event->isNew())
                $this->leadModel->deleteEntity($event->getLead());
        }, -255);
    }

}
