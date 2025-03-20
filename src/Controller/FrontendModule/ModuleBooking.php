<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */
namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(ModuleBooking::TYPE, category: 'dc_modules', template: 'mod_dc_booking')]
class ModuleBooking extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

    protected ?PageModel $page;

    private DcaTemplateHelper $helper;

    public function __construct(DcaTemplateHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        $scopeMatcher = $this->container->get('contao.routing.scope_matcher');

        if ($this->page instanceof PageModel && $scopeMatcher->isFrontendRequest($request)) {
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Lazyload services.
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['database_connection'] = Connection::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $database = $this->container->get('database_connection');
        $security = $this->container->get('security.helper');
        $user = $security->getUser();

        // 1. Auswahl der Kategorie
        $category = $request->query->get('category'); // 'tank', 'regulator', 'equipment'
        $type = $request->query->get('equipmentType'); // z.B. 'BCD'

        if (!$category) {
            $template->categories = ['Tank', 'Regulator', 'Equipment'];
        } elseif ($category === 'equipment' && !$type) {
            // Equipment-Typen laden
            $equipmentTypes = $database->fetchAllAssociative("SELECT id, title FROM tl_dc_equipment_types");
            $template->equipmentTypes = $equipmentTypes;
        } else {
            // 2. Anzeige verfügbarer Ressourcen
            $query = $database->createQueryBuilder()
                ->select('*')
                ->from("tl_dc_{$category}") // Dynamische Tabellenauswahl
                ->where('id NOT IN (SELECT asset_id FROM tl_dc_reservation WHERE asset_type = :category)')
                ->setParameter('category', $category);

            if ($type) {
                $query->andWhere('equipment_type = :type')->setParameter('type', $type);
            }

            $template->assets = $query->execute()->fetchAllAssociative();
        }

        // 3. Reservierung speichern
        if ($request->isMethod('POST') && $request->request->get('reserve')) {
            $selectedAssets = $request->request->get('selectedAssets');

            foreach ($selectedAssets as $assetId) {
                $database->insert('tl_dc_reservation', [
                    'asset_type' => $category,
                    'asset_id' => $assetId,
                    'user_id' => $user->id,
                    'reserved_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
            }

            $template->success = 'Reservierung erfolgreich durchgeführt!';
        }

        return $template->getResponse();
    }

    public function updateReservationStatus(int $reservationId, string $status): void
    {
        $database = $this->container->get('database_connection');

        if ($status === 'picked_up') {
            $database->update('tl_dc_reservation', ['picked_up_at' => (new \DateTime())->format('Y-m-d H:i:s')], ['id' => $reservationId]);
        } elseif ($status === 'returned') {
            $database->update('tl_dc_reservation', ['returned_at' => (new \DateTime())->format('Y-m-d H:i:s')], ['id' => $reservationId]);
        }
    }
}
