# Twig-Templates und Anpassungsmöglichkeiten

Das `ContaoDiveclubBundle` nutzt moderne Contao 5 Twig-Templates. Die Darstellung der Frontend-Module kann flexibel über Twig-Template-Vererbung angepasst werden.

## Allgemeine Anpassung via Vererbung

Um ein Template anzupassen, erstellen Sie eine neue Datei im Contao-Verzeichnis `templates/` (z. B. `mod_dc_course_events_list_custom.html.twig`) und erben Sie vom Original-Template. Dank der flachen Block-Struktur können Sie gezielt einzelne Bereiche überschreiben.

**Beispiel:**
```twig
{% extends "@Contao/frontend_module/mod_dc_course_events_list" %}

{% block event_title %}
    <h3>Exklusiv: {{ ev.title }}</h3>
{% endblock %}
```

---

## Übersicht der Templates

### Kurs-Event-Liste (`dc_course_events_list`)
Zeigt eine Übersicht aller veröffentlichten Kurs-Events an.

**Template:** `mod_dc_course_events_list.html.twig`
```twig
{% block content %}
    {% set container_attributes = attrs().addClass('dc-events-list').mergeWith(container_attributes|default) %}
    <div{{ container_attributes }}>
        {% if not hasEvents %}
            {{ block('no_events') }}
        {% else %}
            {{ block('events_warning') }}
            {{ block('events_list') }}
        {% endif %}
    </div>
{% endblock %}
```
**Verfügbare Variablen:**
- `events`: Array der anzuzeigenden Events. Jedes Element enthält:
    - `id`: Interne ID.
    - `title`: Titel des Events.
    - `dateStart`: Formatiertes Startdatum.
    - `dateEnd`: Formatiertes Enddatum.
    - `instructor`: Name des Kursleiters.
    - `description`: Beschreibung (HTML).
    - `location`: Veranstaltungsort.
    - `maxParticipants`: Maximale Teilnehmerzahl.
    - `price`: Kursgebühr.
    - `url`: Link zur Detailseite.
    - `isTankCheck`: (Optional) `true`, wenn es sich um einen Flaschen-TÜV Termin handelt.
- `hasEvents`: Boolean, ob Events vorhanden sind.
- `hasJumpTo`: Boolean, ob eine Reader-Seite konfiguriert wurde.

**Wichtige Blöcke:**
- `events_list`: Container für die Liste.
- `event_item`: Ein einzelner Listeneintrag.
- `event_title`: Der Titel des Events.
- `event_details`: Detailinformationen (Datum, Kursleiter, Ort).
- `event_description`: Kurzbeschreibung.
- `event_link`: Link zur Detailseite.

---

### Kurs-Event-Reader (`dc_course_event_reader`)
Zeigt die Details eines einzelnen Kurs-Events inklusive Zeitplan und Anmeldeformular.

**Template:** `mod_dc_course_event_reader.html.twig`

```twig
{% block content %}
    {% set container_attributes = attrs().addClass('dc-event-reader').mergeWith(container_attributes|default) %}
    <div{{ container_attributes }}>
        {% if notFound|default(false) %}
            {{ block('not_found') }}
        {% else %}
            {{ block('event_title') }}
            {{ block('event_details') }}
            {{ block('event_description') }}
            {{ block('student_progress') }}
            {{ block('event_schedule') }}
            {{ block('event_signup') }}
        {% endif %}
    </div>
{% endblock %}
```
**Verfügbare Variablen:**
- `event`: Objekt/Array mit den Details des Events:
    - `title`, `description`, `dateStart`, `dateEnd`, `price`, `instructor`.
- `schedule`: Array mit den Terminen des Zeitplans:
    - `planned_at`: Formatiertes Datum/Uhrzeit.
    - `location`, `instructor`, `notes`, `module`, `exercise`.
- `hasSchedule`: Boolean, ob ein Zeitplan existiert.
- `isLoggedIn`: Boolean, ob der Nutzer angemeldet ist.
- `alreadyRegistered`: Boolean, ob der Nutzer bereits angemeldet ist.
- `signup`: Array mit Labels für das Anmeldeformular.
- `request_token`: CSRF-Token für das Formular.

**Wichtige Blöcke:**
- `event_title`: Titel des Events.
- `event_details`: Basisinfos (Start, Ende, Preis, Kursleiter).
- `event_description`: Ausführliche Beschreibung.
- `event_schedule`: Der gesamte Zeitplanbereich.
- `schedule_table`: Die Tabelle mit den Terminen.
- `event_signup`: Der Anmeldebereich.
- `signup_guest`: Das Formular für Gäste.
- `signup_member`: Das Formular für angemeldete Mitglieder.

---

### Kurs-Zeitplan / Kalender (`dc_course_event_calendar`)
Stellt die Termine eines Events in einer Kalenderansicht dar.

**Template:** `mod_dc_course_event_calendar.html.twig`
```twig
{% block content %}
    {% set container_attributes = attrs().addClass('dc-course-calendar').mergeWith(container_attributes|default) %}
    <div{{ container_attributes }}>
        {% if be_message is defined %}
            {{ block('be_info') }}
        {% elseif not hasEvents %}
            {{ block('no_events') }}
        {% else %}
            {{ block('calendar_table') }}
            {{ block('calendar_script') }}
        {% endif %}
    </div>
{% endblock %}
```
**Verfügbare Variablen:**
- `weeks`: Array der Wochen, die wiederum Arrays der Tage enthalten:
    - `label`: Tag des Monats.
    - `events`: Array der Termine an diesem Tag (`title`, `time`, `location`, `instructor`, `notes`).
    - `class`: CSS-Klasse für den Tag (`today`, `weekend`, `empty`).
- `days`: Array der Wochentagsnamen.
- `currentMonth`: Name des aktuell angezeigten Monats und Jahres.
- `prevHref` / `nextHref`: Links zur Navigation.
- `hasEvents`: Boolean, ob Termine vorhanden sind.

**Wichtige Blöcke:**
- `calendar_table`: Der Tabellencontainer.
- `calendar_nav`: Vor/Zurück Navigation.
- `calendar_day_labels`: Wochentagsnamen.
- `calendar_event`: Ein einzelner Termin im Kalender.
- `calendar_event_details`: Detail-Popup bei Klick auf einen Termin.

---

### Flaschen-Check / TÜV (`dc_tank_check`)
Ermöglicht Mitgliedern und Gästen die Anmeldung von Tauchflaschen für eine TÜV-Prüfung.

**Template:** `mod_dc_tank_check.html.twig`
```twig
{% block content %}
    {% if success %}
        {{ block('success_view') }}
    {% elseif isBooking %}
        {{ block('booking_view') }}
    {% else %}
        {{ block('proposal_list') }}
    {% endif %}
{% endblock %}
```
**Verfügbare Variablen:**
- `proposals`: Liste verfügbarer TÜV-Termine (für die Listenansicht).
- `isBooking`: Boolean, ob gerade eine Buchung durchgeführt wird.
- `proposal`: Das aktuell gewählte TÜV-Angebot.
- `sessionTanks`: Array der bereits im "Warenkorb" befindlichen Flaschen.
- `articles`: Liste zusätzlich buchbarer Leistungen (O2-Service etc.).
- `tankSizes`: Array verfügbarer Flaschengrößen.
- `labels`: Übersetzungen für das Formular.
- `success`: Boolean, ob die Buchung erfolgreich abgeschlossen wurde.

**Wichtige Blöcke:**
- `proposal_list`: Liste der verfügbaren TÜV-Termine.
- `booking_view`: Die Ansicht während des Buchungsvorgangs.
- `form_add_tank`: Formular zum Hinzufügen einer Flasche.
- `reserved_tanks`: Übersicht der bereits vorgemerkten Flaschen.
- `final_booking`: Abschluss der Buchung (Adressdaten).
- `success_view`: Bestätigungsseite nach erfolgreicher Buchung.

---

### Kurs-Fortschritt (`dc_course_progress`)
Zeigt den aktuellen Ausbildungsstand eines Tauchschülers an.

**Template:** `mod_dc_course_progress.html.twig`
```twig
{% block content %}
    {% set container_attributes = attrs().addClass('mod_dc_course_progress').mergeWith(container_attributes|default) %}
    <div{{ container_attributes }}>
        {% if not isLoggedIn %}
            {{ block('not_logged_in') }}
        {% elseif notFound %}
            {{ block('not_found') }}
        {% else %}
            {{ block('progress_header') }}
            {{ block('progress_exercises') }}
            {{ block('progress_schedule') }}
            {{ block('progress_actions') }}
        {% endif %}
    </div>
{% endblock %}
```
**Verfügbare Variablen:**
- `assignment`: Infos zur Kurszuordnung (`id`, `status`, `course_title`).
- `exercises`: Liste der Übungen:
    - `title`, `module`, `status`, `status_label`, `instructor`, `dateCompleted`.
- `schedule`: Zeitplan des zugehörigen Events.
- `labels`: Übersetzungen für die Ansicht.

**Wichtige Blöcke:**
- `course_info`: Infos zum gewählten Kurs.
- `progress_summary`: Zusammenfassung der erledigten Übungen.
- `modules_list`: Liste der Kursmodule.
- `exercise_row`: Eine einzelne Übung mit Status.

---

### Weitere Templates

#### Buchungsübersicht (`dc_booking`)
**Template:** `mod_dc_booking.html.twig`
- `items`: Liste der Buchungen.
```twig
{% block content %}
  {% set assets = assets ?? [] %} {# Standardwert setzen, falls assets nicht existiert #}
  {{ block('booking_headline') }}
  {{ block('booking_intro') }}
  {{ block('booking_selection') }}
  {{ block('booking_actions_summary') }}
  {{ block('booking_items_selection') }}
 {% endblock %}'
```

#### Ausrüstungs-Auflistung (`dc_equipment_listing`)
**Template:** `mod_dc_equipment_listing.html.twig`
- `data`: Array mit Ausrüstungstypen (`id`, `title`, `type`).

#### Vereinsflaschen (`dc_tanks_listing`)
**Template:** `mod_dc_tanks_listing.html.twig`
- `tanks`: Array mit Flaschendaten (Seriennummer, Größe, TÜV-Datum etc.).

#### Kursübersicht Schüler (`dc_student_courses`)
**Template:** `mod_dc_student_courses.html.twig`
- `courses`: Liste der Kurse eines Schülers.

#### Allgemeine Auflistung (`dc_listing`)
**Template:** `mod_dc_listing.html.twig`
- `event`, `proposal`, `articles`: Verknüpfte Daten eines Events.

---

## Best Practices für Anpassungen

1. **Nicht das Original ändern:** Ändern Sie niemals Dateien direkt im `vendor/`-Verzeichnis oder im Bundle-Ordner selbst (außer Sie entwickeln das Bundle). Nutzen Sie immer das Contao `templates/`-Verzeichnis.
2. **Dateinamen:** Wenn Sie ein Template für alle Instanzen eines Moduls ändern wollen, nutzen Sie den gleichen Namen (z. B. `mod_dc_course_events_list.html.twig`). Wenn Sie eine Variante erstellen wollen, hängen Sie ein Suffix an (z. B. `_custom`) und wählen Sie dieses im Contao-Backend beim Modul aus.
3. **Debugging:** Nutzen Sie `{{ dump() }}`, um alle verfügbaren Variablen in einem Template einzusehen (erfordert den Debug-Modus von Contao).
4. **Mehrsprachigkeit:** Verwenden Sie nach Möglichkeit die bereitgestellten `labels`-Variablen oder den `trans`-Filter für eigene Texte, um die Mehrsprachigkeit zu erhalten.
