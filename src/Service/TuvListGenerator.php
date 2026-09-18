<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use RuntimeException;
use setasign\Fpdi\TcpdfFpdi as Fpdi;

class TuvListGenerator
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function generateAll(array $filters, array $search, array $sorting, string $format): array
    {
        $this->framework->initialize();

        $proposals = $this->loadFilteredProposals($filters, $search, $sorting);

        $rows = [];

        foreach ($proposals as $proposal) {
            $rows = array_merge($rows, $this->getOrdersData($proposal->id));
        }

        return match ($format) {
            'csv' => [
                'content' => $this->generateCsvFromRows($rows),
                'extension' => 'csv',
                'contentType' => 'text/csv'
            ],
            'xlsx' => [
                'content' => $this->generateXlsxFromRows($rows),
                'extension' => 'xlsx',
                'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            default => [
                'content' => $this->generatePdfFromRows($rows),
                'extension' => 'pdf',
                'contentType' => 'application/pdf'
            ]
        };
    }

    private function loadFilteredProposals(array $filters, array $search, array $sorting)
    {
        $allowedFields = ['title', 'alias', 'vendorName', 'checkId', 'published']; // 🔒 whitelist

        $conditions = [];
        $values = [];

        // 🔥 FILTER (safe)
        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if (!in_array($field, $allowedFields, true)) {
                continue; // 🔒 block unknown fields
            }

            $conditions[] = "$field = ?";
            $values[] = $value;
        }

        // 🔥 SEARCH (safe LIKE)
        foreach ($search as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if (!in_array($field, $allowedFields, true)) {
                continue;
            }

            $conditions[] = "$field LIKE ?";
            $values[] = '%' . $value . '%';
        }

        $sql = "SELECT * FROM tl_dc_check_proposal WHERE 1=1";

        if (!empty($conditions)) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        if (!empty($sorting['field']) && in_array($sorting['field'], $allowedFields, true)) {
            $direction = strtoupper($sorting['direction'] ?? 'ASC');

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            $sql .= ' ORDER BY ' . $sorting['field'] . ' ' . $direction;
        }

        $result = Database::getInstance()
            ->prepare($sql)
            ->execute(...$values);

        // 🔥 convert Result -> Models (Contao standard)
        $collection = [];

        while ($result->next()) {
            $collection[] = DcCheckProposalModel::findByPk($result->id);
        }

        return array_filter($collection);
    }

    private function getOrdersData(int $proposalId): array
    {
        $proposal = DcCheckProposalModel::findByPk($proposalId);

        if (!$proposal) {
            return [];
        }

        $bookings = DcCheckBookingModel::findBy('pid', $proposalId);

        $data = [];

        if ($bookings) {
            foreach ($bookings as $booking) {
                $orders = DcCheckOrderModel::findBy('pid', $booking->id);

                if ($orders) {
                    foreach ($orders as $order) {
                        $data[] = [
                            'member_name' => $booking->firstname . ' ' . $booking->lastname,
                            'serialNumber' => (string)$order->serialNumber,
                            'size' => $order->size . ' L',
                            'manufacturer' => (string)$order->manufacturer,
                            'o2clean' => $order->o2clean ? 'Ja' : 'Nein'
                        ];
                    }
                }
            }
        }

        return $data;
    }

    private function generatePdfFromRows(array $rows): string
    {
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        foreach ($rows as $row) {
            $pdf->Cell(0, 6, implode(' | ', $row), 0, 1);
        }

        return $pdf->Output('', 'S');
    }

    private function generateCsvFromRows(array $rows): string
    {
        $fp = fopen('php://temp', 'r+');

        fputcsv($fp, ['Kunde', 'Seriennummer', 'Größe', 'Hersteller', 'O2-Clean'], ';');

        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }

        rewind($fp);
        return stream_get_contents($fp);
    }

    private function generateXlsxFromRows(array $rows): string
    {
        return $this->generateCsvFromRows($rows);
    }
}
