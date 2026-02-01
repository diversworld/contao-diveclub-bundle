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
        if (null === $args) { // Fallback für die Standard-Anzeige (wenn keine Spalten genutzt werden)
            return sprintf(
                '%s (%sL) - %s € [%s]',
                $row['serialNumber'], // Seriennummer
                $row['size'], // Flaschengröße
                number_format((float)$row['totalPrice'], 2, ',', '.'), // Gesamtpreis formatiert
                $row['status'] // Status-Code
            ); // Gib den formatierten String zurück
        }

        $args[0] = sprintf( // Spalte 1: Gerät und Größe
            '%s (%sL)',
            $row['serialNumber'],
            $row['size']
        );
        $args[1] = number_format((float)$row['totalPrice'], 2, ',', '.') . ' €'; // Spalte 2: Preis formatiert
        $args[2] = $GLOBALS['TL_LANG']['tl_dc_check_order'][$row['status']]; // Spalte 3: Übersetzter Status

        return $args; // Gib die aktualisierten Argumente zurück
    }
}
