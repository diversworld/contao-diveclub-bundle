# Installation & Konfiguration

## Installation

Das Bundle wird über Composer installiert:

```bash
composer require diversworld/contao-diveclub-bundle
```

Nach der Installation müssen die Datenbank-Migrationen über den Contao Manager oder die Konsole ausgeführt werden:

```bash
php vendor/bin/contao-console contao:migrate
```

## Globale Konfiguration

Die zentralen Einstellungen finden Sie im Contao Backend unter **System > Konfiguration**. Dort gibt es einen Bereich
für das Diveclub Bundle.

### Einstellungen (tl_dc_config)

- **Vorlagen (Templates):** Hier weisen Sie Ihre eigenen PHP-Dateien für Hersteller, Ausrüstungstypen, Größen, Regler
  und Kurse zu. Diese Dateien dienen als Datenquelle für Dropdown-Menüs.
- **Rechnungen:** Auswahl eines PDF-Templates (Briefpapier) und Definition von Zusatztexten für generierte Rechnungen.
- **Speicherorte:** Definition der Ordner im Contao-Dateisystem für generierte PDFs (Rechnungen, TÜV-Listen).
  Standardmäßig wird `files/` verwendet.
- **TÜV-Listen Export:** Standard-Exportformat wählen (PDF, CSV oder XLSX).
- **Reservierungen:** Konfiguration von Bestätigungstexten und Benachrichtigungs-E-Mails für Ausleihvorgänge.
- **Mietbedingungen:** Hinterlegung der AGB für die Ausrüstungsmiete.

---
[[Zurück zur Startseite](Home)]
