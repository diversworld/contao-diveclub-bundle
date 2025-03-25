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

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke($value, DataContainer $dc): string
    {
        dump($dc->activeRecord);
        if (!$dc->activeRecord) {
            return '-';
        }

        $memberId = (int) $dc->activeRecord->member_id;

        // Falls keine member_id vorhanden ist, nichts tun
        if ($memberId === 0) {
            return '-0';
        }

        // Führende Nullen hinzufügen, um die member_id dreistellig zu machen
        $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);

        // Datum im Format jjjjmmtt
        $currentDate = date('Ymd');

        // Neues Title-Format
        $newTitle = $currentDate . $formattedMemberId;

        $this->db->update(
            'tl_dc_reservation', // Reservierungs-Tabelle
            ['title' => $newTitle],
            ['id' => $dc->id]
        );

        return $newTitle;
    }
}
