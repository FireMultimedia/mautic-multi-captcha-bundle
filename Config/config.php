<?php declare(strict_types=1);

use MauticPlugin\MauticMultiCaptchaBundle\EventListener\AltchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\HcaptchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\RecaptchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\TurnstileFormSubscriber;

use MauticPlugin\MauticMultiCaptchaBundle\Service\AltchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\HcaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\RecaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\TurnstileClient;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\AltchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\HcaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\RecaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;

use MauticPlugin\MauticMultiCaptchaBundle\Controller\AltchaApiController;



use Mautic\CoreBundle\Helper\AppVersion;

// assume that Mautic developers use sane versioning
$mauticVersion = str_replace(".", "", explode("-", (new AppVersion())->getVersion())[0]);

switch(true) {
    case $mauticVersion >= 600:
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
    case $mauticVersion >= 500:
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
    "description" => "Enables Google's reCAPTCHA, hCaptcha, Cloudflare Turnstile, and Altcha integration for Mautic",
    "version"     => "1.1.1",
    "author"      => "FireMultimedia B.V.",

    "routes" => [
        "public" => [
            "mautic_altcha_api_challenge" => [
                "path"       => "/altcha/api/challenge",
                "controller" => "mautic.altcha.controller.api:generateChallengeAction",
                "method"     => "GET"
            ]
        ]
    ],

    "services" => [
        "events" => [
            "mautic.altcha.event_listener.form_subscriber" => [
                "class" => AltchaFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.altcha.service.altcha_client",
                    "mautic.lead.model.lead",
                    "mautic.helper.integration"
                ]
            ],

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
            ]
        ],

        "models" => [

        ],

        "controllers" => [
            "mautic.altcha.controller.api" => [
                "class" => AltchaApiController::class,
                "arguments" => [
                    "mautic.altcha.service.altcha_client"
                ]
            ]
        ],

        "others" => [
            "mautic.altcha.service.altcha_client" => [
                "class" => AltchaClient::class,

                "arguments" => [
                    "mautic.helper.integration"
                ]
            ],

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
            ]
        ],

        "integrations" => [
            "mautic.integration.altcha" => [
                "class"     => AltchaIntegration::class,
                "arguments" => $defaultIntegrationArguments
            ],

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
            ]
        ]
    ],

    "parameters" => [

    ]
];
