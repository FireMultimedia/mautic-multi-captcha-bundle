<?php declare(strict_types=1);

use MauticPlugin\MauticMultiCaptchaBundle\EventListener\RecaptchaFormSubscriber;
use MauticPlugin\MauticMultiCaptchaBundle\EventListener\TurnstileFormSubscriber;

use MauticPlugin\MauticMultiCaptchaBundle\Service\RecaptchaClient;
use MauticPlugin\MauticMultiCaptchaBundle\Service\TurnstileClient;

use MauticPlugin\MauticMultiCaptchaBundle\Integration\RecaptchaIntegration;
use MauticPlugin\MauticMultiCaptchaBundle\Integration\TurnstileIntegration;

return [
    "name"        => "MultiCAPTCHA",
    "description" => "Enables Google's reCAPTCHA, hCaptcha, and Cloudflare Turnstile integration for Mautic",
    "version"     => "1.0.0",
    "author"      => "FireMultimedia B.V.",

    "routes" => [

    ],

    "services" => [
        "events" => [
            "mautic.recaptcha.event_listener.form_subscriber" => [
                "class" => RecaptchaFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.helper.integration",
                    "mautic.recaptcha.service.recaptcha_client",
                    "mautic.lead.model.lead"
                ]
            ],

            "mautic.turnstile.event_listener.form_subscriber" => [
                "class" => TurnstileFormSubscriber::class,

                "arguments" => [
                    "event_dispatcher",
                    "mautic.helper.integration",
                    "mautic.turnstile.service.turnstile_client",
                    "mautic.lead.model.lead"
                ]
            ]
        ],

        "models" => [

        ],

        "others" => [
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
            "mautic.integration.recaptcha" => [
                "class" => RecaptchaIntegration::class,

                "arguments" => [
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
                ]
            ],

            "mautic.integration.turnstile" => [
                "class" => TurnstileIntegration::class,

                "arguments" => [
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
                ]
            ]
        ]
    ],

    "parameters" => [

    ]
];
