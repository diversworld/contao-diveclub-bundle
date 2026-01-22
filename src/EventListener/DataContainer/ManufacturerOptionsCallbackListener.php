<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

#[AsCallback(table: 'tl_dc_regulators', target: 'fields.manufacturer.options')] // Registriert die Klasse als Options-Callback für das Feld manufacturer in tl_dc_regulators
class ManufacturerOptionsCallbackListener // Listener zum Befüllen des Hersteller-Dropdowns
{
    private DcaTemplateHelper $templateHelper; // Variable für den Template Helper

    public function __construct(DcaTemplateHelper $templateHelper) // Konstruktor mit Dependency Injection des Helpers
    {
        $this->templateHelper = $templateHelper; // Zuweisung des Helpers
    }

    public function __invoke(): array // Methode die beim Laden der Optionen aufgerufen wird
    {
        return $this->templateHelper->getManufacturers(); // Holt die Hersteller-Liste über den Helper aus der konfigurierten Template-Datei
    }
}
