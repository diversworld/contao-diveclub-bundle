# TÜV-Management & Flaschen

Dieses Modul unterstützt die Verwaltung von Tauchflaschen und die Organisation von TÜV-Prüfungsterminen.

## Flaschenverwaltung (Tanks)

Im Bereich **Tauchflaschen** können alle Flaschen des Vereins mit Seriennummer, Größe und letztem Prüfdatum erfasst
werden.

- **TÜV-Überwachung:** Das System zeigt an, welche Flaschen zur Prüfung fällig sind.
- **Listenansicht:** Über ein Frontend-Modul können die Flaschendaten für Mitglieder (z.B. zur Ausleihe) angezeigt
  werden.

## TÜV-Prüfungstermine (Sammelprüfung)

Der Verein kann "Angebote" oder "Termine" für TÜV-Sammelprüfungen erstellen.

### Ablauf einer Sammelprüfung:

1. **Angebot erstellen:** Im Backend unter **TÜV-Angebote** einen Termin und die Preise für Flaschen-TÜV, O2-Service
   etc. anlegen.
2. **Kalender-Verknüpfung:** Das Angebot mit einem Event im Contao-Kalender verknüpfen.
3. **Frontend-Anmeldung:** Mitglieder können über das Modul "Tank Check" ihre eigenen Flaschen für diesen Termin
   anmelden.
4. **Export:** Der Administrator kann eine Liste aller angemeldeten Flaschen exportieren (PDF/CSV/XLSX), um sie dem
   Prüfunternehmen zu übergeben.
5. **Abrechnung:** Nach der Prüfung können im Backend Rechnungen als PDF generiert werden.

---
[[Zurück zur Startseite](Home)]
