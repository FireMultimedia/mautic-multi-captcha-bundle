# Requirements Document

## Introduction

Diese Spezifikation beschreibt die Integration von Altcha als zusätzliche CAPTCHA-Option in das bestehende Mautic Multi-CAPTCHA Bundle. Altcha ist eine datenschutzfreundliche, selbst-gehostete CAPTCHA-Lösung, die eine lokale Server-Validierung ohne externe API-Aufrufe ermöglicht. Im Gegensatz zu anderen CAPTCHA-Lösungen werden keine externen Skripte oder Validierungen verwendet - alle Operationen laufen lokal auf dem Server. Dadurch ist aus DSGVO-Sicht kein Explicit Consent erforderlich. Die Integration folgt dem etablierten Muster der bestehenden CAPTCHA-Implementierungen (reCAPTCHA, hCaptcha, Turnstile) und unterstützt Mautic 5, 6 und 7.

## Glossary

- **Altcha**: Eine Open-Source CAPTCHA-Lösung mit lokaler Server-Validierung
- **Challenge**: Eine kryptographische Aufgabe, die vom Server generiert und vom Client gelöst wird
- **HMAC Key**: Ein geheimer Schlüssel zur Signierung und Validierung von Altcha-Challenges
- **Payload**: Die vom Client zurückgegebene Lösung einer Challenge, enthält algorithm, challenge, number, salt und signature
- **Widget**: Die clientseitige JavaScript-Komponente, die das Altcha-CAPTCHA im Formular rendert
- **Integration Object**: Eine Mautic-Klasse, die Plugin-Konfiguration und -Authentifizierung verwaltet
- **Form Subscriber**: Ein Event-Listener, der Formular-Events abfängt und CAPTCHA-Validierung durchführt
- **Client Service**: Ein Service, der die Kommunikation mit der CAPTCHA-API oder -Bibliothek kapselt
- **Form Type**: Eine Symfony-Form-Klasse, die die Konfigurationsoptionen für ein Formularfeld definiert
- **Mautic System**: Die Mautic Marketing-Automation-Plattform

## Requirements

### Requirement 1

**User Story:** Als Mautic-Administrator möchte ich Altcha als CAPTCHA-Option konfigurieren können, damit ich eine datenschutzfreundliche CAPTCHA-Lösung ohne externe Abhängigkeiten nutzen kann.

#### Acceptance Criteria

1. WHEN ein Administrator die Plugin-Seite aufruft THEN das Mautic System SHALL eine Altcha-Integration in der Plugin-Liste anzeigen
2. WHEN ein Administrator auf die Altcha-Integration klickt THEN das Mautic System SHALL ein Konfigurationsformular mit HMAC-Key-Feld anzeigen
3. WHEN ein Administrator einen HMAC-Key eingibt und speichert THEN das Mautic System SHALL die Konfiguration persistent speichern
4. WHEN ein Administrator die Integration aktiviert THEN das Mautic System SHALL die Altcha-Option in Formular-Feldern verfügbar machen
5. WHEN kein HMAC-Key konfiguriert ist THEN das Mautic System SHALL die Altcha-Option in Formularen nicht anzeigen

### Requirement 2

**User Story:** Als Mautic-Formular-Editor möchte ich ein Altcha-Feld zu meinen Formularen hinzufügen können, damit ich Spam-Submissions verhindern kann.

#### Acceptance Criteria

1. WHEN die Altcha-Integration konfiguriert ist THEN das Mautic System SHALL ein "Altcha" Feld in der Formularfeld-Liste anzeigen
2. WHEN ein Editor ein Altcha-Feld zum Formular hinzufügt THEN das Mautic System SHALL das Feld mit Standard-Eigenschaften erstellen
3. WHEN ein Editor die Eigenschaften des Altcha-Feldes öffnet THEN das Mautic System SHALL Konfigurationsoptionen für maxNumber, Challenge-Ablaufzeit und Invisible-Modus anzeigen
4. WHEN ein Editor die maxNumber-Eigenschaft ändert THEN das Mautic System SHALL den Wert zwischen 1000 und 1000000 validieren
5. WHEN ein Editor die Challenge-Ablaufzeit ändert THEN das Mautic System SHALL den Wert zwischen 10 und 300 Sekunden validieren
6. WHEN ein Editor den Invisible-Modus aktiviert THEN das Mautic System SHALL die Konfiguration speichern und das Widget im unsichtbaren Modus rendern

### Requirement 3

**User Story:** Als Website-Besucher möchte ich ein Altcha-Widget im Formular sehen, damit ich das Formular nach erfolgreicher Challenge-Lösung absenden kann.

#### Acceptance Criteria

1. WHEN ein Formular mit Altcha-Feld geladen wird THEN das Mautic System SHALL das Altcha-Widget-JavaScript von lokalen Ressourcen einbinden
2. WHEN das Widget geladen wird THEN das Mautic System SHALL eine Challenge mit konfigurierten Parametern lokal generieren
3. WHEN die Challenge generiert wird THEN das Mautic System SHALL algorithm, challenge, salt und signature ohne externe API-Aufrufe zurückgeben
4. WHEN der Benutzer die Challenge löst THEN das Widget SHALL den Payload mit der Lösung an das Formular übergeben
5. WHEN das Formular ohne gelöste Challenge abgesendet wird THEN das Mautic System SHALL die Submission ablehnen
6. WHEN der Invisible-Modus aktiviert ist THEN das Mautic System SHALL das Widget unsichtbar rendern und automatisch die Challenge lösen

### Requirement 4

**User Story:** Als Mautic-System möchte ich Altcha-Payloads serverseitig validieren, damit nur legitime Form-Submissions akzeptiert werden.

#### Acceptance Criteria

1. WHEN ein Formular mit Altcha-Payload abgesendet wird THEN das Mautic System SHALL die Altcha-Bibliothek zur Validierung verwenden
2. WHEN der Payload gültig ist THEN das Mautic System SHALL die Form-Submission akzeptieren
3. WHEN der Payload ungültig ist THEN das Mautic System SHALL die Form-Submission ablehnen und eine Fehlermeldung anzeigen
4. WHEN die Challenge abgelaufen ist THEN das Mautic System SHALL die Form-Submission ablehnen
5. WHEN die Signatur nicht übereinstimmt THEN das Mautic System SHALL die Form-Submission ablehnen

### Requirement 5

**User Story:** Als Entwickler möchte ich, dass die Altcha-Integration dem bestehenden Code-Muster folgt, damit die Wartbarkeit und Konsistenz des Bundles gewährleistet ist.

#### Acceptance Criteria

1. WHEN die Altcha-Integration implementiert wird THEN das Mautic System SHALL die gleiche Verzeichnisstruktur wie bestehende Integrationen verwenden
2. WHEN Services registriert werden THEN das Mautic System SHALL die gleichen Dependency-Injection-Muster wie bestehende Services verwenden
3. WHEN Event-Listener registriert werden THEN das Mautic System SHALL die gleichen Event-Subscription-Muster wie bestehende Listener verwenden
4. WHEN Templates erstellt werden THEN das Mautic System SHALL die gleiche Twig-Template-Struktur wie bestehende Templates verwenden
5. WHEN Übersetzungen hinzugefügt werden THEN das Mautic System SHALL die gleiche Translation-Struktur wie bestehende Übersetzungen verwenden

### Requirement 6

**User Story:** Als Mautic-Administrator möchte ich, dass die Altcha-Integration mit Mautic 5, 6 und 7 kompatibel ist, damit ich sie unabhängig von meiner Mautic-Version nutzen kann.

#### Acceptance Criteria

1. WHEN die Altcha-Integration in Mautic 5 installiert wird THEN das Mautic System SHALL alle Funktionen korrekt bereitstellen
2. WHEN die Altcha-Integration in Mautic 6 installiert wird THEN das Mautic System SHALL alle Funktionen korrekt bereitstellen
3. WHEN die Altcha-Integration in Mautic 7 installiert wird THEN das Mautic System SHALL alle Funktionen korrekt bereitstellen
4. WHEN die Version-spezifische Service-Konfiguration geladen wird THEN das Mautic System SHALL die korrekten Argumente für die jeweilige Mautic-Version verwenden
5. WHEN Composer-Abhängigkeiten aufgelöst werden THEN das Mautic System SHALL die altcha-org/altcha-Bibliothek erfolgreich installieren

### Requirement 7

**User Story:** Als Mautic-Administrator möchte ich aussagekräftige Fehlermeldungen erhalten, damit ich Probleme mit der Altcha-Integration schnell beheben kann.

#### Acceptance Criteria

1. WHEN die Altcha-Validierung fehlschlägt THEN das Mautic System SHALL eine übersetzte Fehlermeldung anzeigen
2. WHEN die Altcha-Bibliothek nicht installiert ist THEN das Mautic System SHALL eine klare Fehlermeldung mit Installationsanweisungen anzeigen
3. WHEN der HMAC-Key fehlt oder ungültig ist THEN das Mautic System SHALL eine Fehlermeldung in der Plugin-Konfiguration anzeigen
4. WHEN eine Challenge-Generierung fehlschlägt THEN das Mautic System SHALL den Fehler loggen und eine Fallback-Nachricht anzeigen
5. WHEN ein Lead nach fehlgeschlagener Validierung erstellt wurde THEN das Mautic System SHALL den Lead automatisch löschen

### Requirement 8

**User Story:** Als Mautic-System möchte ich sicherstellen, dass Altcha DSGVO-konform ohne externe Abhängigkeiten arbeitet, damit keine Einwilligung des Benutzers erforderlich ist.

#### Acceptance Criteria

1. WHEN das Altcha-Widget geladen wird THEN das Mautic System SHALL keine externen Skripte von Drittanbieter-Domains laden
2. WHEN eine Challenge generiert wird THEN das Mautic System SHALL keine Daten an externe Server senden
3. WHEN eine Challenge validiert wird THEN das Mautic System SHALL die Validierung ausschließlich lokal auf dem Server durchführen
4. WHEN das Altcha-Widget initialisiert wird THEN das Mautic System SHALL keine Cookies oder Browser-Storage verwenden
5. WHEN die Altcha-Integration aktiv ist THEN das Mautic System SHALL keine Explicit-Consent-Option anzeigen, da keine externen Ressourcen verwendet werden
