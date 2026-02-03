# Kursverwaltung

Die Kursverwaltung ermöglicht das Anlegen von Tauchkursen, die Zuweisung von Modulen und die Dokumentation des
Lernfortschritts.

## Struktur der Kurse

Ein Kurs ist hierarchisch aufgebaut:

1. **Kursart (Course):** Grundlegende Definition (z.B. OWD, AOWD).
2. **Module:** Unterteilung des Kurses (z.B. Theorie, Pool, Freiwasser).
3. **Übungen (Exercises):** Konkrete Fertigkeiten innerhalb eines Moduls.

## Ausbildungsprozess

### 1. Kurs-Setup

Zuerst werden die Kursarten und die zugehörigen Übungen definiert. Diese dienen als Vorlage.

### 2. Schüler-Verwaltung

Schüler werden im System erfasst und einem konkreten Kurs zugeordnet. Bei der Zuordnung werden automatisch alle für
diesen Kurs definierten Übungen für den Schüler generiert.

### 3. Fortschrittsdokumentation

Tauchlehrer können im Backend den Status jeder Übung für einen Schüler setzen:

- `done` (Erfolgreich abgeschlossen)
- `repeat` (Muss wiederholt werden)
- `failed` (Nicht bestanden)

### 4. Frontend-Ansicht

Schüler können ihren eigenen Fortschritt im Frontend über das Modul **Course Progress** einsehen. Dort wird grafisch
oder in Tabellenform angezeigt, welche Übungen bereits erfolgreich absolviert wurden.

---
[[Zurück zur Startseite](Home)]
