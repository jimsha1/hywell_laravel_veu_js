<?php

namespace App\Exports;

use App\Models\OrderItem;
use App\Models\Products;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProductWiseSalesExcelGenerate implements
    FromCollection,
    WithHeadings,
    WithEvents,
    ShouldAutoSize,
    WithCustomStartCell
{
    protected $customer_id;
    protected $delivered_by;
    protected $order_by;
    protected $product_id;
    protected $start_date;
    protected $end_date;
    protected $is_outside;

    public function __construct(
        $product_id,
        $start_date,
        $end_date,
        $customer_id = null,
        $delivered_by = null,
        $order_by = null,
        $is_outside = 0
    ) {
        $this->product_id = $product_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->customer_id = $customer_id;
        $this->delivered_by = $delivered_by;
        $this->order_by = $order_by;
        $this->is_outside = $is_outside;
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Product Code',
            'Product Name',

            'Total Quantity',
            'Free Quantity',
            'Returned Quantity',


        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        | Keep this consistent with the web listing.
        */

        $query = OrderItem::select(
            'order_items.product_id',

            DB::raw('MAX(products.product_code) as product_code'),
            DB::raw('MAX(products.product_name) as product_name'),
            DB::raw('MAX(products.unit_price) as unit_price'),
            DB::raw('MAX(products.selling_price) as selling_price'),
            DB::raw('MAX(product_categories.category_name) as category'),

            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.free_quantity) as total_free_quantity'),
            DB::raw('SUM(order_items.returned_quantity) as total_returned_quantity'),
            DB::raw('SUM(order_items.total) as total_amount'),

            DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),

            DB::raw('
                SUM(order_items.total) /
                NULLIF(SUM(order_items.quantity), 0)
                as avg_price
            ')
        )
            ->join(
                'orders',
                'order_items.order_id',
                '=',
                'orders.id'
            )

            ->leftJoin(
                'products',
                'order_items.product_id',
                '=',
                'products.id'
            )

            ->leftJoin(
                'product_categories',
                'products.category_id',
                '=',
                'product_categories.id'
            )

            /*
            |--------------------------------------------------------------------------
            | Same status filter as WEB listing
            |--------------------------------------------------------------------------
            */
            ->where(
                'orders.order_status',
                'delivered'
            )

            /*
            |--------------------------------------------------------------------------
            | Same Inside / Outside filter as WEB listing
            |--------------------------------------------------------------------------
            */
            ->where(
                'orders.is_outside',
                $this->is_outside
            )

            /*
            |--------------------------------------------------------------------------
            | Same date filter as WEB listing
            |--------------------------------------------------------------------------
            | Web listing uses delivered_date.
            */
            ->whereBetween(
                'orders.delivered_date',
                [
                    $this->start_date . ' 00:00:00',
                    $this->end_date . ' 23:59:59'
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Product Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($this->product_id)) {
            $query->where(
                'order_items.product_id',
                $this->product_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($this->customer_id)) {
            $query->where(
                'orders.customer_id',
                $this->customer_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delivery Boy Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($this->delivered_by)) {
            $query->where(
                'orders.delivered_by',
                $this->delivered_by
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        switch ($this->order_by) {






            default:

                $query->orderBy(
                    'product_code',
                    'asc'
                );

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Group By
        |--------------------------------------------------------------------------
        */

        $query->groupBy(
            'order_items.product_id',
            'products.id',
            'product_categories.id'
        );

        /*
        |--------------------------------------------------------------------------
        | Get Results
        |--------------------------------------------------------------------------
        */

        $results = $query
            ->orderBy('product_code')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Prepare Excel Data
        |--------------------------------------------------------------------------
        */

        $data = [];

        foreach ($results as $key => $item) {

            $product = Products::with('category')
                ->find($item->product_id);

            $quantity = (int) $item->total_quantity;
            $returnedQuantity = (int) $item->total_returned_quantity;

            $data[] = [

                'SI.No' => $key + 1,

                'Product Code' =>
                $product->product_code ?? 'N/A',

                'Product Name' =>
                $product->product_name ?? 'N/A',



                // 'Total Quantity' =>
                //     $quantity,

                // 'Free Quantity' =>
                //     (int) $item->total_free_quantity,

                // 'Returned Quantity' =>
                //     $returnedQuantity,

                'Quantity' => $quantity ?? 0,

                'Free Quantity' => (int) ($item->total_free_quantity ?? 0),

                'Returned Quantity' => $returnedQuantity ?? 0,
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

                /*
                |--------------------------------------------------------------------------
                | Main Heading
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells('A1:M1');

                $sheet->setCellValue(
                    'A1',
                    'Product Wise Sales Report'
                );

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => [
                            'rgb' => '000000'
                        ],
                    ],

                    'alignment' => [
                        'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,
                        'vertical' =>
                        Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Header Style
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A3:M3')->applyFromArray([

                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => [
                            'rgb' => '000000'
                        ],
                    ],

                    'alignment' => [
                        'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

                        'vertical' =>
                        Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Data Style
                |--------------------------------------------------------------------------
                */

                if ($highestRow >= 4) {

                    $sheet
                        ->getStyle(
                            'A4:M' . $highestRow
                        )
                        ->applyFromArray([

                            'alignment' => [
                                'horizontal' =>
                                Alignment::HORIZONTAL_LEFT,

                                'vertical' =>
                                Alignment::VERTICAL_CENTER,
                            ],

                            'font' => [
                                'size' => 11,
                                'bold' => false,
                            ],
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Number Formatting
                |--------------------------------------------------------------------------
                */

                if ($highestRow >= 4) {

                    $sheet
                        ->getStyle(
                            'E4:F' . $highestRow
                        )
                        ->getNumberFormat()
                        ->setFormatCode('0.00');

                    $sheet
                        ->getStyle(
                            'K4:L' . $highestRow
                        )
                        ->getNumberFormat()
                        ->setFormatCode('0.00');
                }
            },
        ];
    }
}
