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

use MauticPlugin\MauticMultiCaptchaBundle\Form\Type\TurnstileType;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Service\TurnstileClient;
use MauticPlugin\MauticMultiCaptchaBundle\CaptchaEvents;

/**
 * <h1>Class TurnstileFormSubscriber</h1>
 *
 * @package MauticPlugin\MauticMultiCaptchaBundle\EventListener
 *
 * @authors see: composer.json
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
class TurnstileFormSubscriber implements EventSubscriberInterface {

    public const MODEL_NAME_KEY_LEAD = "lead.lead";

    protected EventDispatcherInterface $eventDispatcher;

    protected TurnstileClient $turnstileClient;

    private LeadModel $leadModel;

    private TranslatorInterface $translator;

    private bool $turnstileIsConfigured = false;

    protected ?string $siteKey;

    protected ?string $secretKey;

    /**
     * <h2>FormSubscriber constructor.</h2>
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param IntegrationHelper        $integrationHelper
     * @param TurnstileClient          $turnstileClient
     * @param LeadModel                $leadModel
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        IntegrationHelper        $integrationHelper,
        TurnstileClient          $turnstileClient,
        LeadModel                $leadModel
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->turnstileClient = $turnstileClient;
        $this->leadModel       = $leadModel;

        $integrationObject = $integrationHelper->getIntegrationObject(TurnstileIntegration::INTEGRATION_NAME);

        $this->translator = $integrationObject->getTranslator();

        if($integrationObject instanceof AbstractIntegration) {
            $keys = $integrationObject->getKeys();

            $this->siteKey   = $keys["site_key"] ?? null;
            $this->secretKey = $keys["secret_key"] ?? null;

            if($this->siteKey && $this->secretKey)
                $this->turnstileIsConfigured = true;
        }
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            FormEvents::FORM_ON_BUILD                 => ["onFormBuild", 0],
            CaptchaEvents::TURNSTILE_ON_FORM_VALIDATE => ["onFormValidate", 0],
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
        if(!$this->turnstileIsConfigured)
            return;

        $event->addFormField("plugin.turnstile", [
            "label"    => "strings.turnstile.plugin.name",
            "formType" => TurnstileType::class,
            "template" => "@MauticMultiCaptcha/Integration/turnstile.html.twig",
            "site_key" => $this->siteKey,

            "stringBag" => [
                "accept_cookies"              => $this->translator->trans("strings.turnstile.accept_cookies"),
                "accept_cookies.notice"       => $this->translator->trans("strings.turnstile.accept_cookies.notice"),
                "accept_cookies.notice.value" => $this->translator->trans("strings.turnstile.accept_cookies.notice.value")
            ],

            "builderOptions" => [
                "addLeadFieldList" => false,
                "addIsRequired"    => false,
                "addDefaultValue"  => false,
                "addSaveResult"    => true,
            ]
        ]);

        $event->addValidator("plugin.turnstile.validator", [
            "eventName" => CaptchaEvents::TURNSTILE_ON_FORM_VALIDATE,
            "fieldType" => "plugin.turnstile",
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
        if(!$this->turnstileIsConfigured)
            return;

        if(!$this->turnstileClient->verify($_POST["cf-turnstile-response"])) {
            $event->failedValidation($this->translator === null ? "Turnstile was not successful." : $this->translator->trans("strings.turnstile.failure_message"));

            return;
        }

        $this->eventDispatcher->addListener(LeadEvents::LEAD_POST_SAVE, function(LeadEvent $event) {
            if($event->isNew())
                $this->leadModel->deleteEntity($event->getLead());
        }, -255);
    }

}
