<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.title.save')]
class ReservationTitleCallback
{
    private Connection $db;
    private LoggerInterface $logger;


    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function __invoke($value, DataContainer $dc): string
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        $memberId = (int) $dc->activeRecord->member_id;

        // Falls keine member_id vorhanden ist, nichts tun
        if ($memberId === 0) {
            return '-0';
        }

        // Prüfen, ob der Titel bereits existiert
        $existingTitle = $dc->activeRecord->title;
        if (!empty($existingTitle && !empty($value))) {
            // Wenn ein Titel existiert, diesen Wert zurückgeben
            return $existingTitle;
        }

        try {
            // Führende Nullen hinzufügen, um die member_id dreistellig zu machen
            $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);

            // Datum im Format jjjjmmtt
            $currentDate = date('dmHi');
            $currentYear = date('Y');

            // Neues Title-Format
            $newTitle = $currentYear . '-' . $formattedMemberId . '-' . $currentDate;

            // Optional: alias automatisch setzen
            $alias = 'id-' . $newTitle;

            return $newTitle; // Nur neuen Titel zurückgeben (kein Datenbank-Update hier)
        } catch (Exception $e) {
            // Fehlerprotokollierung
            $this->logger->error(
                sprintf('Fehler bei Titelgenerierung in tl_dc_reservation (ID: %d): %s', $dc->id, $e->getMessage())
            );

            // Originalwert zurückgeben, wenn etwas schiefgeht
            return $value;
        }
    }
}
