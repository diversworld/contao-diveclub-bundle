<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_order', target: 'list.label.label')] // Registriert die Klasse als Label-Callback für die Tabelle tl_dc_check_order
class OrderLabelListener // Listener zur Anpassung der Label-Anzeige für Bestellpositionen
{
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string // Methode zur Formatierung des Labels
    {
        $sizeLabel = $GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'][$row['size']] ?? $row['size'];
        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'][$row['status']] ?? $row['status'];

        if (null === $args) { // Fallback für die Standard-Anzeige (wenn keine Spalten genutzt werden)
            return sprintf(
                '%s (%s) - %s € [%s]',
                $row['serialNumber'], // Seriennummer
                $sizeLabel, // Flaschengröße (lokalisierte Bezeichnung)
                number_format((float)$row['totalPrice'], 2, ',', '.'), // Gesamtpreis formatiert
                $statusLabel // Status (lokalisiert)
            ); // Gib den formatierten String zurück
        }

        $args[0] = sprintf( // Spalte 1: Gerät und Größe
            '%s (%s)',
            $row['serialNumber'],
            $sizeLabel
        );
        $args[1] = number_format((float)$row['totalPrice'], 2, ',', '.') . ' €'; // Spalte 2: Preis formatiert
        $args[2] = $statusLabel; // Spalte 3: Übersetzter Status

        return $args; // Gib die aktualisierten Argumente zurück
    }
}
