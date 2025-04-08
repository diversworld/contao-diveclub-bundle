<?php


namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.returned_at.save')]
class ReservationReturnedCallback
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
        $currentDate = $value;

        // Neues Title-Format
        $newStatus = 'returned';

        $this->db->update(
            'tl_dc_reservation_items', // Reservierungs-Tabelle
            [
                'reservation_status' => $newStatus,
                'updated_at' => $currentDate,
                'returned_at' => $currentDate,
            ],
            ['pid' => $dc->id]
        );
        $this->db->update(
            'tl_dc_reservation', // Reservierungs-Tabelle
            [
                'reservation_status' => $newStatus,
            ],
            ['id' => $dc->id]
        );

        return $value;
    }
}
