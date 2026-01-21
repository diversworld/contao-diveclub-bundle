<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\Backend;

use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcConfigModel;
use Diversworld\ContaoDiveclubBundle\Service\TuvListGenerator;
use Contao\FilesModel;
use Contao\System;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contao/dc_tuv_list_export/{id}', name: 'dc_tuv_list_export', defaults: ['_scope' => 'backend', '_token_check' => true])]
class TuvListExportController
{
    public function __construct(private readonly TuvListGenerator $generator)
    {
    }

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $proposal = DcCheckProposalModel::findByPk($id);
            if (null === $proposal) {
                throw new RuntimeException('Proposal not found');
            }

            // Get format from config
            $config = DcConfigModel::findOneBy(['published=?'], [1]);
            $format = $config?->tuvListFormat ?: 'pdf';

            switch ($format) {
                case 'csv':
                    $content = $this->generator->generateCsv((int)$id);
                    $contentType = 'text/csv';
                    $extension = 'csv';
                    break;
                case 'xlsx':
                    $content = $this->generator->generateXlsx((int)$id);
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    $extension = 'xlsx';
                    break;
                case 'pdf':
                default:
                    $content = $this->generator->generatePdf((int)$id);
                    $contentType = 'application/pdf';
                    $extension = 'pdf';
                    break;
            }

            $filename = 'TUV-Liste-' . ($proposal->title ?: $id) . '.' . $extension;

            // Save the file if a folder is configured
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');
            $savePath = $projectDir . '/files';

            if ($config?->tuvListFolder) {
                $folderModel = FilesModel::findByPk($config->tuvListFolder);
                if ($folderModel && is_dir($projectDir . '/' . $folderModel->path)) {
                    $savePath = $projectDir . '/' . $folderModel->path;
                }
            }

            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }

            file_put_contents($savePath . '/' . $filename, $content);

            return new Response($content, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        } catch (RuntimeException $e) {
            return new Response($e->getMessage(), 404);
        }
    }
}
