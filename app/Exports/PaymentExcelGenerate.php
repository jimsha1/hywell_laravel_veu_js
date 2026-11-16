<?php

namespace App\Exports;

use App\Models\OrderPayment;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

class PaymentExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    // protected $customer_id;
    protected $customer_search;
    protected $start_date;
    protected $end_date;
    protected $employee_id;
    protected $payment_type;
    protected $customer_type;

    public function __construct($customer_search, $start_date, $end_date, $employee_id = null, $payment_type = null, $customer_type = 'in')
    {
        // $this->customer_id = $customer_id;
        $this->customer_search = $customer_search;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->employee_id = $employee_id;
        $this->payment_type = $payment_type;
        $this->customer_type = $customer_type;
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
            'Order No',
            'Customer Code',
            'Customer Name',
            'Paid Amount',
            // 'Balance Amount',
            'Payment Mode',
            'Employee'
        ];
    }

    public function collection(): Collection
    {
        $query = OrderPayment::with(['customer', 'order', 'user']);

        if ($this->customer_type === 'in') {
            $query->whereHas('order', function ($orderQuery) {
                $orderQuery->where('is_outside', 0);
            });
        } else {
            $query->whereHas('order', function ($orderQuery) {
                $orderQuery->where('is_outside', 1);
            });
        }

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



        if (!empty($this->customer_search)) {

            $search = $this->customer_search;
            $query->where(function ($query1) use ($search) {

                // Normal customer
                $query1->where(function ($q) use ($search) {

                    $q->whereHas('order', function ($order) {
                        $order->where('order_type', '!=', 'Outside Sale');
                    });

                    $q->whereHas('customer', function ($customer) use ($search) {
                        $customer->where('customer_name', 'LIKE', '%' . $search . '%')
                            ->orWhere('customer_code', 'LIKE', '%' . $search . '%');
                    });
                })

                    // Outside salesman
                    ->orWhere(function ($q) use ($search) {

                        $q->whereHas('order', function ($order) use ($search) {

                            $order->where('order_type', 'Outside Sale')
                                ->whereHas('users', function ($user) use ($search) {
                                    $user->where('id', DB::raw('order_payments.customer_id'))
                                        ->where('name', 'LIKE', '%' . $search . '%');
                                });
                        });
                    });
            });
        }
        if (!empty($this->employee_id)) {
            $query->where('user_id', $this->employee_id);
        }

        // Filter by payment type
        if (!empty($this->payment_type)) {
            if ($this->payment_type === 'opening_balance') {
                // Opening balance payments only
                $query->where(function ($q) {
                    $q->where('type', 'opening_balance');
                });
            } elseif ($this->payment_type === 'previous_payments') {
                // Previous payments (order created before payment date)
                $query->where(function ($q) {
                    $q->whereNotNull('order_id')
                        ->whereNotNull('payment_date')
                        ->whereHas('order', function ($orderQ) {
                            $orderQ->whereRaw('DATE(orders.delivered_date) < DATE(order_payments.payment_date)');
                        });
                });
            } elseif ($this->payment_type === 'order_payments') {
                // Order payments (has order_id and not opening balance)
                $query->whereNotNull('order_id')
                    ->where(function ($q) {
                        $q->where('type', '!=', 'opening_balance')
                            ->orWhereNull('type');
                    });
            }
        }

        $results = $query->orderBy('created_at', 'DESC')->get();

        $data = [];
        $totalPaid = 0;
        // $totalBalance = 0;

        foreach ($results as $index => $payment) {
            $totalPaid += floatval($payment->paid_amount ?? 0);
            // $totalBalance += floatval($payment->balance_amount ?? 0);

            // Determine Order No or Opening Balance
            $orderNo = '-';
            if ($payment->order && $payment->order->order_no) {
                $orderNo = $payment->order->order_no;
            } elseif (!$payment->order_id || $payment->type === 'opening_balance') {
                $orderNo = 'Opening Balance';
            }

            $data[] = [
                'SI.No'             => $index + 1,
                'Payment Date'      => optional($payment->created_at)->format('d-m-Y'),
                'Order No'          => $orderNo,
                'Customer Code'     => $payment->order && $payment->order->display_customer ? $payment->order->display_customer['code'] : ($payment->customer
                    ? $payment->customer->customer_code : ($payment->order && $payment->order->customer ? $payment->order->customer->customer_code : '-')),

                'Customer Name'     => $payment->order && $payment->order->display_customer ? $payment->order->display_customer['name'] .
                    (($payment->order->display_customer['type'] ?? '') == 'employee' ? ' [Outside Salesman]' : '') : ($payment->customer ? $payment->customer->customer_name
                        : ($payment->order && $payment->order->customer ? $payment->order->customer->customer_name : '-')),
                'Paid Amount'       => number_format($payment->paid_amount ?? 0, 2),
                // 'Balance Amount'    => number_format($payment->balance_amount ?? 0, 2),
                'Payment Mode'      => $payment->payment_mode ?? '-',
                'Employee'          => $payment->user ? $payment->user->name : '-',
            ];
        }

        // Add total row
        $data[] = [
            'SI.No'            => '',
            'Payment Date'      => '',
            'Order No'          => '',
            'Customer Code'     => '',
            'Customer Name'     => '',
            'Paid Amount'       => number_format($totalPaid, 2),
            // 'Balance Amount'    => number_format($totalBalance, 2),
            'Payment Mode'      => 'TOTAL',
            'Employee'          => '',
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
                $sheet->setCellValue('A1', 'Payment Details Report');

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
