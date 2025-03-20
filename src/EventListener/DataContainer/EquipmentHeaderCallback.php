<?php
namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_equipment', target: 'list.sorting.header')]
class EquipmentHeaderCallback
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function __invoke(array $labels, DataContainer $dc): array
    {
        $this->logger->info('Equipment Header Labels: ' . print_r($labels, true));
        // 1. Parent-ID laden
        $parentId = Input::get('id');

        if (!$parentId) {
            $this->logger->error('Keine Parent-ID gefunden.');
            return ['Typ: unbekannt', 'Art: unbekannt'];
        }

        // 2. Subtypen aus Template laden
        $equipmentType  = $this->getTemplateOptions('typesFile');
        $subTypes       = $this->getTemplateOptions('subTypesFile');

        // 3. Parent-Typ aus Tabelle laden
        $record = $this->db->fetchAssociative(
            "SELECT types, subType
         FROM tl_dc_equipment_types
         WHERE id = ?",
            [$parentId]
        );

        if (!$record) {
            $this->logger->error("Kein Datensatz für Parent-ID {$parentId} gefunden.");
            return ['Typ: unbekannt', 'Art: unbekannt'];
        }

        // 4. Typ und SubTyp auflösen
        $equipmentId = (int)$record['types']; // ID des Typs
        $modelId = (int)$record['subType']; // ID des Subtyps

        $record['title'] = $equipmentType[$equipmentId];
        //$record['subType'] = $this->resolveModel($subTypes, $modelId, 'subType', (int)$record['subType']);
        $record['subType'] = $this->resolveSubType($subTypes, $equipmentId, $modelId);

        $this->logger->info('Typ: '. $record['types']);
        $this->logger->info('Subtyp: '. $record['subType']);

        // 6. Sprachdatei laden und Mapping vorbereiten
        System::loadLanguageFile('tl_dc_regulators');

        $mapping = [
            'Typ' => 'title',
            'Art' => 'subType',
        ];

        foreach ($mapping as $labelKey => $recordField) {
            $labels[$GLOBALS['TL_LANG']['tl_dc_equipment'][$recordField] ?? $labelKey] = $record[$recordField] ?? 'Nicht verfügbar';
        }

        return $labels; // Rückgabe als Array
    }

    /**
    * Lädt die Dropdown-Werte (Optionen) aus einem Template wie `dc_regulator_data.txt`.
    */
    private function getTemplateOptions($templateName)
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

    function getTemplateFromConfig($templateName): string
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

    private function resolveSubType(array $subTypes, int $equipmentId, int $modelId): string
    {
        if (isset($subTypes[$equipmentId][$modelId]) )
        {
            return $subTypes[$equipmentId][$modelId];
        }
        return 'Unbekanntes Modell'; // Fallback, falls nicht gefunden
    }
}
