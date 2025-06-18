<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_control_card', target: 'list.sorting.header')]
class RegControlHeaderCallback
{
    private Connection $db;
    private LoggerInterface $logger;
    private DcaTemplateHelper $templateHelper;

    public function __construct(Connection $db, LoggerInterface $logger, DcaTemplateHelper $templateHelper)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(array $labels, DataContainer $dc): array
    {
        // 1. Parent-ID laden
        $parentId = Input::get('id');

        if (!$parentId) {
            $this->logger->error('No parent ID found for record in tl_dc_control_card.');
            return ['leer', 'leer', 'leer'];
        }

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
        $manufacturers = $this->templateHelper->getManufacturers();

        // 4. Hersteller auflösen
        $manufacturerId = (int)$record['manufacturer']; // Speichern der numerischen ID
        $record['manufacturer'] = $manufacturers[$manufacturerId] ?? 'Unbekannter Hersteller'; // Anzeigename
        $models1st = $this->templateHelper->getRegModels1st($manufacturerId, $dc);
        $models2nd = $this->templateHelper->getRegModels2nd($manufacturerId, $dc);

        // 5. Modelle auflösen
        $record['regModel1st'] = $models1st[(int)$record['regModel1st']] ?? '';
        $record['regModel2ndPri'] = $models2nd[(int)$record['regModel2ndPri']] ?? '';
        $record['regModel2ndSec'] = $models2nd[(int)$record['regModel2ndSec']] ?? '';

        // 6. Sprachdatei laden und Mapping vorbereiten
        System::loadLanguageFile('tl_dc_regulators');

        $mapping = [
            'Inventarnummer' => 'title',
            'Hersteller' => 'manufacturer',
            'Modell 1. Stufe' => 'regModel1st',
            'Modell 2. Stufe (primär)' => 'regModel2ndPri',
            'Modell 2. Stufe (sekundär)' => 'regModel2ndSec',
        ];

        foreach ($mapping as $labelKey => $recordField) {
            $labels[$GLOBALS['TL_LANG']['tl_dc_regulators'][$recordField][0] ?? $labelKey] = $record[$recordField] ?? 'Nicht verfügbar';
        }

        return $labels;
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
