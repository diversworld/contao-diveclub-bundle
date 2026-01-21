<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Service\TankCheckPdfGenerator;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.onsubmit')]
class BookingPdfGenerationListener
{
    public function __construct(private readonly TankCheckPdfGenerator $pdfGenerator)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        // Check if status has been changed to 'pickedup'
        // Since onsubmit is called after the record is saved to the database (if it's not a new record)
        // or during the process. activeRecord contains the new values.

        if ($dc->activeRecord->status === 'pickedup') {
            $this->pdfGenerator->generateForBooking((int) $dc->activeRecord->id);
        }
    }
}
