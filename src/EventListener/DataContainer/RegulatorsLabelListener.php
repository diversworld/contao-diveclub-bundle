<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_regulators', target: 'list.label.label')]
class RegulatorsLabelListener
{
    private DcaTemplateHelper $templateHelper;
    private Connection $db;

    public function __construct(DcaTemplateHelper $templateHelper, Connection $db)
    {
        $this->templateHelper = $templateHelper;
        $this->db = $db;
    }

    /**
     * @param array $row
     * @param string $label
     * @param DataContainer|null $dc
     * @param array|null $args
     * @return array|string
     */
    public function __invoke(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): array|string
    {
        if (null === $args) {
            return $label;
        }

        $manufacturerId = (int) $row['manufacturer'];
        $manufacturers = $this->templateHelper->getManufacturers();

        $args[1] = $manufacturers[$manufacturerId] ?? '-';

        $models1st = $this->templateHelper->getRegModels1st($manufacturerId, $dc);
        $models2nd = $this->templateHelper->getRegModels2nd($manufacturerId, $dc);

        $args[2] = $models1st[(int) $row['regModel1st']] ?? '-';
        $args[3] = $models2nd[(int) $row['regModel2ndPri']] ?? '-';
        $args[4] = $models2nd[(int) $row['regModel2ndSec']] ?? '-';

        // Get the latest revision date
        $latestRevision = $this->db->fetchOne(
            "SELECT actualCheckDate FROM tl_dc_regulator_control WHERE pid = ? ORDER BY actualCheckDate DESC LIMIT 1",
            [$row['id']]
        );

        $args[6] = $latestRevision ? Date::parse(Config::get('dateFormat'), (int) $latestRevision) : '-';

        return $args;
    }
}
