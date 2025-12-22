<?php

namespace Diversworld\ContaoDiveclubBundle\Helper;

use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use RuntimeException;

class DcaTemplateHelper
{
    public function getManufacturers()
    {
        return $this->getTemplateOptions('manufacturersFile');
    }

    /**
     * Gibt die Optionen für eine Vorlage zurück.
     */
    public function getTemplateOptions($templateName): array
    {
        // Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName);

        // Überprüfen, ob der Pfad leer ist oder die Datei nicht existiert
        if (empty($templatePath) || !file_exists($templatePath)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateNotFound'], $templatePath));
            // Wenn nichts konfiguriert ist, geben wir ein leeres Array zurück statt abzustürzen
            return [];
        }

        $options = include $templatePath;

        if (!is_array($options)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateContent'], $options));
        }

        return $options;
    }

    private function getTemplateFromConfig($templateName): string
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $configArray = [];

        // Lade die erforderlichen Felder aus der Tabelle tl_dc_config
        $result = Database::getInstance()->execute("
            SELECT manufacturersFile, typesFile, regulatorsFile, sizesFile, courseTypesFile, courseCategoriesFile
            FROM tl_dc_config
            LIMIT 1"
        );

        if ($result->numRows > 0) {
            // Für jedes Feld die UUID verarbeiten
            $files = [
                'manufacturersFile' => $result->manufacturersFile,
                'typesFile' => $result->typesFile,
                'regulatorsFile' => $result->regulatorsFile,
                'sizesFile' => $result->sizesFile,
                'courseTypesFile' => $result->courseTypesFile,
                'courseCategoriesFile' => $result->courseCategoriesFile,
            ];

            // UUIDs in Pfade umwandeln
            foreach ($files as $key => $uuid) {
                if (!empty($uuid)) {
                    $convertedUuid = StringUtil::binToUuid($uuid);
                    $fileModel = FilesModel::findByUuid($convertedUuid);

                    if ($fileModel !== null && file_exists($rootDir . '/' . $fileModel->path)) {
                        $configArray[$key] = $rootDir . '/' . $fileModel->path;
                    } else {
                        $configArray[$key] = null; // Datei nicht gefunden oder ungültige UUID
                    }
                } else {
                    $configArray[$key] = null; // Leerer Wert in der DB
                }
            }
        } else {
            throw new RuntimeException('Keine Einträge in der Tabelle tl_dc_config gefunden.');
        }

        switch ($templateName) {
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
                return $configArray[$templateName];
        }
    }

    public function getSizes()
    {
        return $this->getTemplateOptions('sizesFile');
    }

    public function getEquipmentFlatTypes(): array
    {
        $types = $this->getTemplateOptions('typesFile');

        // Flache Struktur erstellen
        $flattenedOptions = [];
        foreach ($types as $id => $typeData) {
            if (isset($typeData['name'])) { // Sicherstellen, dass der Name-Index existiert
                $flattenedOptions[$id] = $typeData['name']; // Verwenden Sie den 'name'-Wert
            }
        }
        return $flattenedOptions; // ['1' => 'Anzüge', '2' => 'ABC-Equipment', ...]
    }

    public function getSubTypes(int $typeId): array
    {
        $types = $this->getEquipmentTypes(); // Equipment-Typen laden

        foreach ($types as $tid => $typeData) {
            // Wenn der Typ übereinstimmt, Subtypen extrahieren
            if ($tid == $typeId) {
                // Subtypen-Array extrahieren
                return $typeData['subtypes'];
            }
        }

        return []; // Keine Subtypen gefunden
    }

    public function getEquipmentTypes()
    {
        return $this->getTemplateOptions('typesFile');
    }

    public function getCourseTypes(): array
    {
        return $this->getTemplateOptions('courseTypesFile');
    }

    public function getCourseCategories(): array
    {
        return $this->getTemplateOptions('courseCategoriesFile');
    }

    public function getRegModels1st(?int $manufacturer = null, ?DataContainer $dc = null): array
    {
        // Hersteller entweder aus Parameter oder DataContainer ermitteln
        if (!$manufacturer && $dc && $dc->activeRecord && $dc->activeRecord->manufacturer) {
            $manufacturer = $dc->activeRecord->manufacturer;
        }

        if (!$manufacturer) {
            return []; // Kein Hersteller verfügbar
        }

        $models = $this->getTemplateOptions('regulatorsFile');

        if (!isset($models[$manufacturer]['regModel1st']) || !is_array($models[$manufacturer]['regModel1st'])) {
            return [];
        }
        return $models[$manufacturer]['regModel1st'];
    }

    public function getRegModels2nd(?int $manufacturer = null, ?DataContainer $dc = null): array
    {
        // Hersteller entweder aus Parameter oder DataContainer ermitteln
        if (!$manufacturer && $dc && $dc->activeRecord && $dc->activeRecord->manufacturer) {
            $manufacturer = $dc->activeRecord->manufacturer;
        }

        if (!$manufacturer) {
            return []; // Kein Hersteller verfügbar
        }

        $models = $this->getTemplateOptions('regulatorsFile');

        // Prüfen, ob der Hersteller existiert und Modelle für die zweite Stufe definiert sind
        if (!isset($models[$manufacturer]['regModel2nd']) || !is_array($models[$manufacturer]['regModel2nd'])) {
            return [];
        }

        // Rückgabe der Modelle für die zweite Stufe
        return $models[$manufacturer]['regModel2nd'];
    }
}
