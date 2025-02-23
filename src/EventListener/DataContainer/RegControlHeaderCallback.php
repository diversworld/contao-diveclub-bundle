<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Contao\TemplateLoader;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_regulator_control', target: 'list.sorting.header')]
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
        // 1. Hole die `pid` des aktuellen Datensatzes (Parent-ID)
        $parentId = $dc->activeRecord ? $dc->activeRecord->pid : null;

        if (!$parentId) {
            $this->logger->error('No parent ID found for record in tl_dc_regulator_control.');
            return []; // Keine Parent-ID, leeres Array zurückgeben
        }

        // 2. Lade die Parent-Daten aus `tl_dc_regulators`
        $record = $this->db->fetchAssociative(
            "SELECT title, manufacturer, serialNumber1st, regModel1st, serialNumber2ndPri, regModel2ndPri, serialNumber2ndSec, regModel2ndSec
             FROM tl_dc_regulators
             WHERE id = ?",
            [$dc->pid],
        );

        $this->logger->debug('Fetched parent record: ' . print_r($record, true));

        if (!$record) {
            $this->logger->warning('No data found in tl_dc_regulators for parent ID: ' . $parentId);
            return []; // Keine Daten gefunden, leeres Array zurückgeben
        }

        // 3. Template-Daten laden und Modelle auflösen
        $templateOptions = $this->getTemplateOptions('regulator_data');
        $manufacturerId = $record['manufacturer'] ?? null;

        $this->logger->debug('Loaded template options: ' . print_r($templateOptions, true));
        $this->logger->debug('Manufacturer ID: ' . $manufacturerId);

        if ($manufacturerId && isset($templateOptions[$manufacturerId])) {
            $templateData = $templateOptions[$manufacturerId];

            // Modelle anhand der Indexwerte ersetzen
            $record['regModel1st'] = $templateData[$manufacturerId][$record['regModel1st']] ?? $record['regModel1st'];
            $record['regModel2ndPri'] = $templateData[$manufacturerId][$record['regModel2ndPri']] ?? $record['regModel2ndPri'];
            $record['regModel2ndSec'] = $templateData[$manufacturerId][$record['regModel2ndSec']] ?? $record['regModel2ndSec'];
        } else {
            $this->logger->warning('Manufacturer ID not found or no template options available.');
        }

        $this->logger->debug('Resolved parent record: ' . print_r($record, true));

        // 4. Fülle die Header-Felder
        return array_filter([
            $GLOBALS['TL_LANG']['tl_dc_regulators']['title'] => $record['title'] ?? null,
            $GLOBALS['TL_LANG']['tl_dc_regulators']['manufacturer'] => $record['manufacturer'] ?? null,
            'Seriennummer 1. Stufe' => $record['serialNumber1st'] ?? null,
            'Modell 1. Stufe' => $record['regModel1st'] ?? null,
            'Seriennummer 2. Stufe (primär)' => $record['serialNumber2ndPri'] ?? null,
            'Modell 2. Stufe (primär)' => $record['regModel2ndPri'] ?? null,
            'Seriennummer 2. Stufe (sekundär)' => $record['serialNumber2ndSec'] ?? null,
            'Modell 2. Stufe (sekundär)' => $record['regModel2ndSec'] ?? null,
        ]);
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
}
