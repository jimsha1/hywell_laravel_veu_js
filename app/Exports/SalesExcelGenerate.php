<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Models\Products;
use App\Models\Order;
use App\Models\CommissionHistory;
use App\Models\VehicleLoadStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class SalesExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $invoice_no;
    protected $customer_name;
    private $start_date;
    private $end_date;
    protected $payment_mode;
    protected $payment_status;
    protected $delivery_status;
    protected $filter_order_by;
    protected $filter_delivery_by;
    protected $customer_type;


    public function __construct($invoice_no, $start_date, $end_date, $customer_name, $payment_mode, $payment_status, $delivery_status = null, $filter_order_by, $filter_delivery_by, $customer_type)
    {
        $this->invoice_no = $invoice_no;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->customer_name = $customer_name;
        $this->payment_mode = $payment_mode;
        $this->payment_status = $payment_status;
        $this->delivery_status = $delivery_status;
        $this->filter_order_by = $filter_order_by;
        $this->filter_delivery_by = $filter_delivery_by;
        $this->customer_type = $customer_type;
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Date',
            'Invoice No',
            "Order By",
            'Seller',
            'Customer Name',
            'Payment Mode',
            'Total Quantity',
            'Free Quantity',
            'Total Amount',
            'Paid Amount',
            'Refund Amount',
            'Refund Order Balance',
            'Balance Amount',
            'Payment Status',
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
        $query = Order::with('orderItems.product', 'customer', 'orderBy', 'deliveryBy', 'payments')
            ->where('order_status', 'delivered');

        $customerType = strtolower((string) $this->customer_type);
        $query->where('is_outside', in_array($customerType, ['in', 'company'], true) ? 0 : 1);
        // Filter by delivery status
        if (!empty($this->delivery_status)) {
            $query->where('order_status', $this->delivery_status);
        }

        if ($this->start_date && $this->end_date) {
            // For delivered orders, filter by delivered_date
            // For requested/rejected orders, filter by created_at or order_date
            if (!empty($this->delivery_status) && $this->delivery_status == 'delivered') {
                $query->whereBetween('delivered_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
            } else {
                $query->where(function ($q) {
                    $q->whereBetween('order_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59'])
                        ->orWhere(function ($subQ) {
                            $subQ->whereNull('order_date')
                                ->whereBetween('created_at', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
                        });
                });
            }
        }

        if (!empty($this->customer_name)) {
            $search = $this->customer_name;

            $query->where(function ($query1) use ($search) {

                $query1->where(function ($q) use ($search) {
                    $q->where('order_type', '!=', 'Outside Sale')
                        ->whereHas('customer', function ($customer) use ($search) {
                            $customer->where('customer_name', 'LIKE', "%{$search}%");
                        });
                });

                $query1->orWhere(function ($q) use ($search) {
                    $q->where('order_type', 'Outside Sale')
                        ->whereHas('users', function ($user) use ($search) {
                            $user->where('name', 'LIKE', "%{$search}%");
                        });
                });
            });
        }

        if (!empty($this->invoice_no)) {
            $query->where('order_no', $this->invoice_no);
        }

        if (!empty($this->payment_status)) {
            $query->where('payment_status', $this->payment_status);
        }

        if (!empty($this->payment_mode)) {
            $query->whereHas('payments', function ($q) {
                $q->where('payment_mode', $this->payment_mode);
            });
        }

        // if (!empty($this->filter_order_by)) {
        //     $query->where(function ($q) {
        //         $q->where('order_by', $this->filter_order_by)
        //             ->orWhere('delivered_by', $this->filter_order_by);
        //     });
        // }

        if (!empty($this->filter_order_by)) {
            $query->where('order_by', $this->filter_order_by);
        }
        if (!empty($this->filter_delivery_by)) {
            $query->where('delivered_by', $this->filter_delivery_by);
        }

        $results = $query->orderBy('created_at', 'DESC')->get();

        $data = [];

        foreach ($results as $key => $result) {
            $paymentModes = $result->payments->pluck('payment_mode')->implode(', ');

            $paidAmount = $result->paid_amount ?? 0;

            if ($result->order_type == 'Outside Sale') {

                $display_name = [
                    'id'   => optional($result->orderBy)->id,
                    'name' => optional($result->orderBy)->name,
                    'code' => 'UID-' . optional($result->orderBy)->id,
                    'type' => 'employee',
                ];
            } else {
                $display_name = [
                    'id'   => optional($result->customer)->id,
                    'name' => optional($result->customer)->customer_name,
                    'code' => optional($result->customer)->customer_code,
                    'type' => 'customer',
                ];
            }
            $data[] = [
                'SI.No'            => $key + 1,
                'Date' => $result->delivered_date
                    ? \Carbon\Carbon::parse($result->delivered_date)->format('Y-m-d')
                    : '',

                // 'Invoice No'       => $result->order_no ?? '-',
                'Invoice No' => ($result->order_no ?? '-') . ($result->is_returned == 1 ? ' (Returned)' : ''),
                'Order By'           => $result->orderBy->name ?? '-',
                'Seller'           => $result->deliveryBy->name ?? '-',
                'Customer Name'    => $display_name['name'] ?? '-',
                'Payment Mode'     => $paymentModes,
                'Total Quantity'   => $result->total_quantity ?? 0,
                'Free Quantity'    => $result->orderItems->sum('free_quantity'),
                'Total Amount'     => number_format($result->total_amount ?? 0, 2),
                'Paid Amount'      => number_format($paidAmount, 2),
                'Refund Amount'     => number_format($result->total_refunded_amount ?? 0, 2),
                'Refund Order Balance' => number_format($result->total_refund_from_order_balance ?? 0, 2),
                'Balance Amount'   => number_format($result->balance_amount ?? 0, 2),
                'Payment Status'   => $result->payment_status ?? 'N/A',
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
                $sheet->getStyle('G4:G' . $highestRow)
                    ->getAlignment()->setWrapText(true);

                // Merge A1:F1 for main heading
                $sheet->mergeCells('A1:O1');
                $sheet->setCellValue('A1', 'Sales Report');

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
                $sheet->getStyle('A3:O3')->applyFromArray([
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
                $sheet->getStyle('A4:O' . $highestRow)->applyFromArray([
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
