<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Service\TankCheckPdfGenerator;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.onsubmit')] // Registriert die Klasse als onsubmit-Callback für die Tabelle tl_dc_check_booking
class BookingPdfGenerationListener // Listener zur automatischen PDF-Generierung beim Speichern einer Buchung
{
    public function __construct(private readonly TankCheckPdfGenerator $pdfGenerator) // Konstruktor mit Dependency Injection des PDF-Generators
    {
    }

    public function __invoke(DataContainer $dc): void // Methode die beim Speichern des Datensatzes ausgeführt wird
    {
        if (!$dc->activeRecord) { // Falls kein aktiver Datensatz vorhanden ist
            return; // Abbrechen
        }

        // Check if status has been changed to 'pickedup'
        // Since onsubmit is called after the record is saved to the database (if it's not a new record)
        // or during the process. activeRecord contains the new values.

        if ($dc->activeRecord->status === 'pickedup') { // Wenn der Status auf "Abgeholt" (pickedup) gesetzt wurde
            $this->pdfGenerator->generateForBooking((int) $dc->activeRecord->id); // Generiere automatisch das PDF für diese Buchung
        }
    }
}
