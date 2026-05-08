<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\Backend;

use Contao\Input;
use Contao\System;
use Contao\BackendUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoDiveclubBundle\Model\DcConfigModel;
use Diversworld\ContaoDiveclubBundle\Service\TuvListGenerator;

#[Route('/contao/dc_tuv_list_export', name: 'dc_tuv_list_export', defaults: ['_scope' => 'backend', '_token_check' => true])]
class TuvListExportController
{
    public function __construct(
        private readonly TuvListGenerator $generator
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->disableCache();

            $user = BackendUser::getInstance();

            // 🔥 Contao Filter korrekt aus Input (WICHTIG!)
            $filters = Input::get('filter', true) ?? [];
            $search  = Input::get('search', true) ?? [];
            $sorting = [
                'field' => Input::get('sort'),
                'direction' => Input::get('order')
            ];

            // optional: User-Rechte berücksichtigen
            if (!$user->isAdmin) {
                // später: eingeschränkte Datensätze
            }

            $config = DcConfigModel::findOneBy(['published=?'], [1]);
            $format = $config?->tuvListFormat ?: 'pdf';

            $result = $this->generator->generateAll(
                $filters,
                $search,
                $sorting,
                $format
            );

            $filename = 'TUV-Liste-' . date('Y-m-d_H-i-s') . '.' . $result['extension'];

            return new Response(
                $result['content'],
                200,
                [
                    'Content-Type' => $result['contentType'],
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]
            );

        } catch (\Throwable $e) {
            return new Response('Export failed: ' . $e->getMessage(), 500);
        }
    }

    private function disableCache(): void
    {
        System::getContainer()->get('contao.framework')->initialize();
    }
}
