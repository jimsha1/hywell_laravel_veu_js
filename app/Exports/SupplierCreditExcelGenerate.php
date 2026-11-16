<?php

namespace App\Exports;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SupplierCreditExcelGenerate implements
    FromCollection,
    WithHeadings,
    WithEvents,
    ShouldAutoSize,
    WithCustomStartCell
{
    protected $supplier_name;
    protected $supplier_code;
   

    public function __construct($supplier_name, $supplier_code)
    {
        $this->supplier_name = $supplier_name;
        $this->supplier_code = $supplier_code;
    }

    /* ---------------------------------
     | HEADINGS
     |---------------------------------*/
    public function headings(): array
    {
        return [
            'SI.No',
            'Supplier Code',
            'Supplier Name',
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
        $query = Supplier::query();

        if (!empty($this->supplier_name)) {
            $query->where('supplier_name', 'LIKE', "%{$this->supplier_name}%");
        }

        if (!empty($this->supplier_code)) {
            $query->where('supplier_code', 'LIKE', "%{$this->supplier_code}%");
        }

       

        // Only suppliers with credit
        $query->where('balance', '>', 0);

        $results = $query->orderBy('balance', 'DESC')->get();

        $data = [];

        foreach ($results as $key => $result) {
            $data[] = [
                'SI.No'          => $key + 1,
                'Supplier Code'  => $result->supplier_code ?? '-',
                'Supplier Name'  => $result->supplier_name ?? '-',
                'Phone'          => $result->phone ?? '-',
                'Credit Balance' => (float) ($result->balance ?? 0),
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
                $sheet->setCellValue('A1', 'Supplier Credit Report');

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
