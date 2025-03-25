<?php
namespace Diversworld\ContaoDiveclubBundle\Helper;

use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Exception;

class DcaTemplateHelper
{
    public function getManufacturers()
    {
        return $this->getTemplateOptions('manufacturersFile');
    }

    public function getSizes()
    {
        return $this->getTemplateOptions('sizesFile');
    }
    public function getEquipmentTypes()
    {
        return $this->getTemplateOptions('typesFile');
    }
    public function getSubTypes(?int $type = null, ?DataContainer $dc = null): array
    {
        // Typ entweder aus Parameter ($type) oder DataContainer ($dc) ermitteln
        if (!$type && $dc && $dc->activeRecord->title) {
            $type = $dc->activeRecord->types; // Typ aus DataContainer
        }

        // Wenn kein Typ verfügbar ist, leere Rückgabe
        if (!$type) {
            return [];
        }

        // Optionen laden
        $types = $this->getTemplateOptions('subTypesFile');

        // Rückgabe der Subtypen für den ermittelten Typ
        return $types[$type] ?? [];
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

    /**
     * Gibt die Optionen für eine Vorlage zurück.
     */
    public function getTemplateOptions($templateName)
    {
        // Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName);

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateNotFound'], $templatePath));
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
            SELECT manufacturersFile, typesFile, subTypesFile, regulatorsFile, sizesFile
            FROM tl_dc_config
            LIMIT 1"
        );

        if ($result->numRows > 0) {
            // Für jedes Feld die UUID verarbeiten
            $files = [
                'manufacturersFile' => $result->manufacturersFile,
                'typesFile' => $result->typesFile,
                'subTypesFile' => $result->subTypesFile,
                'regulatorsFile' => $result->regulatorsFile,
                'sizesFile'     => $result->sizesFile,
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
            throw new \RuntimeException('Keine Einträge in der Tabelle tl_dc_config gefunden.');
        }

        return $configArray[$templateName];
    }
}
