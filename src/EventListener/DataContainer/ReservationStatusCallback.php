<?php


namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.reservation_status.save')]
class ReservationStatusCallback
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke($value, DataContainer $dc): string
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        // Datum im Format jjjjmmtt
        $currentDate = time();

        // Neues Title-Format
        $newStatus = $value;

        $this->db->update(
            'tl_dc_reservation_items', // Reservierungs-Tabelle
            [
                'reservation_status' => $newStatus,
                'updated_at' => $currentDate,
            ],
            ['pid' => $dc->id]
        );

        return $newStatus;
    }
}
