<?php


declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;

class DcEquipmentType
{
    private ContaoFramework $framework;
    private TemplateService $templateService;

    public function __construct(ContaoFramework $framework, TemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->framework = $framework;
    }

    public function callbackGetTypes(): array
    {
        return $this->templateService->getTypes();
    }

    public function getSubTypes(DataContainer $dc): array
    {
        return $this->templateService->getSubTypes($dc);
    }

    public function subTypeLabel(array $row, string $label, DataContainer $dc = null): string
    {
        // Lade die Subtypen aus der Template-Datei
        $subTypes = $this->templateService->getTemplateOptions('dc_equipment_subTypes');

        // Ermittle den aktuellen Subtypen-Text basierend auf dem gespeicherten Typ und Subtyp
        $currentType = $row['title']; // Titel/Typ aus der Datenbankzeile
        $subTypeId = $row['subType']; // Subtype-ID aus der Datenbankzeile

        // Standardwert, falls keine Zuordnung gefunden wird
        $subTypeName = $subTypeId;

        // Überprüfen, ob der Titel/Subtype im Array existiert
        if (isset($subTypes[$currentType]) && isset($subTypes[$currentType][$subTypeId])) {
            $subTypeName = $subTypes[$currentType][$subTypeId];
        }

        // Label als Kombination aus Titel und Subtype-Name zurückgeben
        return sprintf('%s', $subTypeName);
    }
}
