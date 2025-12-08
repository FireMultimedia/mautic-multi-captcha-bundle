# Implementation Plan: Altcha Integration

- [x] 1. Add Composer dependency and update configuration





  - Add `altcha-org/altcha` to composer.json require section
  - Update bundle version in Config/config.php
  - Add Altcha event constant to CaptchaEvents.php
  - _Requirements: 6.5, 5.1_


- [x] 2. Implement AltchaIntegration class








  - Create Integration/AltchaIntegration.php extending AbstractIntegration
  - Implement getName() returning "Altcha"
  - Implement getDisplayName() returning "Altcha"
  - Implement getAuthenticationType() returning "none"
  - Implement getRequiredKeyFields() with hmac_key field
  - _Requirements: 1.1, 1.2, 5.2_


- [x] 2.1 Write property test for HMAC-Key persistence







  - **Property 1: HMAC-Key Persistence**
  - **Validates: Requirements 1.3**

- [x] 3. Implement AltchaClient service




  - Create Service/AltchaClient.php
  - Implement constructor with IntegrationHelper dependency
  - Load HMAC-Key from integration configuration
  - Implement createChallenge(int $maxNumber, int $expiresInSeconds): array method
  - Implement verify(string $payload): bool method
  - Add error handling for missing HMAC-Key
  - Add error handling for missing Altcha library
  - Add logging for errors
  - _Requirements: 3.2, 3.3, 4.1, 4.2, 4.3, 7.2, 7.3, 7.4_

- [x] 3.1 Write property test for challenge structure completeness


  - **Property 4: Challenge Structure Completeness**
  - **Validates: Requirements 3.2, 3.3**

- [x] 3.2 Write property test for valid payload acceptance

  - **Property 5: Valid Payload Acceptance**
  - **Validates: Requirements 4.1, 4.2**

- [x] 3.3 Write property test for invalid payload rejection



  - **Property 6: Invalid Payload Rejection**
  - **Validates: Requirements 4.3**

- [x] 4. Implement AltchaType form type





  - Create Form/Type/AltchaType.php extending AbstractType
  - Implement buildForm() method
  - Add maxNumber field (NumberType) with validation (1000-1000000)
  - Add expires field (NumberType) with validation (10-300)
  - Add invisible field (YesNoButtonGroupType)
  - Set default values: maxNumber=50000, expires=120, invisible=false
  - Implement getBlockPrefix() returning "Altcha"
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 4.1 Write property test for maxNumber range validation


  - **Property 2: maxNumber Range Validation**
  - **Validates: Requirements 2.4**

- [x] 4.2 Write property test for expires range validation


  - **Property 3: Expires Range Validation**
  - **Validates: Requirements 2.5**

- [x] 5. Implement AltchaFormSubscriber event listener





  - Create EventListener/AltchaFormSubscriber.php implementing EventSubscriberInterface
  - Implement constructor with dependencies: EventDispatcherInterface, AltchaClient, LeadModel, IntegrationHelper
  - Load integration configuration and check if configured
  - Implement getSubscribedEvents() subscribing to FormEvents::FORM_ON_BUILD and CaptchaEvents::ALTCHA_ON_FORM_VALIDATE
  - Implement onFormBuild() method to register Altcha field
  - Implement onFormValidate() method to verify payload
  - Add lead cleanup logic after failed validation
  - _Requirements: 2.1, 4.1, 4.2, 4.3, 7.1, 7.5_

- [x] 5.1 Write property test for lead cleanup after failed validation


  - **Property 7: Lead Cleanup After Failed Validation**
  - **Validates: Requirements 7.5**

- [x] 6. Create Altcha widget template





  - Create Resources/views/Integration/altcha.html.twig
  - Include field_helper block from existing templates
  - Generate challenge data using AltchaClient
  - Render altcha-widget custom element
  - Add script tag for Altcha widget from CDN
  - Configure widget with challenge data
  - Handle invisible mode configuration
  - Add hidden input field for payload
  - Add error message container
  - _Requirements: 3.1, 3.2, 3.3, 3.6, 8.1_

- [x] 6.1 Write property test for local resource loading


  - **Property 8: Local Resource Loading**
  - **Validates: Requirements 8.1**

- [x] 7. Register services in configuration




  - Update Config/config.php to register AltchaClient service
  - Register AltchaFormSubscriber event listener
  - Register AltchaIntegration in integrations section
  - Configure service dependencies
  - _Requirements: 5.2, 5.3_

- [x] 8. Add translations





  - Update Translations/en_US/messages.ini
  - Add mautic.form.field.type.plugin.altcha
  - Add strings.altcha.plugin.name
  - Add strings.altcha.settings.hmac_key
  - Add strings.altcha.settings.max_number and tooltip
  - Add strings.altcha.settings.expires and tooltip
  - Add strings.altcha.settings.invisible and tooltip
  - Add strings.altcha.failure_message
  - Add strings.altcha.expired_message
  - Add strings.altcha.config_error
  - _Requirements: 5.5, 7.1_

- [x] 9. Add plugin icon




  - Add Assets/img/altcha.png icon file
  - Ensure icon follows same dimensions as other CAPTCHA icons
  - _Requirements: 5.1_

- [x] 10. Update CaptchaEvents class




  - Add ALTCHA_ON_FORM_VALIDATE constant to CaptchaEvents.php
  - _Requirements: 5.3_

- [x] 11. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Create German translations





  - Create Translations/de_DE/messages.ini
  - Translate all Altcha-related strings to German
  - _Requirements: 5.5_

- [x] 13. Update README documentation




  - Add Altcha section to README.md
  - Document configuration steps
  - Add screenshots for configuration and usage
  - Document invisible mode feature
  - Add GDPR compliance notes
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 14. Final checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.
