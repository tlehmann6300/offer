# Quick Start Guide - Alumni System

## Für Administratoren und Vorstände

Diese Anleitung erklärt, wie Sie die neuen Alumni-Funktionen verwenden.

---

## 🚀 Schnellstart in 3 Schritten

### Schritt 1: Alumni einladen

1. Gehen Sie zu **Admin → Benutzerverwaltung**
2. Scrollen Sie zum Formular "Neuen Benutzer einladen"
3. Geben Sie die E-Mail-Adresse ein
4. Wählen Sie **"Alumni"** als Rolle aus
5. Klicken Sie auf **"Einladung senden"**
6. Kopieren Sie den generierten Link und senden Sie ihn per E-Mail an die Person

**Screenshot-Position:**
```
┌────────────────────────────────────┐
│ Neuen Benutzer einladen            │
├────────────────────────────────────┤
│ E-Mail: [alumni@example.com     ]  │
│ Rolle:  [Alumni            ▼]      │
│         [✉️ Einladung senden]      │
└────────────────────────────────────┘
```

### Schritt 2: Alumni registriert sich

Die eingeladene Person:
1. Öffnet den Link
2. Sieht einen **Hinweis zur manuellen Freigabe**
3. Erstellt ein Passwort
4. Kann sich einloggen und hat **Lesezugriff** auf Inventar und Dashboard

**Status nach Registrierung:**
- ✅ Kann sich einloggen
- ✅ Kann Inventar ansehen
- ❌ Kann nichts bearbeiten
- ❌ Hat keinen Zugriff auf Alumni-Netzwerk-Daten

### Schritt 3: Alumni validieren (Vorstand)

1. Gehen Sie zu **Admin → Benutzerverwaltung**
2. Finden Sie den Alumni-Benutzer in der Liste
3. Sehen Sie das gelbe Badge **"🕐 Ausstehend"** in der Spalte "2FA / Validierung"
4. Klicken Sie auf **"🕐 Ausstehend"**
5. Das Badge wechselt zu **"✅ Verifiziert"**
6. Der Alumni hat nun vollen Lesezugriff

**Vorher:**
```
👤 alumni@test.de    [Alumni ▼]    🕐 Ausstehend
```

**Nachher:**
```
👤 alumni@test.de    [Alumni ▼]    ✅ Verifiziert
```

---

## 📋 Häufige Aufgaben

### Alumni-Validierung widerrufen

Wenn Sie die Validierung rückgängig machen möchten:
1. Gehen Sie zu **Admin → Benutzerverwaltung**
2. Klicken Sie auf das grüne **"✅ Verifiziert"** Badge
3. Der Status wechselt zurück zu **"🕐 Ausstehend"**
4. Der Alumni-Zugriff wird eingeschränkt

### Alumni zum Alumni-Vorstand befördern

1. Gehen Sie zu **Admin → Benutzerverwaltung**
2. Finden Sie den Alumni-Benutzer
3. Ändern Sie die Rolle von **"Alumni"** zu **"Alumni-Vorstand"**
4. Die Person hat nun Vorstandszugriff (Level 3)

### Neue Standorte verwenden

Die Standorte H-1.88 und H-1.87 sind automatisch verfügbar:

**Beim Artikel erstellen:**
1. Gehen Sie zu **Inventar → Neuer Artikel**
2. Im Dropdown "Standort" finden Sie:
   - Hauptbüro
   - Lager
   - Konferenzraum A
   - Werkstatt
   - **H-1.88** ⭐ NEU
   - **H-1.87** ⭐ NEU

---

## 🔐 Rollenhierarchie im Überblick

| Rolle | Level | Berechtigungen |
|-------|-------|----------------|
| **Alumni** | 1 | Lesen (benötigt Validierung für Alumni-Netzwerk) |
| Mitglied | 1 | Lesen |
| Ressortleiter | 2 | Lesen + Inventar bearbeiten |
| **Alumni-Vorstand** | 3 | Wie Vorstand + Alumni validieren |
| Vorstand | 3 | Lesen + Bearbeiten + Alumni validieren |
| Administrator | 4 | Vollzugriff |

---

## ❓ FAQ

### Kann ich mehrere Alumni gleichzeitig einladen?
Nein, jede Einladung muss einzeln erstellt werden. Dies stellt sicher, dass jeder Alumni einen einzigartigen, sicheren Token erhält.

### Wie lange ist ein Einladungslink gültig?
7 Tage. Nach Ablauf muss eine neue Einladung erstellt werden.

### Kann ein Alumni sich selbst verifizieren?
Nein. Die Validierung muss durch ein Vorstandsmitglied oder einen Administrator erfolgen.

### Was passiert, wenn ich einen Alumni nicht validiere?
Der Alumni kann sich einloggen und hat Lesezugriff auf Inventar und Dashboard, aber keinen Zugriff auf interne Alumni-Netzwerk-Daten.

### Kann ich einen Alumni zur regulären Mitgliedsrolle ändern?
Ja. Ändern Sie einfach die Rolle in der Benutzerverwaltung. Die Validierung spielt dann keine Rolle mehr.

### Werden Alumni-Aktivitäten protokolliert?
Ja. Alle Login-Vorgänge, Validierungen und Rollenänderungen werden im Audit-Log protokolliert.

### Kann ein Alumni 2FA aktivieren?
Ja. Alumni können wie alle anderen Benutzer 2FA in ihrem Profil aktivieren.

---

## 🔍 Wo finde ich...

### ...die Benutzerverwaltung?
Hauptmenü → **Admin** → **Benutzerverwaltung**

### ...die neuen Standorte?
- Beim Erstellen eines Artikels: **Inventar** → **Neuer Artikel** → Dropdown "Standort"
- Beim Bearbeiten: **Inventar** → Artikel auswählen → **Bearbeiten** → Dropdown "Standort"
- Beim Filtern: **Inventar** → Filterformular → Dropdown "Standort"

### ...das Audit-Log?
Hauptmenü → **Admin** → **Audit-Logs**

### ...die Einladungs-Historie?
Aktuell nicht in der UI sichtbar. Prüfen Sie die Datenbank-Tabelle `invitation_tokens` für Details.

---

## 📞 Support

Bei Fragen oder Problemen:

1. **Dokumentation konsultieren:**
   - `ALUMNI_SYSTEM.md` - Vollständige technische Dokumentation
   - `ALUMNI_WORKFLOW.md` - Visuelle Workflows und Diagramme
   - `README.md` - Allgemeine System-Dokumentation

2. **Audit-Logs prüfen:**
   - Admin → Audit-Logs
   - Hier sehen Sie alle Alumni-bezogenen Aktionen

3. **Datenbank prüfen** (für Admins):
   ```sql
   -- Alle Alumni-Benutzer anzeigen
   SELECT email, is_alumni_validated, created_at 
   FROM users 
   WHERE role = 'alumni';
   
   -- Alle offenen Einladungen
   SELECT email, role, created_at, expires_at 
   FROM invitation_tokens 
   WHERE used_at IS NULL;
   ```

---

## 🎓 Tipps für Best Practices

### ✅ DO
- Prüfen Sie die Identität des Alumni vor der Validierung
- Verwenden Sie aussagekräftige E-Mail-Adressen
- Aktivieren Sie 2FA für alle Alumni-Vorstandsmitglieder
- Überprüfen Sie regelmäßig die Audit-Logs

### ❌ DON'T
- Teilen Sie Einladungslinks nicht öffentlich
- Validieren Sie Alumni nicht automatisch ohne Prüfung
- Verwenden Sie keine Sammel-E-Mail-Adressen
- Löschen Sie nicht versehentlich aktive Alumni-Konten

---

## 📊 Statistiken im Blick behalten

### Dashboard-Übersicht
Das Dashboard zeigt:
- Gesamtzahl der Artikel
- Gesamtwert des Inventars
- Artikel mit niedrigem Bestand
- Kürzliche Aktivitäten

### Benutzerverwaltung
Hier sehen Sie:
- Anzahl der Benutzer pro Rolle
- 2FA-Status jedes Benutzers
- Alumni-Validierungsstatus
- Letzter Login-Zeitstempel

---

## 🔄 Updates und Wartung

### Nach einem Update
1. Prüfen Sie, ob neue Migrationen verfügbar sind
2. Lesen Sie die Release Notes
3. Testen Sie kritische Funktionen (Login, Einladungen, Validierung)
4. Überprüfen Sie die Audit-Logs auf Anomalien

### Regelmäßige Wartung
- **Wöchentlich:** Audit-Logs prüfen
- **Monatlich:** Abgelaufene Einladungen aufräumen
- **Vierteljährlich:** Alumni-Validierungen überprüfen

---

**Letzte Aktualisierung:** 2026-02-01  
**Version:** 1.1.0  
**Weitere Hilfe:** Siehe `ALUMNI_SYSTEM.md` für detaillierte technische Informationen
