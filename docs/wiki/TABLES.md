### Übersicht der Datenbanktabellen (Diveclub Bundle)

Diese Übersicht listet die im `contao-diveclub-bundle` verwendeten Datenbanktabellen und deren Verwendungszweck auf.

#### Kern-Tabellen

| Tabelle            | Beschreibung                                                                           |
|--------------------|----------------------------------------------------------------------------------------|
| `tl_dc_tanks`      | Verwaltung von Tauchgeräten (Flaschen), inklusive TÜV-Terminen, Hersteller und Status. |
| `tl_dc_regulators` | Verwaltung von Atemreglern und deren Revisionen.                                       |
| `tl_dc_equipment`  | Allgemeine Ausrüstungsgegenstände (Jackets, Anzüge, etc.).                             |
| `tl_dc_config`     | Zentrale Konfigurationseinstellungen für das Diveclub Bundle.                          |

#### Kurssystem

| Tabelle                          | Beschreibung                                              |
|----------------------------------|-----------------------------------------------------------|
| `tl_dc_dive_course`              | Definition von Tauchkursen (Stammdaten).                  |
| `tl_dc_course_modules`           | Einzelne Module, die zu einem Kurs gehören.               |
| `tl_dc_course_exercises`         | Übungen innerhalb der Kursmodule.                         |
| `tl_dc_course_event`             | Konkrete Kursveranstaltungen (Termine).                   |
| `tl_dc_course_event_schedule`    | Zeitplan für eine Kursveranstaltung.                      |
| `tl_dc_event_schedule_exercises` | Zuordnung von Übungen zu Zeitplan-Einträgen.              |
| `tl_dc_students`                 | Verwaltung von Tauchschülern (verknüpft mit Mitgliedern). |
| `tl_dc_course_students`          | Zuordnung von Schülern zu Kursveranstaltungen.            |
| `tl_dc_student_exercises`        | Fortschritt der Schüler bei den einzelnen Übungen.        |

#### Verleih & Reservierung

| Tabelle                   | Beschreibung                                              |
|---------------------------|-----------------------------------------------------------|
| `tl_dc_reservation`       | Kopfdaten von Ausrüstungsreservierungen.                  |
| `tl_dc_reservation_items` | Einzelne Positionen einer Reservierung (konkrete Geräte). |

#### TÜV-Prüfung (Check)

| Tabelle                   | Beschreibung                                        |
|---------------------------|-----------------------------------------------------|
| `tl_dc_check_proposal`    | Sammeltermine für TÜV-Prüfungen.                    |
| `tl_dc_check_booking`     | Buchungen von Mitgliedern für einen TÜV-Termin.     |
| `tl_dc_check_order`       | Einzelne Flaschen in einer TÜV-Buchung.             |
| `tl_dc_check_articles`    | Preisliste und Rechnungsartikel für TÜV-Prüfungen.  |
| `tl_dc_regulator_control` | Kontrollkarten/Protokolle für Atemregler-Prüfungen. |

#### Erweiterungen bestehender Tabellen

| Tabelle              | Beschreibung                                            |
|----------------------|---------------------------------------------------------|
| `tl_calendar`        | Erweitert um Diveclub-spezifische Einstellungen.        |
| `tl_calendar_events` | Erweitert für die Verknüpfung mit Kursen und Prüfungen. |
| `tl_module`          | Erweitert um Konfigurationen für Frontend-Module.       |
