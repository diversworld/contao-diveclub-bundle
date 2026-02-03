# Ausrüstungsverwaltung & Regler

Das Bundle ermöglicht die detaillierte Verwaltung von vereinseigener oder Schul-Ausrüstung.

## Allgemeine Ausrüstung (Equipment)

Unter dem Menüpunkt **Ausrüstung** können diverse Gegenstände wie Anzüge, ABC-Ausrüstung, Jackets etc. verwaltet werden.
Jedes Teil kann einen Status besitzen, um die Verfügbarkeit zu tracken:

- `available` (verfügbar)
- `reserved` (reserviert)
- `borrowed` (verliehen)
- `returned` (zurückgegeben)
- `overdue` (überfällig)
- `lost` / `damaged` / `missing`

## Regler-Management

Speziell für Atemregler gibt es eine detaillierte Erfassung der ersten und zweiten Stufen sowie der Revisionshistorie.

### Konfiguration der Modelldaten

Die Hersteller und Modelle werden über externe Textdateien definiert, um flexibel auf den Bestand reagieren zu können.
Beispiel für `equipment_manufacturer.txt`:

```php
<?php
return [
'1' => 'Scubapro',
'2' => 'Aqualung',
// ...
];
```

### Wartungshistorie

Zu jedem Regler-Set können die Service-Termine hinterlegt werden. Das System kann so genutzt werden, um fällige
Revisionen im Blick zu behalten.

## Reservierungssystem (Frontend)

Mitglieder können im Frontend verfügbare Ausrüstung reservieren.

1. **Frontend-Modul:** "Equipment Reservation" auf einer Seite einbinden.
2. **Prozess:** Mitglied wählt Zeitspanne und Artikel -> Status springt auf `reserved`.
3. **Ausgabe:** Der Administrator markiert bei Abholung den Status als `borrowed`.

---
[[Zurück zur Startseite](Home)]
