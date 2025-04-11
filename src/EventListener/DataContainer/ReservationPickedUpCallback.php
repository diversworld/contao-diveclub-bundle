<?php


namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.picked_up_at.save')]
class ReservationPickedUpCallback
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
        $this->logger->info('ReservationPickedUpCallback'. $value, [__METHOD__, 'ReservationPickedUpCallback']);
        if (!$dc->activeRecord) {
            return '-';
        }

        // Datum im Format jjjjmmtt
        $currentDate = $value;
        // Neues Title-Format
        $newStatus = 'borrowed';

        $subtypes = DcReservationItemsModel::findBy('pid', $dc->id);

        foreach ($subtypes as $subtype) {
            // Subtype-Status aktualisieren
            $this->db->update(
                'tl_dc_reservation_items',
                [
                    'reservation_status' => $newStatus,
                    'updated_at' => $currentDate,
                ],
                ['id' => $subtype->id]
            );

            // Asset-Status aktualisieren (tl_dc_equipment_subtypes)
            if (!empty($subtype->asset_id)) { // Sicherstellen, dass eine asset_id existiert
                $this->db->update(
                    'tl_dc_equipment_subtypes',
                    [
                        'status' => $newStatus,
                        'updated_at' => $currentDate,
                    ],
                    ['id' => $subtype->asset_id]
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
