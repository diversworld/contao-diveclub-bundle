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
        $rentalFee = (float) $dc->activeRecord->rentalFee;        // Das ausgewählte Asset

        // Neues Title-Format
        $newStatus = 'borrowed';


        $subtypes = $this->db->fetchAllAssociative('SELECT * FROM tl_dc_reservation_items WHERE pid = ?', [$dc->id]);

        foreach ($subtypes as $subtype) {
            // Subtype-Status aktualisieren
            $this->db->update(
                'tl_dc_reservation_items',
                [
                    'reservation_status' => $newStatus,
                    'updated_at' => $currentDate,
                ],
                ['id' => $subtype['id']]
            );

            // Asset-Status aktualisieren (tl_dc_equipment_subtypes)
            if (!empty($subtype['asset_id'])) { // Sicherstellen, dass eine asset_id existiert
                $this->db->update(
                    'tl_dc_equipment_subtypes',
                    [
                        'status' => $newStatus,
                        'updated_at' => $currentDate,
                    ],
                    ['id' => $subtype['asset_id']]
                );
            }
        }
        // Status der Reservierung ändern
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
