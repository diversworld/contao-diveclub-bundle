# Installation & Konfiguration

## Voraussetzungen

- PHP 8.3 oder neuer
- Contao 5.7 oder 6.x
- Schreibbarer Contao-Dateibereich `files/`
- Optional: `terminal42/notification_center` für Benachrichtigungen bei Änderungen von Kurszeitplänen

## Installation mit Composer

```bash
composer require diversworld/contao-diveclub-bundle
php vendor/bin/contao-console contao:migrate
```

Die Migration legt die Datenbanktabellen an und kopiert die mitgelieferten Beispieldateien nach
`files/diveclub/templates/`. Leeren Sie anschließend bei Bedarf den Contao-Cache:

```bash
php vendor/bin/contao-console cache:clear
```

Bei DDEV werden die Befehle im Web-Container ausgeführt:

```bash
ddev exec composer require diversworld/contao-diveclub-bundle
ddev exec php vendor/bin/contao-console contao:migrate
```

## Ersteinrichtung

1. Öffnen Sie im Backend den Bereich **Diveclub > Konfiguration**.
2. Legen Sie genau eine zentrale Konfiguration an und veröffentlichen Sie sie.
3. Synchronisieren Sie unter **Dateien** das Dateisystem, damit die kopierten Beispieldateien auswählbar sind.
4. Aktivieren Sie die benötigten Datenquellen und wählen Sie die passenden Dateien aus
   `files/diveclub/templates/` aus.
5. Wählen Sie Instruktoren-Gruppen und Ausbildungsleiter, wenn die Kursverwaltung verwendet wird.
6. Konfigurieren Sie Briefpapier, Speicherordner und E-Mail-Texte für TÜV und Reservierung.
7. Legen Sie die benötigten Frontend-Seiten und Frontend-Module an. Die Weiterleitungen sind unten beschrieben.

## Zentrale Konfiguration (`tl_dc_config`)

Die Einstellungen befinden sich im Backend unter **Diveclub > Konfiguration**.

- **Stammdaten-Dateien:** Hersteller, Ausrüstungstypen und Untertypen, Größen, Reglermodelle, Kurstypen und
  Kurskategorien. Die `.txt`-Dateien enthalten PHP-Code und müssen mit `return [...]` ein Array liefern.
- **Ausbildung:** Mitgliedergruppen für Instruktoren, Ausbildungsleiter und sichtbare Dashboard-Bereiche.
- **Rechnungen:** PDF-Briefpapier, Zusatztext und Speicherordner für erzeugte Rechnungen.
- **TÜV-Listen:** Ausgabeformat PDF, CSV oder XLSX sowie eigener Speicherordner.
- **Reservierungen:** Bestätigungstext, Empfängeradressen und Text der Informationsmail.
- **Mietbedingungen:** Bedingungen für die Ausleihe von Vereinsausrüstung.
- **API:** Optionales Logo, Startseitentext, Newsarchiv und Rechtstexte für die App-Anbindung.

### Mitgelieferte Stammdaten-Dateien

| Konfigurationsfeld              | Beispieldatei                    |
|:--------------------------------|:---------------------------------|
| Hersteller                      | `dc_equipment_manufacturers.txt` |
| Ausrüstungstypen und Untertypen | `dc_equipment_types.txt`         |
| Größen                          | `dc_equipment_sizes.txt`         |
| Atemregler                      | `dc_regulator_data.txt`          |
| Kurstypen                       | `dc_course_types.txt`            |
| Kurskategorien                  | `dc_course_categories.txt`       |

Die Dateien dürfen kopiert und angepasst werden. Bereits vorhandene Dateien im Zielordner werden bei späteren
Migrationen nicht überschrieben.

## Frontend-Seiten und Weiterleitungen

Die Weiterleitungsfelder beziehen sich immer auf eine **Seite**, auf der das genannte Frontend-Modul eingebunden ist.

| Ausgangsmodul                                       | Feld                                | Benötigte Zielseite                                                                            |
|:----------------------------------------------------|:------------------------------------|:-----------------------------------------------------------------------------------------------|
| Kursveranstaltungen-Liste (`dc_course_events_list`) | Detailseite für Kursveranstaltungen | Seite mit `dc_course_event_reader`; öffnet die Details einer Kursveranstaltung.                |
| Kursveranstaltungen-Liste (`dc_course_events_list`) | Buchungsseite für TÜV-Prüfungen     | Seite mit `dc_tank_check`; öffnet den gewählten TÜV-Termin und das Buchungsformular.           |
| Kursübersicht Schüler (`dc_student_courses`)        | Kursfortschrittsseite               | Seite mit `dc_course_progress`; erhält die Kurszuweisung als `assignment`-Parameter.           |
| Kursveranstaltung-Reader (`dc_course_event_reader`) | Bestätigungsseite der Kursanmeldung | Seite nach erfolgreicher Anmeldung; dort können `{{course::*}}`-Insert-Tags ausgegeben werden. |
| Flaschenprüfung (`dc_tank_check`)                   | Bestätigungsseite der TÜV-Buchung   | Seite mit `dc_check_confirmation`; liest die zuletzt gespeicherte Buchung aus der Session.     |

Wenn bei einem Reader oder Buchungsmodul keine Bestätigungsseite gewählt ist, bleibt der Benutzer nach dem Absenden auf
der aktuellen Seite und erhält dort die Erfolgsmeldung.

## Benachrichtigungen für Kurszeitpläne

Installieren Sie bei Bedarf das Notification Center und legen Sie eine Benachrichtigung vom Typ **Kurszeitplanänderung**
(`dc_course_schedule_update`) an. Der Versand wird am Datensatz der Kursveranstaltung ausgelöst und informiert die
zugeordneten Schüler über geänderte sowie aktuelle Zeitplaneinträge.

## Abschlussprüfung

- Die zentrale Konfiguration ist veröffentlicht.
- Alle aktivierten Stammdaten-Dateien sind synchronisiert und in der Dateiverwaltung auswählbar.
- Die verwendeten Kursveranstaltungen, TÜV-Termine und Stammdatensätze sind veröffentlicht.
- Jede konfigurierte Zielseite enthält das in der Weiterleitungsmatrix genannte Frontend-Modul.
- Geschützte Module sind nur für die gewünschten Mitgliedergruppen erreichbar.

---
[[Zurück zur Startseite](Home)]
