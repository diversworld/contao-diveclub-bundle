# Equipment-Verleih

Das ContaoDiveclubBundle bietet ein integriertes System zur Verwaltung des Verleihs von Tauchausrüstung. Dies umfasst
die Reservierung im Frontend durch Mitglieder sowie die Abwicklung (Abholung/Rückgabe) im Backend durch Administratoren.

## Funktionen im Überblick

- **Verfügbarkeitsprüfung:** Mitglieder sehen im Frontend, welche Ausrüstungsgegenstände im gewünschten Zeitraum
  verfügbar sind.
- **Transparenter Status:** Jeder Verleihvorgang durchläuft definierte Status, die jederzeit nachvollziehbar sind.
- **Gebührenverwaltung:** Hinterlegung von Leihgebühren für verschiedene Gegenstände.
- **Historie:** Alle Verleihvorgänge werden dokumentiert und sind einem Mitglied zugeordnet.

## Der Verleih-Prozess

Der Ablauf eines Verleihs gliedert sich typischerweise in folgende Schritte:

### 1. Reservierung (Frontend)

Mitglieder können über das Frontend-Modul "Equipment Reservation" Ausrüstung für einen bestimmten Zeitraum anfragen.

- Auswahl des Zeitraums (Start- und Enddatum).
- Auswahl der gewünschten Gegenstände (Flaschen, Regler, Jackets etc.).
- Nach Abschluss der Reservierung wird der Status auf `reserved` gesetzt.

### 2. Abholung (Backend)

Sobald das Mitglied die Ausrüstung abholt, dokumentiert der Administrator dies im Backend.

- Aufrufen der Reservierung unter **Verleih & Reservierung**.
- Erfassen des Abholdatums (`picked_up_at`).
- Der Status der Gegenstände ändert sich auf `borrowed` (ausgeliehen).

### 3. Rückgabe (Backend)

Bei Rückgabe der Ausrüstung wird der Vorgang abgeschlossen.

- Erfassen des Rückgabedatums (`returned_at`).
- Prüfung auf Defekte oder Vollständigkeit.
- Der Status wird auf `returned` gesetzt, wodurch die Gegenstände für neue Reservierungen wieder als `available`
  markiert werden.

## Status-Definitionen

Innerhalb des Verleihsystems können Gegenstände und Reservierungen folgende Status annehmen:

| Status             | Beschreibung                                                                    |
|--------------------|---------------------------------------------------------------------------------|
| `available`        | Der Gegenstand ist verfügbar und kann reserviert werden.                        |
| `reserved`         | Der Gegenstand ist für einen Zeitraum reserviert, aber noch nicht abgeholt.     |
| `borrowed`         | Der Gegenstand ist aktuell beim Mitglied (ausgeliehen).                         |
| `returned`         | Der Gegenstand wurde zurückgegeben.                                             |
| `overdue`          | Die Rückgabefrist wurde überschritten.                                          |
| `damaged` / `lost` | Der Gegenstand ist defekt oder verloren gegangen und steht nicht zur Verfügung. |
| `cancelled`        | Die Reservierung wurde storniert.                                               |

## Verwaltung im Backend

Die Verwaltung erfolgt über zwei Hauptebenen:

1. **Reservierungen (`tl_dc_reservation`):** Hier werden die Kopfdaten (Mitglied, Zeitraum, Gesamtstatus, Gebühren)
   verwaltet.
2. **Reservierungspositionen (`tl_dc_reservation_items`):** Hier werden die einzelnen Gegenstände aufgelistet, die zu
   einer Reservierung gehören.

---
[[Zurück zur Startseite](Home)]
