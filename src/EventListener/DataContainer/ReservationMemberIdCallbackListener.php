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

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.member_id.save')]
class ReservationMemberIdCallbackListener
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

        $memberId = (int) $value; // Letzte Eingabe der `member_id`
        $currentMemberId = (int) $dc->activeRecord->member_id; // Aktueller in der Datenbank gespeicherter Wert
        $existingTitle = $dc->activeRecord->title; // Aktueller Titelwert

        // Falls keine member_id vorhanden ist, nichts tun
        if ($memberId === 0) {
            return '-0';
        }

        if (!empty($existingTitle)) {
            // Wenn ein Titel existiert, diesen Wert zurückgeben
            return $value;
        }

        try {
            // Führende Nullen hinzufügen, um die member_id dreistellig zu machen
            $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);
            // Datum im Format jjjjmmtt
            $currentDateTime = date('dmHi');
            $currentYear = date('Y');

            // Neues Title-Format
            $newTitle = sprintf('%s-%s-%s', $currentYear, $formattedMemberId, $currentDateTime);

            // Titel dem `activeRecord` zuweisen
            $dc->activeRecord->title = $newTitle;

            // Optional: Alias generieren, falls gewünscht
            $alias = 'id-' . $newTitle;
            $dc->activeRecord->alias = $alias;

            return $value; // Nur neuen Titel zurückgeben (kein Datenbank-Update hier)

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
