<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\Config;
use Contao\DataContainer;
use Contao\Date;
use Contao\Input;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;

class ReservationListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection        $db,
        private readonly LoggerInterface $logger,
        private readonly Slug              $slug,
        private readonly DcaTemplateHelper $templateHelper,
    )
    {
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.alias.save')]
    public function onAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_reservation');
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.member_id.save')]
    public function onMemberIdSave($value, DataContainer $dc): string
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        $memberId = (int)$value;
        $existingTitle = $dc->activeRecord->title;

        if ($memberId === 0) {
            return '-0';
        }

        if (!empty($existingTitle)) {
            return $value;
        }

        try {
            $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);
            $currentDateTime = date('dmHi');
            $currentYear = date('Y');

            $newTitle = sprintf('%s-%s-%s', $currentYear, $formattedMemberId, $currentDateTime);

            $dc->activeRecord->title = $newTitle;
            $dc->activeRecord->alias = 'id-' . $newTitle;

            return $value;
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Fehler bei Titelgenerierung in tl_dc_reservation (ID: %d): %s', $dc->id, $e->getMessage())
            );

            return $value;
        }
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.picked_up_at.save')]
    public function onPickedUpAtSave($value, DataContainer $dc): mixed
    {
        if (!$dc->activeRecord || empty($value)) {
            return $value;
        }

        try {
            $newStatus = 'borrowed';
            $currentDate = time();

            $reservationItems = DcReservationItemsModel::findBy('pid', $dc->id);
            if ($reservationItems !== null) {
                foreach ($reservationItems as $reservationItem) {
                    $tableName = $reservationItem->item_type;
                    $allowedTables = ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'];

                    if (in_array($tableName, $allowedTables, true)) {
                        $this->db->update($tableName, ['status' => $newStatus], ['id' => $reservationItem->item_id]);
                        $this->logger->info(sprintf('Status von Asset ID %d in Tabelle %s auf %s geändert.', $reservationItem->item_id, $tableName, $newStatus));
                    }
                }
            }

            $this->db->update('tl_dc_reservation_items', [
                'reservation_status' => $newStatus,
                'updated_at' => $currentDate,
                'picked_up_at' => $currentDate,
            ], ['pid' => $dc->id]);

            $this->db->update('tl_dc_reservation', ['reservation_status' => $newStatus], ['id' => $dc->id]);

        } catch (Exception $e) {
            $this->logger->error(sprintf('Fehler beim Aktualisieren der Assets für Reservierung ID %d: %s', $dc->id, $e->getMessage()), [__METHOD__]);
        }

        return $value;
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.returned_at.save')]
    public function onReturnedAtSave($value, DataContainer $dc): mixed
    {
        if (!$dc->activeRecord || !$value) {
            return $value;
        }

        try {
            $currentDate = $value;
            $newStatus = 'returned';
            $itemStatus = 'available';

            $dc->activeRecord->reservation_status = $newStatus;

            $reservationItems = DcReservationItemsModel::findBy('pid', $dc->id);
            if ($reservationItems !== null) {
                foreach ($reservationItems as $reservationItem) {
                    $tableName = $reservationItem->item_type;
                    $allowedTables = ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'];

                    if (in_array($tableName, $allowedTables, true)) {
                        $this->db->update($tableName, ['status' => $itemStatus], ['id' => $reservationItem->item_id]);
                        $this->logger->info(sprintf('Status von Asset ID %d in Tabelle %s auf %s geändert.', $reservationItem->item_id, $tableName, $itemStatus));
                    }
                }
            }

            $this->db->update('tl_dc_reservation_items', [
                'reservation_status' => $newStatus,
                'updated_at' => $currentDate,
                'returned_at' => $currentDate,
            ], ['pid' => $dc->id]);

            $this->db->update('tl_dc_reservation', ['reservation_status' => $newStatus], ['id' => $dc->id]);

        } catch (Exception $e) {
            $this->logger->error(sprintf('Fehler beim Aktualisieren der Assets für Reservierung ID %d: %s', $dc->id, $e->getMessage()), [__METHOD__]);
        }

        return $value;
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.reservation_status.save')]
    public function onStatusSave($value, DataContainer $dc): string
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        if ($dc->activeRecord->reservation_status === $value && $dc->field !== 'picked_up_at') {
            return $value;
        }

        if ($dc->field === 'picked_up_at' && $value) {
            $value = 'borrowed';
        }

        $this->db->update('tl_dc_reservation_items', [
            'reservation_status' => $value,
            'updated_at' => time(),
        ], ['pid' => $dc->id]);

        return $value;
    }

    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.title.save')]
    public function onTitleSave($value, DataContainer $dc): string
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        $memberId = (int)$dc->activeRecord->member_id;
        if ($memberId === 0) {
            return '-0';
        }

        if (!empty($dc->activeRecord->title) && !empty($value) && $value === '-0') {
            return $dc->activeRecord->title;
        }

        try {
            $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);
            $newTitle = sprintf('%s-%s-%s', date('Y'), $formattedMemberId, date('dmHi'));

            return $newTitle;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Fehler bei Titelgenerierung in tl_dc_reservation (ID: %d): %s', $dc->id, $e->getMessage()));
            return $value;
        }
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'config.onsubmit')]
    public function onItemSubmit(DataContainer $dc): void
    {
        if (!$dc->id || !$dc->activeRecord) {
            return;
        }

        // Auslesen der relevanten Felder
        $pickedUpAt = $dc->activeRecord->picked_up_at;
        $returnedAt = $dc->activeRecord->returned_at;
        $reservationStatus = $dc->activeRecord->reservation_status;
        $itemType = $dc->activeRecord->item_type;           // Z. B. `tl_dc_tanks`, `tl_dc_regulators`, `tl_dc_equipment`
        $assetId = (int)$dc->activeRecord->item_id;        // Das ausgewählte Asset

        if ($reservationStatus === 'returned' || $reservationStatus === 'cancelled') {
            $status = 'available';
        } else {
            $status = $reservationStatus;    // Neuer Status (z. B. aus Ihrer Reservierungslogik)
        }

        if (!$itemType || !$assetId) {
            return;
        }

        // Prüfen, ob es sich um eine unterstützte Tabelle handelt
        $allowedTables = [
            'tl_dc_tanks',
            'tl_dc_regulators',
            'tl_dc_equipment',
        ];

        if (!in_array($itemType, $allowedTables, true)) {
            // Falls die Tabelle nicht erlaubt ist, nichts tun
            $this->logger->error(sprintf('Ungültige Tabelle: %s für Asset ID %d', $itemType, $assetId), [__METHOD__]);
            return;
        }

        // Keep the detail record in sync with the selected asset. Type and
        // subtype belong to general equipment only; for tanks and regulators
        // they must stay empty instead of containing stale values.
        $equipmentClassification = ['types' => '', 'sub_type' => ''];

        if ('tl_dc_equipment' === $itemType) {
            $equipment = $this->db->fetchAssociative(
                'SELECT type, subType FROM tl_dc_equipment WHERE id = ?',
                [$assetId],
            );

            if (!$equipment) {
                $this->logger->error(sprintf('Equipment ID %d wurde nicht gefunden.', $assetId), [__METHOD__]);

                return;
            }

            $equipmentClassification = [
                'types' => (string)$equipment['type'],
                'sub_type' => (string)$equipment['subType'],
            ];
        }

        $this->db->update(
            'tl_dc_reservation_items',
            $equipmentClassification,
            ['id' => (int)$dc->id],
        );

        // 1. Überprüfen, ob der Status einer der speziellen Werte ist
        $specialStatuses = ['overdue', 'lost', 'damaged', 'missing'];

        if (in_array($reservationStatus, $specialStatuses, true)) {
            // Status direkt in Asset-Tabelle und Reservierungs-Tabelle setzen
            $this->db->update(
                $itemType,                 // Asset-Tabelle
                ['status' => $reservationStatus],
                ['id' => $assetId]
            );

            $this->db->update(
                'tl_dc_reservation_items', // Reservierungs-Tabelle
                ['reservation_status' => $reservationStatus],
                ['id' => (int)$dc->id]
            );

            return; // Keine weitere Verarbeitung erforderlich
        }

        // 2. Standard-Logik für Borrowed/Available
        if (!empty($pickedUpAt) && empty($returnedAt)) {
            $status = 'borrowed';
            $reservationStatus = 'borrowed';
        } elseif (!empty($pickedUpAt) && !empty($returnedAt)) {
            $status = 'available';
            $reservationStatus = 'returned';
        }

        // Status des entsprechenden Assets in der richtigen Tabelle aktualisieren
        $this->db->update(
            $itemType,                  // Tabelle aus item_type
            ['status' => $status],      // Zu setzende Spalten
            ['id' => $assetId]         // Bedingung (ID des Assets)
        );
        $this->db->update(
            'tl_dc_reservation_items',
            ['reservation_status' => $reservationStatus],
            ['id' => (int)$dc->id]
        );
    }

    /**
     * Return the assets belonging to the selected asset type.
     *
     * All assets are deliberately returned here, irrespective of their current
     * status. Otherwise an already reserved asset would disappear from the
     * select field when an existing reservation item is edited.
     */
    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.item_id.options')]
    public function getAssetOptions(DataContainer $dc): array
    {
        $allowedTables = [
            'tl_dc_tanks',
            'tl_dc_regulators',
            'tl_dc_equipment',
        ];

        $postedItemType = Input::post('item_type');
        $itemType = \is_string($postedItemType) && '' !== $postedItemType
            ? $postedItemType
            : (string)($dc->activeRecord->item_type ?? '');

        if (!\in_array($itemType, $allowedTables, true)) {
            return [];
        }

        return $this->db->fetchAllKeyValue(
            sprintf('SELECT id, title FROM %s ORDER BY title', $itemType),
        );
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.types.options')]
    public function getEquipmentTypeOptions(): array
    {
        return $this->templateHelper->getEquipmentFlatTypes();
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.sub_type.options')]
    public function getEquipmentSubTypeOptions(DataContainer $dc): array
    {
        $postedType = Input::post('types');
        $type = \is_scalar($postedType) && '' !== (string)$postedType
            ? (int)$postedType
            : (int)($dc->activeRecord->types ?? 0);

        return $type > 0 ? $this->templateHelper->getSubTypes($type) : [];
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            $labels = $args;
            $itemType = (string)($row['item_type'] ?? '');
            $allowedTables = ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'];
            $assetTitle = '-';

            if (in_array($itemType, $allowedTables, true) && !empty($row['item_id'])) {
                $assetTitle = (string)($this->db->fetchOne(
                    sprintf('SELECT title FROM %s WHERE id = ?', $itemType),
                    [(int)$row['item_id']],
                ) ?: '-');
            }

            $labels[0] = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'][$itemType] ?? $itemType;
            $labels[1] = $assetTitle;
            $labels[2] = '-';
            $labels[3] = '-';

            if ('tl_dc_equipment' === $itemType) {
                $equipmentTypes = $this->templateHelper->getEquipmentFlatTypes();
                $equipmentSubTypes = $this->templateHelper->getSubTypes((int)($row['types'] ?? 0));
                $labels[2] = $equipmentTypes[$row['types'] ?? ''] ?? ' ';
                $labels[3] = $equipmentSubTypes[$row['sub_type'] ?? ''] ?? ' ';
            }
            $labels[4] = !empty($row['created_at'])
                ? Date::parse(Config::get('datimFormat'), (int)$row['created_at'])
                : '-';
            $labels[5] = !empty($row['updated_at'])
                ? Date::parse(Config::get('datimFormat'), (int)$row['updated_at'])
                : '-';
            $labels[6] = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$row['reservation_status'] ?? '']
                ?? (string)($row['reservation_status'] ?? '');

            // In MODE_PARENT Contao does not render showColumns. If an array
            // is returned, only its first element becomes the child label.
            // Therefore all configured columns must be combined into the
            // label string here.
            return vsprintf(
                $GLOBALS['TL_DCA']['tl_dc_reservation_items']['list']['label']['format'] ?? '%s',
                array_values($labels),
            );
        }

        return $label;
    }
}
