# Deployment-Konfiguration

## GitLab CI/CD Pipeline

Die Pipeline besteht aus 4 Stages:

1. **test** - Führt PHPUnit-Tests aus
2. **package** - Erstellt ZIP-Paket und lädt es in die GitLab Package Registry
3. **deploy-sandbox** - Automatisches Deployment auf Sandbox (nur main branch)
4. **deploy-live** - Manuelles Deployment auf Live-System (main branch und tags)

## Erforderliche GitLab CI/CD Variablen

### Deployment-Host und Authentifizierung
- `DEPLOY_HOST` - Hostname/IP des Servers (gleich für Sandbox und Live)
- `DEPLOY_PRIVATE_KEY` - SSH Private Key für Authentifizierung (base64-kodiert mit `base64 -w0`)

### Sandbox-Konfiguration
- `SANDBOX_USER` - SSH-Benutzername für Sandbox
- `SANDBOX_PATH` - Pfad zum Mautic-Verzeichnis auf Sandbox (z.B. `/var/www/sandbox`)
- `SANDBOX_HOST` - Hostname der Sandbox für Environment-URL (optional)

### Live-Konfiguration
- `LIVE_USER` - SSH-Benutzername für Live-System
- `LIVE_PATH` - Pfad zum Mautic-Verzeichnis auf Live (z.B. `/var/www/live`)
- `LIVE_HOST` - Hostname des Live-Systems für Environment-URL (optional)

## Deployment-Verhalten

### Sandbox-Deployment
- **Trigger**: Automatisch bei Push auf `main` branch
- **Ziel**: `$SANDBOX_PATH/web/plugins/MauticMultiCaptchaBundle/`
- **Aktionen**:
  - Rsync des Plugin-Codes
  - Automatisches Cache-Clear (`php bin/console cache:clear --env=prod`)

### Live-Deployment
- **Trigger**: Manuell über GitLab UI
- **Verfügbar für**: `main` branch und Tags
- **Ziel**: `$LIVE_PATH/web/plugins/MauticMultiCaptchaBundle/`
- **Aktionen**:
  - Rsync des Plugin-Codes
  - Automatisches Cache-Clear (`php bin/console cache:clear --env=prod`)

## SSH-Key Setup

1. Generiere SSH-Key-Pair:
   ```bash
   ssh-keygen -t rsa -b 4096 -C "gitlab-ci@mautic-deployment"
   ```

2. Füge den Public Key zu den autorisierten Keys auf dem Server hinzu:
   ```bash
   cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
   ```

3. Kodiere den Private Key mit base64 und füge ihn als GitLab CI/CD Variable hinzu:
   ```bash
   base64 -w0 ~/.ssh/id_rsa
   ```
   Kopiere die Ausgabe und füge sie als `DEPLOY_PRIVATE_KEY` Variable hinzu

## Ausgeschlossene Dateien

Beim Deployment werden folgende Dateien/Ordner ausgeschlossen:
- `.git*` - Git-Dateien
- `Tests/` - Test-Dateien
- `phpunit.xml` - PHPUnit-Konfiguration
- `composer.lock` - Composer Lock-Datei
- `vendor/` - Vendor-Verzeichnis

## Manuelles Deployment

Für Live-Deployments:
1. Gehe zu GitLab → CI/CD → Pipelines
2. Wähle die gewünschte Pipeline
3. Klicke auf "Play" bei der `deploy-live` Stage

## Neue Features in Version 1.1.0

### ALTCHA API-Endpunkt
- **Neuer Endpunkt**: `/altcha/api/challenge`
- **Zweck**: Löst das Caching-Problem von ALTCHA-Challenges in Mautic-Formularen
- **Funktionalität**: Generiert dynamisch frische ALTCHA-Challenges als JSON
- **Parameter**:
  - `maxNumber` (optional): Maximale Zahl für Challenge (1000-1000000, Standard: 100000)
  - `expires` (optional): Gültigkeitsdauer in Sekunden (10-3600, Standard: 300)

### Template-Updates
- ALTCHA-Template nutzt jetzt JavaScript-basierte Challenge-Generierung
- Fallback auf Server-seitige Generierung bei API-Fehlern
- Verbesserte Cache-Vermeidung durch dynamische Widget-IDs

## Troubleshooting

### SSH-Verbindungsprobleme
- Prüfe SSH-Key-Format (keine Windows-Zeilenendings)
- Prüfe SSH-Key-Berechtigungen auf dem Server
- Prüfe Firewall-Einstellungen

### Rsync-Probleme
- Prüfe Pfad-Berechtigungen auf dem Zielserver
- Prüfe ob Zielverzeichnis existiert

### Cache-Clear-Probleme
- Prüfe PHP-CLI-Verfügbarkeit auf dem Server
- Prüfe Mautic-Pfad-Konfiguration
- Prüfe Dateiberechtigungen im Mautic-Verzeichnis

### ALTCHA API-Probleme
- Prüfe ob ALTCHA-Integration konfiguriert ist (HMAC-Key)
- Prüfe Browser-Konsole auf JavaScript-Fehler
- Teste API-Endpunkt direkt: `GET /altcha/api/challenge`
- Bei 503-Fehlern: ALTCHA-Konfiguration prüfen