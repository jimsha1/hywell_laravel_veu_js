<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Models\Products;
use App\Models\CommissionHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CommissionExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $product_id;
    protected $employee_name;
    protected $order_code;
    private $start_date;
    private $end_date;


    public function __construct($product_id, $start_date, $end_date, $employee_name, $order_code = null)
    {
        $this->product_id = $product_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->employee_name = $employee_name;
        $this->order_code = $order_code;
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Date',
            'Employee',
            'Order No',
            'Product',
            'Commission',

        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0000FF']]],
            5 => ['font' => ['bold' => true]],
        ];
    }

    public function collection(): Collection
    {

        $result = CommissionHistory::with('product', 'orderBy', 'order');

        $startDate = $this->start_date;
        $endDate = $this->end_date;
        if ($endDate != null && $startDate != null) {
            // Filter by date field, fallback to created_at if date is null
            $result->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('date')
                            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                    });
            });
        } else if ($endDate == null && $startDate != null) {
            $result->where(function ($query) use ($startDate) {
                $query->where(function ($q) use ($startDate) {
                    $q->whereNotNull('date')
                        ->where('date', '>=', $startDate . ' 00:00:00');
                })->orWhere(function ($q) use ($startDate) {
                    $q->whereNull('date')
                        ->where('created_at', '>=', $startDate . ' 00:00:00');
                });
            });
        } else if ($endDate != null && $startDate == null) {
            $result->where(function ($query) use ($endDate) {
                $query->where(function ($q) use ($endDate) {
                    $q->whereNotNull('date')
                        ->where('date', '<=', $endDate . ' 23:59:59');
                })->orWhere(function ($q) use ($endDate) {
                    $q->whereNull('date')
                        ->where('created_at', '<=', $endDate . ' 23:59:59');
                });
            });
        } else {
        }

        if (!empty($this->product_id)) {
            $result->where('product_id', $this->product_id);
        }

        if (!empty($this->employee_name)) {
            $result->whereHas('orderBy', function ($query) {
                $query->where('name', 'like', '%' . $this->employee_name . '%');
            });
        }

        if (!empty($this->order_code)) {
            $result->whereHas('order', function ($query) {
                $query->where('order_no', 'like', '%' . $this->order_code . '%');
            });
        }

        //   @dd($this->product_id);
        $results = $result->orderBy('date', 'DESC')->orderBy('created_at', 'DESC')->get();

        $data = [];

        foreach ($results as $key => $result) {

            $data[] = [
                'SI.No'      => $key + 1,
                'Date'      => optional($result->created_at)->format('Y-m-d'),
                'Employee'   => optional($result->orderBy)->name ?? '-',
                'Order No'   => optional($result->order)->order_no ?? '-',
                'Product'    => optional($result->product)->product_name . ' [' . optional($result->product)->product_code . ']',
                'Commission' => $result->commission_amount,
            ];
        }
        // dd($data);

        return collect($data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Wrap text in column C
                $sheet->getStyle('C6:C' . $highestRow)
                    ->getAlignment()->setWrapText(true);

                // Merge A1:F1 for main heading
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'Commission Report');

                // Style for main heading
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Style for headings
                $sheet->getStyle('A3:E3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Style for data rows
                $sheet->getStyle('A4:E' . $highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    ],
                    'font' => [
                        'size' => 11,
                        'bold' => false,
                    ],
                ]);
            },
        ];
    }
}
