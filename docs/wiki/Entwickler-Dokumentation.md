# Entwickler-Dokumentation

Informationen für Entwickler und Redakteure zur Anpassung und Erweiterung des Bundles.

## Insert-Tags

Das Bundle bietet spezielle Insert-Tags, um Daten aus der aktuellen Session (z.B. nach einer Buchung) anzuzeigen.

### TÜV-Buchungen (`{{dc_check::*}}`)

Bezieht sich auf Daten aus der Tabelle `tl_dc_check_order` basierend auf der Session `last_tank_check_order`.

| Tag                           | Beschreibung                    |
|:------------------------------|:--------------------------------|
| `{{dc_check::bookingNumber}}` | Die generierte Buchungsnummer.  |
| `{{dc_check::totalPrice}}`    | Gesamtpreis der Buchung.        |
| `{{dc_check::firstname}}`     | Vorname des Buchenden.          |
| `{{dc_check::bookingDate}}`   | Datum der Buchung (formatiert). |

### Kurs-Anmeldungen (`{{course::*}}`)

Bezieht sich auf Daten der Kurszuordnung basierend auf `last_course_order`.

| Tag                     | Beschreibung              |
|:------------------------|:--------------------------|
| `{{course::title}}`     | Name des Kurses / Events. |
| `{{course::firstname}}` | Vorname des Schülers.     |
| `{{course::dateStart}}` | Startdatum des Kurses.    |

## Twig Templates

Das Bundle nutzt Contao 5 Twig Templates. Diese können über Vererbung im globalen `templates/`-Ordner angepasst werden.

### Beispiel: Anpassung der Kursliste

Erstellen Sie eine Datei `templates/mod_dc_course_events_list.html.twig`:

```twig
{% extends "@Contao/frontend_module/mod_dc_course_events_list" %}

{% block event_title %}
    <h3>Kurs: {{ ev.title }}</h3>
{% endblock %}
```

### Wichtige Templates:

- `mod_dc_course_events_list.html.twig`: Liste der Kurstermine.
- `mod_dc_course_event_reader.html.twig`: Detailansicht eines Kurses.
- `mod_dc_tank_check.html.twig`: Formular für die TÜV-Anmeldung.
- `mod_dc_course_progress.html.twig`: Fortschrittsanzeige für Schüler.

---
[[Zurück zur Startseite](Home)]
