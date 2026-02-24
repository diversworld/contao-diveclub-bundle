<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;

class ReservationItemsCallbackListener
{
    private DcaTemplateHelper $helper;

    public function __construct(DcaTemplateHelper $helper)
    {
        $this->helper = $helper;
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.item_id.options')]
    public function onAssetOptionsCallback(DataContainer $dc): array
    {
        // Sicherstellen, dass $dc->activeRecord existiert und item_type gesetzt ist
        if (!$dc->activeRecord || !$dc->activeRecord->item_type) {
            return [];
        }

        // Die ausgewählte Tabelle basierend auf item_type ermitteln
        $tableName = $dc->activeRecord->item_type;

        // Prüfen, ob die Tabelle existiert (Sicherheitsvorkehrung)
        if (!\in_array($tableName, ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'], true)) {
            return [];
        }

        $options = [];

        switch ($tableName) {
            case 'tl_dc_tanks':
                $result = DcTanksModel::findPublished();
                if (null !== $result) {
                    while ($result->next()) {
                        $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                        $status = $result->status ? ' (' . $statusText . ')' : '';

                        $options[$result->id] = $result->size . "L - " . $result->title . ' ' . $status;
                    }
                }
                break;

            case 'tl_dc_regulators':
                $result = DcRegulatorsModel::findPublished();
                if (null !== $result) {
                    while ($result->next()) {
                        $title = $result->title ?? 'Keine Nummer';
                        $manufacturerName = $this->helper->getManufacturers()[$result->manufacturer] ?? 'Unbekannter Hersteller';
                        $regModel1st = $this->helper->getRegModels1st((int)$result->manufacturer)[$result->regModel1st] ?? 'keiner';
                        $regModel2ndPri = $this->helper->getRegModels2nd((int)$result->manufacturer)[$result->regModel2ndPri] ?? 'keiner';
                        $regModel2ndSec = $this->helper->getRegModels2nd((int)$result->manufacturer)[$result->regModel2ndSec] ?? 'keiner';
                        $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                        $status = $result->status ? ' (' . $statusText . ')' : '';
                        $options[$result->id] = $title . " - " . $manufacturerName . " - " . $regModel1st . ' - ' . $regModel2ndPri . ' - ' . $regModel2ndSec . ' ' . $status;
                    }
                }
                break;

            case 'tl_dc_equipment':
                if (!$dc->activeRecord->types || !$dc->activeRecord->sub_type) {
                    return [];
                }

                $result = DcEquipmentModel::findBy(
                    ['type = ? AND subType = ? AND published = ?'],
                    [$dc->activeRecord->types, $dc->activeRecord->sub_type, '1']
                );

                if (null === $result || $result->count() < 1) {
                    return [];
                }

                $vendors = $this->helper->getManufacturers();
                $sizes = $this->helper->getSizes();

                while ($result->next()) {
                    $manufacturerName = $vendors[$result->manufacturer] ?? 'Unbekannter Hersteller';
                    $size = $sizes[$result->size] ?? 'Unbek. Größe';
                    $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                    $status = $result->status ? ' (' . $statusText . ')' : '';

                    $options[$result->id] = $manufacturerName . ' - ' . $result->model . ' - ' . $size . ' ' . $status;
                }
                break;
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.item_id.load')]
    public function onAssetLoadCallback($value, DataContainer $dc)
    {
        if (!$dc->activeRecord || !$dc->activeRecord->item_id) {
            return $value;
        }

        $database = Database::getInstance();
        $item = $database->prepare("SELECT title, status FROM tl_dc_equipment WHERE id = ?")
            ->execute($dc->activeRecord->item_id);

        if ($item->numRows > 0) {
            $status = $item->status === 'reserved' ? ' (Reserviert)' : ' (Verfügbar)';
            $_SESSION['TL_INFO'][] = sprintf(
                'Aktuell ausgewähltes Asset: <strong>%s</strong>%s',
                $item->title,
                $status
            );
        }

        return $value;
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.types.options')]
    public function onTypeOptionsCallback(DataContainer $dc): array
    {
        return $this->helper->getEquipmentFlatTypes();
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.created_at.save')]
    public function onCreatedAtSaveCallback($value, DataContainer $dc)
    {
        if (!empty($value)) {
            return (int)$value;
        }

        return time();
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.updated_at.save')]
    public function onUpdatedAtSaveCallback($value, DataContainer $dc)
    {
        return time();
    }
}
