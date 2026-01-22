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

#[AsFrontendModule('dc_check_confirmation', category: 'dc_manager', template: 'frontend_module/mod_dc_check_confirmation')]
class BookingConfirmationController extends AbstractFrontendModuleController
{
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
        $template->booking = $booking->row();
        $template->orders = $orderData;

        // Parse confirmation text with insert tags
        $parser = System::getContainer()->get('contao.insert_tag.parser');
        $template->confirmation_text = $parser->replace($model->confirmation_text ?: '');

        // Basic template data
        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';
        $template->type = $model->type;

        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        }

        return $template->getResponse();
    }
}
