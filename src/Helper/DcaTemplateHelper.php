<?php

namespace Diversworld\ContaoDiveclubBundle\Helper;

use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use RuntimeException;

class DcaTemplateHelper // Hilfsklasse zum Laden von Template-Daten für DCA-Dropdowns
{
    public function getManufacturers() // Holt Hersteller-Optionen
    {
        return $this->getTemplateOptions('manufacturersFile'); // Ruft Optionen für Hersteller-Template ab
    }

    /**
     * Gibt die Optionen für eine Vorlage zurück.
     */
    public function getTemplateOptions($templateName): array // Lädt Optionen aus einer PHP-Template-Datei
    {
        // Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName); // Ermittle den Pfad zur konfigurierten Datei

        // Überprüfen, ob der Pfad leer ist oder die Datei nicht existiert
        if (empty($templatePath) || !file_exists($templatePath)) { // Falls kein Pfad gefunden oder Datei fehlt
            // Im Backend eine Fehlermeldung anzeigen, wenn nichts konfiguriert ist
            if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest())) { // Wenn im Backend-Kontext
                $message = $GLOBALS['TL_LANG']['ERR']['noConfigFound'] ?? 'Es wurde keine Konfiguration gefunden. Bitte erstellen Sie zuerst eine Konfiguration in den Einstellungen.'; // Fehlermeldung holen
                \Contao\Message::addError($message); // Fehlermeldung in Contao ausgeben
            }

            // Wenn nichts konfiguriert ist, geben wir ein leeres Array zurück statt abzustürzen
            return []; // Gib leeres Array zurück
        }

        $options = include $templatePath; // Binde die PHP-Datei ein und erhalte das Array

        if (!is_array($options)) { // Falls die Datei kein Array zurückgibt
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateContent'], $options)); // Wirf eine Exception
        }

        return $options; // Gib das geladene Array zurück
    }

    private function getTemplateFromConfig($templateName): ?string // Ermittelt den absoluten Dateipfad aus der Konfiguration
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir'); // Projekt-Wurzelverzeichnis
        $configArray = []; // Array für die Pfade

        // Lade die erforderlichen Felder aus der Tabelle tl_dc_config
        try {
            $result = Database::getInstance()->execute("
                SELECT manufacturersFile, typesFile, regulatorsFile, sizesFile, courseTypesFile, courseCategoriesFile
                FROM tl_dc_config
                LIMIT 1"
            ); // Führe SQL-Abfrage auf die Konfigurationstabelle aus
        } catch (\Exception $e) { // Falls Tabelle nicht existiert
            return null; // Gib null zurück
        }

        if ($result->numRows > 0) { // Wenn ein Konfigurationsdatensatz existiert
            // Für jedes Feld die UUID verarbeiten
            $files = [
                'manufacturersFile' => $result->manufacturersFile,
                'typesFile' => $result->typesFile,
                'regulatorsFile' => $result->regulatorsFile,
                'sizesFile' => $result->sizesFile,
                'courseTypesFile' => $result->courseTypesFile,
                'courseCategoriesFile' => $result->courseCategoriesFile,
            ]; // Sammle die binären UUIDs aus der DB

            // UUIDs in Pfade umwandeln
            foreach ($files as $key => $uuid) { // Iteriere über die Felder
                if (!empty($uuid)) { // Wenn eine UUID hinterlegt ist
                    $convertedUuid = StringUtil::binToUuid($uuid); // Konvertiere Binär zu String-UUID
                    $fileModel = FilesModel::findByUuid($convertedUuid); // Hole das Contao FilesModel

                    if ($fileModel !== null && file_exists($rootDir . '/' . $fileModel->path)) { // Wenn Datei im Filesystem existiert
                        $configArray[$key] = $rootDir . '/' . $fileModel->path; // Speichere absoluten Pfad
                    } else {
                        $configArray[$key] = null; // Datei nicht gefunden oder ungültige UUID
                    }
                } else {
                    $configArray[$key] = null; // Leerer Wert in der DB
                }
            }
        } else {
            // Im Backend eine Fehlermeldung anzeigen, wenn keine Einträge in der Tabelle vorhanden sind
            if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest())) { // Backend-Check
                $message = $GLOBALS['TL_LANG']['ERR']['noConfigFound'] ?? 'Es wurde keine Konfiguration gefunden. Bitte erstellen Sie zuerst eine Konfiguration in den Einstellungen.'; // Sprach-String
                \Contao\Message::addError($message); // Fehlermeldung setzen
            }
            return null; // Gib null zurück
        }

        switch ($templateName) { // Fallunterscheidung basierend auf dem angefragten Template
            case 'dc_regulator_data':
                return $configArray['regulatorsFile'];
            case 'dc_equipment_types':
                return $configArray['typesFile'];
            case 'dc_equipment_sizes':
                return $configArray['sizesFile'];
            case 'dc_equipment_manufacturers':
                return $configArray['manufacturersFile'];
            case 'dc_course_types':
                return $configArray['courseTypesFile'];
            case 'dc_course_categories':
                return $configArray['courseCategoriesFile'];
            default:
                return $configArray[$templateName]; // Rückgabe über den Feldnamen
        }
    }

    public function getSizes() // Holt Größen-Optionen
    {
        return $this->getTemplateOptions('sizesFile'); // Lädt Größen aus Template
    }

    public function getEquipmentFlatTypes(): array // Holt flache Liste der Equipment-Typen
    {
        $types = $this->getTemplateOptions('typesFile'); // Lade Typen-Daten aus Template

        // Flache Struktur erstellen
        $flattenedOptions = []; // Ziel-Array initialisieren
        foreach ($types as $id => $typeData) { // Iteriere über geladene Typen
            if (isset($typeData['name'])) { // Sicherstellen, dass der Name-Index existiert
                $flattenedOptions[$id] = $typeData['name']; // Verwende den Namen als Label
            }
        }
        return $flattenedOptions; // Gib flache Liste zurück (z.B. ['1' => 'Anzüge'])
    }

    public function getSubTypes(int $typeId): array // Holt Untertypen für einen bestimmten Equipment-Typ
    {
        $types = $this->getEquipmentTypes(); // Equipment-Typen laden

        foreach ($types as $tid => $typeData) { // Iteriere über alle Typen
            // Wenn der Typ übereinstimmt, Subtypen extrahieren
            if ($tid == $typeId) { // Falls ID übereinstimmt
                // Subtypen-Array extrahieren
                return $typeData['subtypes']; // Gib Untertypen-Array zurück
            }
        }

        return []; // Keine Subtypen gefunden
    }

    public function getEquipmentTypes() // Holt alle Equipment-Typen
    {
        return $this->getTemplateOptions('typesFile'); // Lädt aus Template-Datei
    }

    public function getCourseTypes(): array // Holt Kurs-Typen
    {
        return $this->getTemplateOptions('courseTypesFile'); // Lädt aus Template-Datei
    }

    public function getCourseCategories(): array // Holt Kurs-Kategorien
    {
        return $this->getTemplateOptions('courseCategoriesFile'); // Lädt aus Template-Datei
    }

    public function getRegModels1st(?int $manufacturer = null, ?DataContainer $dc = null): array // Holt Atemregler-Modelle (1. Stufe)
    {
        // Hersteller entweder aus Parameter oder DataContainer ermitteln
        if (!$manufacturer && $dc && $dc->activeRecord && $dc->activeRecord->manufacturer) { // Falls kein Hersteller übergeben, versuche aus Datensatz zu lesen
            $manufacturer = $dc->activeRecord->manufacturer; // Setze Hersteller-ID
        }

        if (!$manufacturer) { // Falls immer noch kein Hersteller bekannt
            return []; // Kein Hersteller verfügbar
        }

        $models = $this->getTemplateOptions('regulatorsFile'); // Lade Regler-Daten

        if (!isset($models[$manufacturer]['regModel1st']) || !is_array($models[$manufacturer]['regModel1st'])) { // Wenn keine 1. Stufen für diesen Hersteller
            return []; // Gib leer zurück
        }
        return $models[$manufacturer]['regModel1st']; // Gib Modelle zurück
    }

    public function getRegModels2nd(?int $manufacturer = null, ?DataContainer $dc = null): array // Holt Atemregler-Modelle (2. Stufe)
    {
        // Hersteller entweder aus Parameter oder DataContainer ermitteln
        if (!$manufacturer && $dc && $dc->activeRecord && $dc->activeRecord->manufacturer) { // Versuche Hersteller aus DCA zu ermitteln
            $manufacturer = $dc->activeRecord->manufacturer; // Hersteller-ID setzen
        }

        if (!$manufacturer) { // Falls kein Hersteller vorhanden
            return []; // Kein Hersteller verfügbar
        }

        $models = $this->getTemplateOptions('regulatorsFile'); // Lade Regler-Daten

        // Prüfen, ob der Hersteller existiert und Modelle für die zweite Stufe definiert sind
        if (!isset($models[$manufacturer]['regModel2nd']) || !is_array($models[$manufacturer]['regModel2nd'])) { // Falls keine 2. Stufen vorhanden
            return []; // Gib leer zurück
        }

        // Rückgabe der Modelle für die zweite Stufe
        return $models[$manufacturer]['regModel2nd']; // Gib Modelle zurück
    }
}
