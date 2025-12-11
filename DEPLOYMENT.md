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
- `DEPLOY_PRIVATE_KEY` - SSH Private Key für Authentifizierung

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

3. Füge den Private Key als GitLab CI/CD Variable `DEPLOY_PRIVATE_KEY` hinzu

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