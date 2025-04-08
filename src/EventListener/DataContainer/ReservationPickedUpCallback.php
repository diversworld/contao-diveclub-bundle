<?php


namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.picked_up.save')]
class ReservationPickedUpCallback
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
        $assetId = (int) $dc->activeRecord->item_id;        // Das ausgewählte Asset

        // Neues Title-Format
        $newStatus = 'borrowed';


        // Status der Items aktualisieren
        $this->db->update(
            'tl_dc_reservation_items', // Reservierungs-Tabelle
            [
                'reservation_status' => $newStatus,
                'updated_at' => $currentDate,
                'picked_up_at' => $currentDate,
            ],
            ['pid' => $dc->id]
        );

        // Status der Reservierung ändern
        $this->db->update(
            'tl_dc_reservation', // Reservierungs-Tabelle
            [
                'reservation_status' => $newStatus,
            ],
            ['id' => $dc->id]
        );

        // Status der Assets ändern
        $this->db->update(
            'tl_dc_equipment_subTypes',
            [
                'status' => $newStatus,
            ],
            [ 'id' => $assetId ],
        );

        return $value;
    }
}
