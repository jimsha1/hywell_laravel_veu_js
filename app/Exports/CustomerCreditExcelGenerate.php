<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerCreditExcelGenerate implements
    FromCollection,
    WithHeadings,
    WithEvents,
    ShouldAutoSize,
    WithCustomStartCell
{
    protected $customer_name;
    protected $customer_code;
    protected $employee_id;
    protected $employee_type;

    public function __construct($customer_name, $customer_code, $employee_id = null, $employee_type = '')
    {
        $this->customer_name = $customer_name;
        $this->customer_code = $customer_code;
        $this->employee_id   = $employee_id;
        $this->employee_type = $employee_type;
    }

    /* ---------------------------------
     | HEADINGS
     |---------------------------------*/
    public function headings(): array
    {
        return [
            'SI.No',
            'Customer Code',
            'Customer Name',
            'Phone',
            'Credit Balance',
        ];
    }

    /* ---------------------------------
     | START CELL
     |---------------------------------*/
    public function startCell(): string
    {
        return 'A3';
    }

    /* ---------------------------------
     | DATA COLLECTION
     |---------------------------------*/
    public function collection(): Collection
    {
        $query = app(\App\Services\ReportService::class)->customerCreditReportQuery();

        if (!empty($this->customer_name)) {
            $query->where('customer_name', 'LIKE', "%{$this->customer_name}%");
        }

        if (!empty($this->customer_code)) {
            $query->where('customer_code', 'LIKE', "%{$this->customer_code}%");
        }

        if (!empty($this->employee_id)) {
            $query->where('employee_id', $this->employee_id);
        }

        if ($this->employee_type === 'company') {
            $query->where('is_outside', 0);
        } elseif ($this->employee_type === 'outside') {
            $query->where('is_outside', 1);
        }

        // Only customers with credit
        $query->where('balance_amount', '>', 0);

        $results = $query->orderBy('balance_amount', 'DESC')->get();

        $data = [];

        foreach ($results as $key => $result) {
            $data[] = [
                'SI.No'          => $key + 1,
                'Customer Code'  => $result->customer_code ?? '-',
                'Customer Name'  => $result->customer_name ?? '-',
                'Phone'          => $result->phone_number ?? '-',
                'Credit Balance' => (float) ($result->balance_amount ?? 0),
            ];
        }

        return collect($data);
    }

    /* ---------------------------------
     | EVENTS & STYLES
     |---------------------------------*/
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                /* ===== Main Heading ===== */
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'Customer Credit Report');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                /* ===== Table Headings ===== */
                $sheet->getStyle('A3:E3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                /* ===== Data Rows ===== */
                if ($highestRow >= 4) {
                    $sheet->getStyle('A4:D' . $highestRow)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                        ],
                        'font' => [
                            'size' => 11,
                        ],
                    ]);

                    // Credit column alignment
                    $sheet->getStyle('E4:E' . $highestRow)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                    ]);

                    // NUMBER FORMAT (IMPORTANT)
                    $sheet->getStyle('E4:E' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                /* ===== Total Row ===== */
                if ($highestRow > 3) {
                    $totalRow = $highestRow + 1;

                    $sheet->setCellValue('D' . $totalRow, 'Total:');
                    $sheet->setCellValue('E' . $totalRow, '=SUM(E4:E' . $highestRow . ')');

                    $sheet->getStyle('D' . $totalRow . ':E' . $totalRow)->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                    ]);

                    // Format total cell
                    $sheet->getStyle('E' . $totalRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
