# Altcha Caching Problem - Lösung

## Problem
Das Altcha-Challenge wurde beim `FormEvents::FORM_ON_BUILD` Event generiert und dann zusammen mit dem Formular gecacht. Dies führte dazu, dass das Challenge nur so lange funktionierte, bis die Gültigkeit abgelaufen war. Auch die Twig-Extension-Lösung konnte das Caching-Problem nicht vollständig lösen.

## Finale Lösung: API-Endpunkt
Die endgültige Lösung verwendet einen **separaten API-Endpunkt**, der das Challenge dynamisch als JSON zurückgibt. Das Template lädt das Challenge per JavaScript nach dem Rendern, wodurch das Caching-Problem vollständig umgangen wird.

### Implementierte Komponenten

1. **AltchaApiController** (`Controller/AltchaApiController.php`)
   - API-Endpunkt für dynamische Challenge-Generierung
   - Route: `/altcha/api/challenge`
   - Parameter: `maxNumber`, `expires`
   - Robuste Fehlerbehandlung und Validierung

2. **Template-Update** (`Resources/views/Integration/altcha.html.twig`)
   - JavaScript-basierte Challenge-Generierung
   - Lädt Challenge per Fetch-API vom neuen Endpunkt
   - Fallback auf Server-seitige Generierung bei Fehlern
   - Cache-Vermeidung durch dynamische Widget-IDs

3. **Route-Registrierung** (`Config/config.php`)
   - Neue öffentliche Route für API-Endpunkt
   - Controller als Service registriert

4. **AltchaExtension** (`Twig/AltchaExtension.php`) - Fallback
   - Twig-Extension als Fallback-Mechanismus
   - Wird nur bei API-Fehlern verwendet

### Vorteile der Lösung

- **Vollständige Cache-Umgehung**: Challenge wird nach dem Template-Rendering per JavaScript geladen
- **Immer frisch**: Jeder Formular-Aufruf erhält ein neues, gültiges Challenge
- **Robust**: Fallback auf Server-seitige Generierung bei JavaScript-Fehlern
- **Performance**: Asynchrones Laden verhindert Render-Blocking
- **Sicher**: Jedes Challenge hat eine eigene Gültigkeit
- **API-basiert**: Saubere Trennung von Template und Challenge-Generierung

### Technische Details

Die API-basierte Lösung funktioniert folgendermaßen:
1. Template wird mit ALTCHA-Widget und `challengeurl` Attribut gerendert
2. ALTCHA-Widget lädt automatisch ein frisches Challenge vom API-Endpunkt
3. Bei Ablauf wird automatisch ein neues Challenge geladen (`refetchonexpire="true"`)
4. Kein zusätzliches JavaScript erforderlich - alles wird vom Widget selbst gehandhabt

### API-Endpunkt Details

- **URL**: `/altcha/api/challenge`
- **Methode**: GET
- **Parameter**: Keine (aus Sicherheitsgründen feste Werte)
  - `maxNumber`: 100000 (fest)
  - `expires`: 300 Sekunden (fest)
- **Response**: JSON mit Challenge-Daten oder Fehlermeldung

### Konfiguration

Die Extension wird automatisch in `Config/config.php` registriert:

```php
"mautic.altcha.twig.extension" => [
    "class" => \MauticPlugin\MauticMultiCaptchaBundle\Twig\AltchaExtension::class,
    "arguments" => [
        "mautic.altcha.service.altcha_client"
    ],
    "tags" => ["twig.extension"]
]
```

### Template-Verwendung

```twig
{# Widget mit challengeurl - ALTCHA lädt Challenge automatisch #}
<altcha-widget
    challengeurl="{{ path('mautic_altcha_api_challenge') }}"
    name="{{ inputAttributes.name }}"
    id="{{ inputAttributes.id }}"
    refetchonexpire="true"
    expire="300000"
></altcha-widget>
```

### API-Verwendung

```bash
# Challenge abrufen
curl "https://your-mautic.com/altcha/api/challenge"

# Response (direkt als Challenge-Objekt)
{
    "algorithm": "SHA-256",
    "challenge": "abc123...",
    "maxnumber": 100000,
    "salt": "def456...",
    "signature": "ghi789..."
}
```

## Test

Nach der Implementierung:
1. Formular aufrufen - Challenge wird per JavaScript geladen
2. Warten bis Challenge abläuft
3. Formular erneut aufrufen - neues Challenge wird automatisch geladen
4. Kein Caching-Problem mehr vorhanden