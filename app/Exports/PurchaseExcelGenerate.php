<?php

namespace App\Exports;

use App\Models\Purchase;
use App\Models\Products;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class PurchaseExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $purchase_no;
    protected $supplier_id;
    protected $start_date;
    protected $end_date;
    protected $product_id;
    protected $payment_mode;

    public function __construct($supplier_id, $start_date, $end_date, $purchase_no, $product_id, $payment_mode)
    {
        $this->supplier_id = $supplier_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->purchase_no = $purchase_no;
        $this->product_id = $product_id;
        $this->payment_mode = $payment_mode;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Purchase No',
            'Purchase Date',
            'Supplier Name',
            'Payment Mode(s)',
            'Purchase Amount',
            'Paid Amount',
            'Refund Amount',
            'Refund Order Balance',
            'Balance Amount',
        ];
    }

    public function collection(): Collection
    {
        $query = Purchase::with(['supplier', 'purchaseItem.product', 'purchasePayment']);

        // Filters
        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->whereBetween('purchase_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        } elseif (!empty($this->start_date)) {
            $query->where('purchase_date', '>=', $this->start_date . ' 00:00:00');
        } elseif (!empty($this->end_date)) {
            $query->where('purchase_date', '<=', $this->end_date . ' 23:59:59');
        }

        if (!empty($this->purchase_no)) {
            $query->where('purchase_no', 'like', '%' . $this->purchase_no . '%');
        }

        if (!empty($this->supplier_id)) {
            $query->where('supplier_id', $this->supplier_id);
        }

        if (!empty($this->product_id)) {
            $query->whereHas('purchaseItem.product', function ($q) {
                $q->where('id', $this->product_id);
            });
        }

        if (!empty($this->payment_mode)) {
            $query->whereHas('purchasePayment', function ($q) {
                $q->where('payment_mode', $this->payment_mode);
            });
        }

        $results = $query->orderBy('created_at', 'DESC')->get();

        $data = [];

        foreach ($results as $index => $purchase) {
            // Format multiple payment modes
            $paymentModes = '-';
            if ($purchase->purchasePayment && $purchase->purchasePayment->count()) {
                $paymentModes = $purchase->purchasePayment->map(function ($payment) {
                    return $payment->payment_mode . ' (' . number_format($payment->amount_taken, 2) . ')';
                })->implode(', ');
            }


             $data[] = [
                'SI.No'            => $index + 1,
                'Purchase No'      => ($purchase->purchase_no ?? '-') . ($purchase->is_returned == 1 ? ' (Returned)' : ''),
                'Purchase Date'    => optional($purchase->created_at)->format('Y-m-d'),
                'Supplier Name'    => $purchase->supplier->supplier_name  ?? '-',
                'Payment Mode'     => $paymentModes,
                'Purchase Amount'  => number_format($purchase->total, 2),
                'Paid Amount'          => number_format($purchase->payment_total ?? 0, 2),
                'Refund Amount'        => number_format($purchase->total_refund_amount ?? 0, 2),
                'Refund Order Balance' => number_format($purchase->refund_amount_from_order_balance ?? 0, 2),
                'Balance Amount'       => number_format($purchase->balance_amount ?? 0, 2),

            ];
        }


        return collect($data);
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Wrap text in column C
                $sheet->getStyle('E4:E' . $highestRow)
                    ->getAlignment()->setWrapText(true);

                // Merge A1:F1 for main heading
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'Purchase Report');

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
                $sheet->getStyle('A1')->applyFromArray([
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
                $sheet->getStyle('A4:J' . $highestRow)->applyFromArray([
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
