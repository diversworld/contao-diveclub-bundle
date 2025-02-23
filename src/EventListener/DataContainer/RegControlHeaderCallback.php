<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Contao\TemplateLoader;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_control_card', target: 'list.sorting.header')]
class RegControlHeaderCallback
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
        $this->logger = System::getContainer()->get('monolog.logger.contao.general');

        // 1. Parent-ID laden
        $parentId = Input::get('id');

        if (!$parentId) {
            $this->logger->error('No parent ID found for record in tl_dc_control_card.');
            return ['leer', 'leer', 'leer'];
        }

        $this->logger->info('Labels: ' . print_r($labels, true));

        // 2. Parent-Record (tl_dc_regulators) laden
        $record = $this->db->fetchAssociative(
            "SELECT title, manufacturer, serialNumber1st, regModel1st, regModel2ndPri, regModel2ndSec
         FROM tl_dc_regulators
         WHERE id = ?",
            [$parentId]
        );

        if (!$record) {
            $this->logger->warning('No data found in tl_dc_regulators for parent ID: ' . $parentId);
            return ['leer', 'leer', 'leer'];
        }

        // 3. Templates laden
        $manufacturers = $this->getTemplateOptions('equipment_manufacturers'); // Hersteller
        $models = $this->getTemplateOptions('regulator_data'); // Regulator-Daten

        $this->logger->debug('Loaded manufacturers: ' . print_r($manufacturers, true));
        $this->logger->debug('Loaded models: ' . print_r($models, true));

        // 4. Hersteller auflösen
        $manufacturerId = (int)$record['manufacturer']; // Speichern der numerischen ID
        $record['manufacturer'] = $manufacturers[$manufacturerId] ?? 'Unbekannter Hersteller'; // Anzeigename

        // 5. Modelle auflösen
        $record['regModel1st'] = $this->resolveModel($models, $manufacturerId, 'regModel1st', (int)$record['regModel1st']);
        $record['regModel2ndPri'] = $this->resolveModel($models, $manufacturerId, 'regModel2nd', (int)$record['regModel2ndPri']);
        $record['regModel2ndSec'] = $this->resolveModel($models, $manufacturerId, 'regModel2nd', (int)$record['regModel2ndSec']);

        // 6. Sprachdatei laden und Mapping vorbereiten
        System::loadLanguageFile('tl_dc_regulators');
        $this->logger->debug('Loaded language: ' . print_r($GLOBALS['TL_LANG']['tl_dc_regulators'], true));

        $mapping = [
            is_string($GLOBALS['TL_LANG']['tl_dc_regulators']['title'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_regulators']['title'] : 'Inventarnummer' => 'title',
            is_string($GLOBALS['TL_LANG']['tl_dc_regulators']['manufacturer'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_regulators']['manufacturer'] : 'Hersteller' => 'manufacturer',
            is_string($GLOBALS['TL_LANG']['tl_dc_regulators']['regModel1st'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_regulators']['regModel1st'] : 'Modell 1. Stufe' => 'regModel1st',
            is_string($GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndPri'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndPri'] : 'Modell 2. Stufe (primär)' => 'regModel2ndPri',
            is_string($GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndSec'] ?? null)
                ? $GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndSec'] : 'Modell 2. Stufe (sekundär)' => 'regModel2ndSec',
        ];

        foreach ($mapping as $labelKey => $recordField) {
            $labels[$labelKey] = $record[$recordField] ?? 'Nicht verfügbar';
        }

        $this->logger->debug('Mapped labels: ' . print_r($labels, true));

        return $labels;
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

    private function resolveModel(array $models, int $manufacturerId, string $modelType, int $modelId): string
    {
        // Prüfen, ob Hersteller und Modelltyp existieren
        if (isset($models[$manufacturerId][$modelType][$modelId])) {
            return $models[$manufacturerId][$modelType][$modelId];
        }

        return 'Unbekanntes Modell'; // Fallback, falls nicht gefunden
    }
}
