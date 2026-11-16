<?php

namespace App\Exports;

use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
//use PhpOffice\PhpSpreadsheet\Shared\Date;


class SupplierPaymentExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $supplier_id;
    protected $start_date;
    protected $end_date;

    public function __construct($supplier_id, $start_date, $end_date)
    {
        $this->supplier_id = $supplier_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Payment Date',
            'Purchase No',
            'Supplier Code',
            'Supplier Name',
            'Paid Amount',
            // 'Balance Amount',
            'Payment Mode',
        ];
    }

    public function collection(): Collection
    {
        $query = PurchasePayment::with(['purchase.supplier', 'purchase']);
        // dd($query);

        // Exclude credit payment mode
        $query->where(function ($q) {
            $q->where('payment_mode', '!=', 'credit')
                ->orWhereNull('payment_mode');
        });

        // Filters
        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->whereBetween('payment_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        } elseif (!empty($this->start_date)) {
            $query->where('payment_date', '>=', $this->start_date . ' 00:00:00');
        } elseif (!empty($this->end_date)) {
            $query->where('payment_date', '<=', $this->end_date . ' 23:59:59');
        }

        if (!empty($this->supplier_id)) {
            $query->whereHas('purchase', function ($q) {
                $q->where('supplier_id', $this->supplier_id);
            });
        }


        $results = $query->orderBy('created_at', 'DESC')->get();
        //return $results;
        $data = [];
        $totalPaid = 0;
        // $totalBalance = 0;

        foreach ($results as $index => $payment) {
            $totalPaid += floatval($payment->amount_taken ?? 0);

            // Calculate balance
            // $balance = 0;
            // if ($payment->purchase) {
            //     $total = floatval($payment->purchase->total ?? 0);
            //     $paid = floatval($payment->purchase->payment_total ?? 0);
            //     $balance = $total - $paid;
            // }
            // $totalBalance += $balance;

            // Determine Purchase No or Opening Balance
            $purchaseNo = '-';
            if ($payment->purchase && $payment->purchase->purchase_no) {
                $purchaseNo = $payment->purchase->purchase_no;
            } elseif (!$payment->purchase_id) {
                $purchaseNo = 'Opening Balance';
            }

            $data[] = [
                'SI.No'            => $index + 1,
                //payment_date
                'Payment Date'      => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y'): '-',
                //optional($payment->payment_date)->format('d-m-Y'),
                'Purchase No'        => $purchaseNo,
                'Supplier Code'     => $payment->purchase && $payment->purchase->supplier ? $payment->purchase->supplier->supplier_code : '-',
                'Supplier Name'     => $payment->purchase && $payment->purchase->supplier ? $payment->purchase->supplier->supplier_name : '-',
                'Paid Amount'       => number_format($payment->amount_taken ?? 0, 2),
                // 'Balance Amount'    => number_format($balance, 2),
                'Payment Mode'      => $payment->payment_mode ?? '-',
            ];
        }

        // Add total row
        $data[] = [
            'SI.No'            => '',
            'Payment Date'      => '',
            'Purchase No'        => '',
            'Supplier Code'     => '',
            'Supplier Name'     => '',
            'Paid Amount'       => number_format($totalPaid, 2),
            // 'Balance Amount'    => number_format($totalBalance, 2),
            'Payment Mode'      => 'TOTAL',
        ];

        return collect($data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Merge A1:H1 for main heading
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'Supplier Payment Details Report');

                // Style for main heading - matching PDF header color #2c3e50
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2C3E50'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Style for column headings - matching PDF table header color #34495e
                $sheet->getStyle('A3:' . $highestColumn . '3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '34495E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '2C3E50'],
                        ],
                    ],
                ]);

                // Set row height for column headers
                $sheet->getRowDimension(3)->setRowHeight(25);

                // Style for data rows - matching PDF border color
                $sheet->getStyle('A4:' . $highestColumn . ($highestRow - 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'size' => 11,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Style for total row - matching PDF footer color #f39c12
                $sheet->getStyle('A' . $highestRow . ':' . $highestColumn . $highestRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F39C12'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E67E22'],
                        ],
                    ],
                ]);

                // Center align specific columns
                $sheet->getStyle('A4:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C4:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F4:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G4:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
               

                // Add alternating row colors for better readability - matching PDF even row color #f8f9fa
                for ($row = 4; $row < $highestRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8F9FA'],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
