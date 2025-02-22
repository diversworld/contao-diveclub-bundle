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
        // 1. Lade die Felder aus der Datenbank-Tabelle `tl_dc_regulator_control` für den aktuellen Datensatz
        $record = $this->db->fetchAssociative(
            "SELECT title, manufacturer, serialNumber1st, regModel1st, serialNumber2ndPri, regModel2ndPri, serialNumber2ndSec, regModel2ndSec
             FROM tl_dc_regulators
             WHERE id = ?",
            [$dc->pid]
        );

        $this->logger->debug('Fetched record: ' . print_r($record, true));

        // 2. Hole die Dropdown-Optionen aus dem Template `regulator_data.html5`
        $templateOptions = $this->getTemplateOptions('regulator_data');

        // 3. Löse die Indexwerte der `select`-Felder aus der Datenbank in die tatsächlichen Werte auf
        if ($record && $templateOptions) {
            $manufacturerId = $record['manufacturer'];

            // Modelle der ersten Stufe zuordnen
            $regModel1stOptions = $templateOptions[$manufacturerId]['regModel1st'] ?? [];
            if (!empty($regModel1stOptions)) {
                $record['regModel1st'] = $regModel1stOptions[$record['regModel1st']] ?? $record['regModel1st'];
            }

            // Modelle der zweiten Stufe (primär)
            $regModel2ndOptionsPri = $templateOptions[$manufacturerId]['regModel2nd'] ?? [];
            if (!empty($regModel2ndOptionsPri)) {
                $record['regModel2ndPri'] = $regModel2ndOptionsPri[$record['regModel2ndPri']] ?? $record['regModel2ndPri'];
            }

            // Modelle der zweiten Stufe (sekundär)
            $regModel2ndOptionsSec = $templateOptions[$manufacturerId]['regModel2nd'] ?? [];
            if (!empty($regModel2ndOptionsSec)) {
                $record['regModel2ndSec'] = $regModel2ndOptionsSec[$record['regModel2ndSec']] ?? $record['regModel2ndSec'];
            }
        }

        $this->logger->debug('Resolved record: ' . print_r($record, true));

        // 4. Header-Felder in das Array `labels` einfügen
        $labels['title'] = $record['title'] ?? '';
        $labels['Manufacturer'] = $record['manufacturer'] ?? '';
        $labels['First Stage Serial'] = $record['serialNumber1st'] ?? '';
        $labels['First Stage Model'] = $record['regModel1st'] ?? '';
        $labels['Second Stage Serial (Primary)'] = $record['serialNumber2ndPri'] ?? '';
        $labels['Second Stage Model (Primary)'] = $record['regModel2ndPri'] ?? '';
        $labels['Second Stage Serial (Secondary)'] = $record['serialNumber2ndSec'] ?? '';
        $labels['Second Stage Model (Secondary)'] = $record['regModel2ndSec'] ?? '';

        return $labels;
    }

    /**
     * Lädt die Dropdown-Werte (Optionen) aus einem Template wie `regulator_data.html5`.
     */
    private function getTemplateOptions(string $templateName): array
    {
        // Pfad des Templates holen
        $templatePath = TemplateLoader::getPath($templateName, 'html5');

        if (!$templatePath || !file_exists($templatePath)) {
            $this->logger->error('Template not found: ' . $templatePath);
            return [];
        }

        // Template-Datei lesen
        $content = file_get_contents($templatePath);
        $this->logger->debug('Loaded template content: ' . $content);

        $options = [];
        $content = trim($content);
        $content = trim($content, '<?php');
        $content = trim($content, '?>');

        // Auswerten und sicherstellen, dass es sich um ein Array handelt
        eval('$options = ' . $content . ';');

        if (!is_array($options)) {
            $this->logger->error('Invalid template content format.');
            return [];
        }

        return $options;
    }
}
