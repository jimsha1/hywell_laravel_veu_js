<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Models\Products;
use App\Models\CommissionHistory;
use App\Models\VehicleLoadStock;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class OrderExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $customer_name;
    protected $order_no;
    private $start_date;
    private $end_date;
    private $order_status;
    private $customer_type;


    public function __construct($customer_name, $start_date, $end_date, $order_no, $order_status = null, $customer_type = 'in')
    {
        $this->customer_name = $customer_name;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->order_no = $order_no;
        $this->order_status = $order_status;
        $this->customer_type = $customer_type;
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Date',
            'Order No ',
            'Order Type',
            'Delivery By',
            'Order By',
            'customer Name',
            'Total Quantity',
            'Free Quantity',
            'Total Amount',
            'Status',
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

        // $result = Order::with('orderItems.product', 'customer', 'orderBy')->where('order_status', '!=', 'draft');
        $result = Order::with('orderItems.product', 'customer', 'users', 'orderBy', 'deliveryBy')->where('order_status', '!=', 'draft');
        if ($this->customer_type == 'in') {
            $result->where('is_outside', 0);
        } else {
            $result->where('is_outside', 1);
        }


        $startDate = $this->start_date;
        $endDate = $this->end_date;
        if ($endDate != null && $startDate != null) {
            $result->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } else if ($endDate == null && $startDate != null) {
            $result->where('created_at', '>=', $startDate . ' 00:00:00');
        } else if ($endDate != null && $startDate == null) {
            $result->where('created_at', '<=', $endDate . ' 23:59:59');
        } else {
        }

        if (!empty($this->order_no)) {
            $result->where(function ($q) {
                $q->where('order_type', $this->order_no)
                    ->orWhere('order_no', 'LIKE', "%{$this->order_no}%")
                    ->orWhereHas('orderBy', function ($sub) {
                        $sub->where('name', 'LIKE', "%{$this->order_no}%");
                    });
            });
        }

        // if (!empty($data->customer_name)) {
        //     $result->whereHas('customer', function ($q)  {
        //         $q->where('customer_name', 'LIKE', "%{$data->customer_name}%");
        //     });
        // }
        // if (!empty($this->customer_name)) {
        //     $result->whereHas('customer', function ($q) {
        //         $q->where('customer_name', 'LIKE', "%{$this->customer_name}%");
        //     });
        // }

        if (!empty($this->customer_name)) {

            $searchname = $this->customer_name;

            $result->where(function ($query) use ($searchname) {

                $query->where(function ($q) use ($searchname) {
                    $q->where('order_type', '!=', 'Outside Sale')
                        ->whereHas('customer', function ($customer) use ($searchname) {
                            $customer->where('customer_name', 'LIKE', "%{$searchname}%");
                        });
                });

                $query->orWhere(function ($q) use ($searchname) {
                    $q->where('order_type', 'Outside Sale')
                        ->whereHas('users', function ($user) use ($searchname) {
                            $user->where('name', 'LIKE', "%{$searchname}%");
                        });
                });
            });
        }
        if (!empty($this->order_status)) {
            $result->where('order_status', $this->order_status);
        }

        $results = $result->orderBy('created_at', 'DESC')->get();

        $data = [];
        // @dd($results);
        foreach ($results as $key => $result) {
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
                'SI.No'        => $key + 1,
                'Date'         => optional($result->created_at)->format('Y-m-d'),
                'Order No'   =>  $result->order_no,
                'Order Type'      =>  $result->order_type ?? '-',
                'Order By'         => $result->orderBy->name ?? '-',
                'Delivery By'      => $result->deliveryBy->name ?? '-',
                'customer Name'   => $display_name['name'] ?? '-',
                'Total Quantity' => $result->total_quantity,
                'Free Quantity'    => $result->orderItems->sum('free_quantity'),
                'Total Amount'       => $result->total_amount,
                'Status'       => ucfirst($result->order_status ?? 'N/A'),
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
                $sheet->getStyle('C6:C' . $highestRow)
                    ->getAlignment()->setWrapText(true);

                // Merge A1:I1 for main heading
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'Order Report');

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
                $sheet->getStyle('A3:K3')->applyFromArray([
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
                $sheet->getStyle('A4:K' . $highestRow)->applyFromArray([
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
