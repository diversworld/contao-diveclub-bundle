<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Contao\Database;
use Contao\System;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[AsCallback(table: 'tl_dc_check_proposal', target: 'fields.checkId.save')]
class ProposalEventVendorInfoListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(mixed $varValue, DataContainer $dc): mixed
    {
        // Prüfe, ob der Wert gesetzt ist (keine leere Auswahl)
        if (!empty($varValue)) {
            // Hole die Datenbank-Instanz
            $db = Database::getInstance();

            // Lade die vorhandenen Event-Daten aus der Tabelle tl_calendar_events
            $event = $db->prepare("SELECT * FROM tl_calendar_events WHERE id = ?")
                ->execute($varValue);

            if ($event->numRows > 0) {
                // Hole den Vendor-Namen aus dem aktuellen tl_dc_check_proposal-Datensatz
                $vendor = $dc->activeRecord->id;

                // Update der Vendor-Info für das Event
                $db->prepare("UPDATE tl_calendar_events SET addVendorInfo = ? WHERE id = ?")
                    ->execute($vendor, $varValue);

                // Optional: Protokollieren, dass der Vendor eingetragen wurde
                $this->logger->info(
                    'Vendor-Info für Event-ID ' . $varValue . ' aktualisiert: ' . $vendor,
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]
                );
            } else {
                throw new RuntimeException(sprintf('Das Event mit der ID %d existiert nicht.', $varValue));
            }
        }
        // Rückgabe des gespeicherten Wertes
        return $varValue;
    }
}
