<?php
namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Contao\TemplateLoader;
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
        // 1. Parent-ID laden
        $parentId = Input::get('id');

        if (!$parentId) {
            $this->logger->error('Keine Parent-ID gefunden.');
            return ['Typ: unbekannt', 'Art: unbekannt'];
        }

        // 2. Subtypen aus Template laden
        $equipmentType = $this->getTemplateOptions('equipment_types');
        $subTypes = $this->getTemplateOptions('equipment_subTypes');

        // 3. Parent-Typ aus Tabelle laden
        $record = $this->db->fetchAssociative(
            "SELECT title, subType
         FROM tl_dc_equipment_type
         WHERE id = ?",
            [$parentId]
        );

        if (!$record) {
            $this->logger->error("Kein Datensatz für Parent-ID {$parentId} gefunden.");
            return ['Typ: unbekannt', 'Art: unbekannt'];
        }

        // 4. Typ und SubTyp auflösen
        $equipmentId = (int)$record['title']; // ID des Typs
        $modelId = (int)$record['subType']; // ID des Subtyps

        $record['title'] = $equipmentType[$equipmentId];
        $record['subType'] = $this->resolveModel($subTypes, $modelId, 'subType', (int)$record['subType']);
        $this->logger->info('equipmentId: ' . $equipmentId);
        $this->logger->info('equipmentType: ' . print_r($equipmentType, true));
        $this->logger->info('Titel: '. $record['title']);
        $this->logger->info('Subtyp: '. $record['subType']);


        // 6. Sprachdatei laden und Mapping vorbereiten
        System::loadLanguageFile('tl_dc_regulators');

        // 4. Lokalisierte Werte aus `$GLOBALS['TL_LANG']`
        $title = $GLOBALS['TL_LANG']['tl_dc_equipment_type']['title'][$equipmentId] ?? 'Unbekannter Typ';
        $subType = $GLOBALS['TL_LANG']['tl_dc_equipment_type']['subType'][$equipmentId][$modelId] ?? 'Unbekanntes Modell';

/*        $mapping = [
            is_string($GLOBALS['TL_LANG']['tl_dc_equipment']['title'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_equipment']['title'] : 'Typ' => 'title',
            is_string($GLOBALS['TL_LANG']['tl_dc_equipment']['subType'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_equipment']['subType'] : 'Art' => 'subType',
        ];

        foreach ($mapping as $labelKey => $recordField) {
            $labels[$labelKey] = $record[$recordField] ?? 'Nicht verfügbar';
        }*/

        return array_values($labels); // Rückgabe als Array
    }

    /**
    * Lädt die Dropdown-Werte (Optionen) aus einem Template wie `regulator_data.html5`.
    */
    private function getTemplateOptions(string $templateName): array
    {
        $templatePath = TemplateLoader::getPath($templateName, 'html5');

        if (!$templatePath || !file_exists($templatePath)) {
            $this->logger->error('Template file not found: ' . $templatePath);
            return [];
        }

        $content = file_get_contents($templatePath);
        $this->logger->debug('Loaded template content: ' . $content);

        $options = [];
        $content = trim($content);
        $content = trim($content, '<?p=');
                $content = trim($content, '?>');

        eval('$options = ' . $content . ';');

        if (!is_array($options)) {
            $this->logger->error('Invalid template content format.');
            return [];
        }

        return $options;
    }

    private function resolveModel(array $models, int $equipmentId, string $modelType, int $modelId): string
    {
        // Prüfen, ob Hersteller und Modelltyp existieren
        if (isset($models[$equipmentId][$modelId])) {
            return $models[$equipmentId][$modelId];
        }


        return 'Unbekanntes Modell'; // Fallback, falls nicht gefunden

    }
}
