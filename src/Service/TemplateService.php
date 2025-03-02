<?php
namespace Diversworld\ContaoDiveclubBundle\Service;

use Contao\DataContainer;
use Psr\Log\LoggerInterface;
use Contao\TemplateLoader;

class TemplateService
{
    private string $projectDir;
    private LoggerInterface $logger;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }

    public function getManufacturers(): array
    {
        return $this->getTemplateOptions('dc_equipment_manufacturers');
    }

    public function getSizes(): array
    {
        return $this->getTemplateOptions('dc_equipment_sizes');
    }

    public function getTypes():array
    {
        return $this->getTemplateOptions('dc_equipment_types');
    }

    public function getSubTypes(DataContainer $dc): array
    {
        // Sicherstellen, dass ein aktiver Datensatz vorhanden ist
        if (!$dc->activeRecord) {
            return [];
        }

        // Ermittle den aktuellen Typ aus dem aktiven Datensatz
        $currentType = $dc->activeRecord->title;

        $subTypes = $this->getTemplateOptions('dc_equipment_subTypes');

        // Prüfen, ob für den aktuellen Typ Subtypen definiert wurden
        if (!isset($subTypes[$currentType]) || !is_array($subTypes[$currentType])) {
            // Keine passenden Subtypen gefunden -> leere Liste zurückgeben
            return [];
        }

        // Nur die relevanten Subtypen für diesen Typ zurückgeben
        return $subTypes[$currentType];
    }

    public function getTemplateOptions(string $templateName): array
    {
        $rootTemplatePath = $this->projectDir . '/templates/diveclub/' . $templateName . '.html5';

        $this->logger->debug('Template: ' . TemplateLoader::getPath($templateName, 'html5'));

        if (is_readable($rootTemplatePath)) {
            return $this->parseTemplateFile($rootTemplatePath);
        }

        $this->logger->error('Template not found or not readable: ' . $rootTemplatePath);

        // If not in root directory, check module directory
        $moduleTemplatePath = TemplateLoader::getPath($templateName, 'html5');
        if ($moduleTemplatePath && file_exists($moduleTemplatePath)) {
            return $this->parseTemplateFile($moduleTemplatePath);
        }

        $this->logger->error('Template not found: ' . $templateName);
        throw new \Exception(sprintf('Template not found: %s', $templateName));
    }

    private function parseTemplateFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $content = trim($content, '<?=');
        $content = trim($content, '?>');

        $options = [];
        eval('$options = ' . $content . ';');

        if (!is_array($options)) {
            throw new \Exception(sprintf('Invalid template content in file: %s', $filePath));
        }

        return $options;
    }
}
