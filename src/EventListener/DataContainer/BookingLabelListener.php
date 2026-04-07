<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_booking', target: 'list.label.label')] // Registriert die Klasse als Label-Callback für die Tabelle tl_dc_check_booking
class BookingLabelListener // Listener zur Anpassung der Label-Anzeige in der Backend-Liste
{
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): string|array // Methode zur Formatierung des Labels
    {
        return sprintf( // Fallback für die Standard-Anzeige (wenn keine Spalten genutzt werden)
            '[%s] %s, %s - %s € - %s',
            $row['bookingNumber'],
            $row['lastname'],
            $row['firstname'],
            number_format((float) $row['totalPrice'], 2, ',', '.'),
            $GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'][$row['status']]
        ); // Gib den formatierten String zurück
    }
}
