<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Controller;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


#[AsFrontendModule(BookingConfirmationController::TYPE, category: 'dc_manager')]
class BookingConfirmationController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_check_confirmation';
    public function __construct(
    )
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $session = $request->getSession();
        $orderId = $session->get('last_tank_check_order');

        if (!$orderId) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $order = DcCheckOrderModel::findByPk($orderId);

        if (null === $order) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $booking = DcCheckBookingModel::findByPk($order->pid);

        if (null === $booking) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // Get all orders for this booking
        $orders = DcCheckOrderModel::findBy('pid', $booking->id);

        // Load language file for orders to ensure translations are available
        Controller::loadLanguageFile('tl_dc_check_order');

        $orderData = [];
        if (null !== $orders) {
            foreach ($orders as $order) {
                $data = $order->row();
                $data['status_label'] = $GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'][$order->status] ?? $order->status;
                $orderData[] = $data;
            }
        }

        // Parse confirmation text with insert tags
        $parser = System::getContainer()->get('contao.insert_tag.parser');

        $headline = StringUtil::deserialize($model->headline);
        $headlineData = null;
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $headlineData = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        }

        return new Response($this->twig->render(
            '@Contao/frontend_module/dc_check_confirmation.html.twig',
            [
                'booking' => $booking->row(),
                'orders' => $orderData,
                'confirmation_text' => $parser->replace($model->confirmation_text ?: ''),
                'element_html_id' => 'mod_' . $model->id,
                'element_css_classes' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
                'class' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
                'cssID' => $model->cssID[0] ?? '',
                'type' => $model->type,
                'headline' => $headlineData,
            ]
        ));
    }
}
