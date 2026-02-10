# Cron Job Setup Dokumentation

Diese Datei beschreibt die notwendigen Cron Jobs für das Offer-System und ihre empfohlenen Ausführungsintervalle.

## Übersicht der Cron Jobs

### 1. send_birthday_wishes.php
**Pfad:** `/path/to/offer/cron/send_birthday_wishes.php`

**Beschreibung:** Sendet automatisch Geburtstagswünsche an alle Benutzer, die heute Geburtstag haben.

**Empfohlenes Intervall:** Täglich um 9:00 Uhr morgens

**Begründung:** 
- Morgens ist ideal, damit die Glückwünsche am Anfang des Tages ankommen
- 9:00 Uhr ist früh genug, aber nicht zu früh (Bürozeiten)
- Eine tägliche Ausführung ist ausreichend, da Geburtstage nur einmal täglich geprüft werden müssen

**Crontab Eintrag:**
```bash
0 9 * * * /usr/bin/php /path/to/offer/cron/send_birthday_wishes.php >> /var/log/birthday_wishes.log 2>&1
```

**Alternative Zeit:** 
- 8:00 Uhr: `0 8 * * *` (früher am Morgen)
- 10:00 Uhr: `0 10 * * *` (später am Vormittag)

---

### 2. send_alumni_reminders.php
**Pfad:** `/path/to/offer/cron/send_alumni_reminders.php`

**Beschreibung:** Sendet Erinnerungs-E-Mails an Alumni, deren Profile seit über einem Jahr nicht verifiziert wurden. Das Skript verarbeitet maximal 20 E-Mails pro Ausführung.

**Empfohlenes Intervall:** Wöchentlich, jeden Montag um 10:00 Uhr

**Begründung:**
- Wöchentliche Ausführung ist ausreichend für Profile-Erinnerungen
- Montags ist ideal für den Start der Woche
- Mit der Limitierung von 20 E-Mails pro Ausführung wird der SMTP-Server nicht überlastet
- Bei vielen ausstehenden Profilen werden diese über mehrere Wochen verteilt

**Crontab Eintrag:**
```bash
0 10 * * 1 /usr/bin/php /path/to/offer/cron/send_alumni_reminders.php >> /var/log/alumni_reminders.log 2>&1
```

**Alternative Intervalle:**
- Täglich: `0 10 * * *` (bei vielen ausstehenden Profilen)
- Zweiwöchentlich: `0 10 * * 1` (nur jede zweite Woche, zusätzliche Logik erforderlich)

---

### 3. sync_easyverein.php
**Pfad:** `/path/to/offer/cron/sync_easyverein.php`

**Beschreibung:** Synchronisiert Inventardaten von der EasyVerein API zur lokalen Datenbank.

**Empfohlenes Intervall:** Alle 30 Minuten

**Begründung:**
- Regelmäßige Synchronisation hält die Daten aktuell
- 30 Minuten ist ein guter Kompromiss zwischen Aktualität und Server-Last
- API-Rate-Limits werden nicht überschritten
- Wie im Skript selbst dokumentiert (siehe Zeile 6-9)

**Crontab Eintrag:**
```bash
*/30 * * * * /usr/bin/php /path/to/offer/cron/sync_easyverein.php >> /var/log/easyverein_sync.log 2>&1
```

**Alternative Intervalle:**
- Stündlich: `0 * * * *` (bei seltenen Änderungen)
- Alle 15 Minuten: `*/15 * * * *` (bei hoher Änderungsfrequenz)

---

## Installation

### 1. Crontab bearbeiten
```bash
crontab -e
```

### 2. Alle drei Einträge hinzufügen
Fügen Sie die folgenden Zeilen hinzu (Pfade entsprechend anpassen):

```bash
# Geburtstagswünsche - Täglich um 9:00 Uhr
0 9 * * * /usr/bin/php /path/to/offer/cron/send_birthday_wishes.php >> /var/log/birthday_wishes.log 2>&1

# Alumni Erinnerungen - Wöchentlich, Montags um 10:00 Uhr
0 10 * * 1 /usr/bin/php /path/to/offer/cron/send_alumni_reminders.php >> /var/log/alumni_reminders.log 2>&1

# EasyVerein Sync - Alle 30 Minuten
*/30 * * * * /usr/bin/php /path/to/offer/cron/sync_easyverein.php >> /var/log/easyverein_sync.log 2>&1
```

### 3. Wichtige Hinweise

**Pfade anpassen:**
- Ersetzen Sie `/path/to/offer` mit dem tatsächlichen Pfad zum Offer-Verzeichnis
- Stellen Sie sicher, dass `/usr/bin/php` der korrekte PHP-Pfad ist (prüfen mit `which php`)
- Log-Verzeichnisse müssen existieren und schreibbar sein

**Log-Verzeichnisse erstellen:**
```bash
sudo mkdir -p /var/log
sudo touch /var/log/birthday_wishes.log
sudo touch /var/log/alumni_reminders.log
sudo touch /var/log/easyverein_sync.log
sudo chown www-data:www-data /var/log/*.log
sudo chmod 644 /var/log/*.log
```

**PHP-Pfad prüfen:**
```bash
which php
# Ausgabe z.B.: /usr/bin/php oder /usr/local/bin/php
```

**Crontab Syntax:**
```
*    *    *    *    *
┬    ┬    ┬    ┬    ┬
│    │    │    │    └─── Wochentag (0-7, 0 und 7 = Sonntag)
│    │    │    └──────── Monat (1-12)
│    │    └───────────── Tag (1-31)
│    └────────────────── Stunde (0-23)
└─────────────────────── Minute (0-59)
```

---

## Status Überprüfung

Nutzen Sie das `check_cron_status.php` Skript im Browser, um zu überprüfen, wann die Cron Jobs zuletzt ausgeführt wurden:

```
https://ihre-domain.de/check_cron_status.php
```

Das Skript zeigt die letzte Ausführungszeit jedes Cron Jobs und etwaige Fehler an.

---

## Fehlerbehebung

### Cron Jobs laufen nicht
1. Prüfen Sie, ob cron läuft: `sudo service cron status`
2. Prüfen Sie die crontab: `crontab -l`
3. Prüfen Sie die Log-Dateien auf Fehler
4. Prüfen Sie PHP-Pfad und Skript-Berechtigungen

### E-Mails werden nicht gesendet
1. Prüfen Sie SMTP-Konfiguration in `config/config.php`
2. Prüfen Sie die Log-Dateien auf Fehlermeldungen
3. Testen Sie manuell: `php cron/send_birthday_wishes.php`

### Logs sind zu groß
Implementieren Sie Log-Rotation mit logrotate:
```bash
sudo nano /etc/logrotate.d/offer-cron
```

Inhalt:
```
/var/log/birthday_wishes.log
/var/log/alumni_reminders.log
/var/log/easyverein_sync.log
{
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```
