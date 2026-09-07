<?php declare(strict_types=1);

use MauticPlugin\MauticMultiCaptchaBundle\EventListener\HcaptchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\RecaptchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\TurnstileFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\AltchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\CapFormSubscriber;

use MauticPlugin\MauticMultiCaptchaBundle\Service\HcaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\RecaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\TurnstileClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\CapClient;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\HcaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\RecaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\CapIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Controller\ChallengeController;
use MauticPlugin\MauticMultiCaptchaBundle\Controller\CapAssetController;

use Mautic\CoreBundle\Helper\AppVersion;

// assume that Mautic developers use sane versioning
$mauticVersion = str_replace(".", "", explode("-", (new AppVersion())->getVersion())[0]);

$mauticVersion = str_split((string)$mauticVersion);

switch(true) {
    case $mauticVersion[0] >= 7:
        $defaultIntegrationArguments = [
            "event_dispatcher",
            "mautic.helper.cache_storage",
            "doctrine.orm.entity_manager",
            "request_stack",
            "router",
            "translator",
            "monolog.logger.mautic",
            "mautic.helper.encryption",
            "mautic.lead.model.lead",
            "mautic.lead.model.company",
            "mautic.helper.paths",
            "mautic.core.model.notification",
            "mautic.lead.model.field",
            "mautic.plugin.model.integration_entity",
            "mautic.lead.model.dnc",
            "mautic.lead.field.fields_with_unique_identifier"
        ];
        break;
    case $mauticVersion[0] >= 6:
        $defaultIntegrationArguments = [
            "event_dispatcher",
            "mautic.helper.cache_storage",
            "doctrine.orm.entity_manager",
            "request_stack",
            "router",
            "translator",
            "monolog.logger.mautic",
            "mautic.helper.encryption",
            "mautic.lead.model.lead",
            "mautic.lead.model.company",
            "mautic.helper.paths",
            "mautic.core.model.notification",
            "mautic.lead.model.field",
            "mautic.plugin.model.integration_entity",
            "mautic.lead.model.dnc",
            "mautic.lead.field.fields_with_unique_identifier"
        ];
        break;
    case $mauticVersion[0] >= 5:
        $defaultIntegrationArguments = [
            "event_dispatcher",
            "mautic.helper.cache_storage",
            "doctrine.orm.entity_manager",
            "session",
            "request_stack",
            "router",
            "translator",
            "monolog.logger.mautic",
            "mautic.helper.encryption",
            "mautic.lead.model.lead",
            "mautic.lead.model.company",
            "mautic.helper.paths",
            "mautic.core.model.notification",
            "mautic.lead.model.field",
            "mautic.plugin.model.integration_entity",
            "mautic.lead.model.dnc",
            "mautic.lead.field.fields_with_unique_identifier"
        ];
        break;
    default:
        die("Plugin is not compatible with your Mautic version. Please remove MauticMultiCaptchaBundle");
}

return [
    "name"        => "MultiCAPTCHA",
    "description" => "Enables Google's reCAPTCHA, hCaptcha, Cloudflare Turnstile, ALTCHA, and Cap integration for Mautic",
    "version"     => "1.0.8",
    "author"      => "FireMultimedia B.V.",

    "routes" => [
        "public" => [
            "mautic_altcha_challenge" => [
                "path"       => "/altcha/challenge",
                "controller" => ChallengeController::class // invokable - see ChallengeController::__invoke()
            ],

            "mautic_cap_api_wasm" => [
                "path"       => "/cap/api/wasm",
                "controller" => "mautic.cap.controller.asset:wasmAction",
                "method"     => "GET"
            ]
        ]
    ],

    "services" => [
        "events" => [
            "mautic.hcaptcha.event_listener.form_subscriber" => [
                "class" => HcaptchaFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.hcaptcha.service.hcaptcha_client",
                    "mautic.lead.model.lead",
                    "request_stack",
                    "mautic.helper.integration"
                ]
            ],

            "mautic.recaptcha.event_listener.form_subscriber" => [
                "class" => RecaptchaFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.recaptcha.service.recaptcha_client",
                    "mautic.lead.model.lead",
                    "mautic.helper.integration"
                ]
            ],

            "mautic.turnstile.event_listener.form_subscriber" => [
                "class" => TurnstileFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.turnstile.service.turnstile_client",
                    "mautic.lead.model.lead",
                    "mautic.helper.integration"
                ]
            ],

            "mautic.altcha.event_listener.form_subscriber" => [
                "class" => AltchaFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.altcha.service.altcha_client",
                    "mautic.lead.model.lead",
                    "request_stack",
                    "mautic.helper.integration"
                ]
            ],

            "mautic.cap.event_listener.form_subscriber" => [
                "class" => CapFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.cap.service.cap_client",
                    "mautic.lead.model.lead",
                    "mautic.helper.integration"
                ]
            ]
        ],

        "models" => [

        ],

        "controllers" => [
            "mautic.cap.controller.asset" => [
                "class"     => CapAssetController::class,
                "arguments" => []
            ]
        ],

        "others" => [
            "mautic.hcaptcha.service.hcaptcha_client" => [
                "class" => HcaptchaClient::class,

                "arguments" => [
                    "mautic.helper.integration"
                ]
            ],

            "mautic.recaptcha.service.recaptcha_client" => [
                "class" => RecaptchaClient::class,

                "arguments" => [
                    "mautic.helper.integration"
                ]
            ],

            "mautic.turnstile.service.turnstile_client" => [
                "class" => TurnstileClient::class,

                "arguments" => [
                    "mautic.helper.integration"
                ]
            ],

            "mautic.altcha.service.altcha_client" => [
                "class" => AltchaClient::class,

                "arguments" => [
                    "mautic.helper.integration",
                    "router"
                ]
            ],

            "mautic.cap.service.cap_client" => [
                "class" => CapClient::class,

                "arguments" => [
                    "mautic.helper.integration"
                ]
            ]
        ],

        "integrations" => [
            "mautic.integration.hcaptcha" => [
                "class"     => HcaptchaIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ],

            "mautic.integration.recaptcha" => [
                "class"     => RecaptchaIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ],

            "mautic.integration.turnstile" => [
                "class"     => TurnstileIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ],

            "mautic.integration.altcha" => [
                "class"     => AltchaIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ],

            "mautic.integration.cap" => [
                "class"     => CapIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ]
        ]
    ],

    "parameters" => [

    ]
];
