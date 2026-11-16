<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Models\Products;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class StockExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $product_id;
    protected $salesman_id;
    protected $stock_movement_mode;
    protected $customer_type;
    private $start_date;
    private $end_date;

    public function __construct($product_id, $salesman_id, $stock_movement_mode, $start_date, $end_date, $customer_type = 'company')
    {
        $this->product_id = $product_id;
        $this->salesman_id = $salesman_id;
        $this->stock_movement_mode = $stock_movement_mode;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->customer_type = $customer_type;
    }

    public function headings(): array
    {
        return [
            'SI.No',
            'Date',
            'Operation',
            'Product',
            'Inward',
            'Outward',
            'Remaining Qty',
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
        $query = StockMovement::with('product');

        // Date filters - filter by date field, fallback to created_at if date is null
        if ($this->start_date && $this->end_date) {
            $query->where(function($q) {
                $q->whereBetween('date', [
                    $this->start_date . ' 00:00:00',
                    $this->end_date . ' 23:59:59'
                ])->orWhere(function($subQ) {
                    $subQ->whereNull('date')
                         ->whereBetween('created_at', [
                             $this->start_date . ' 00:00:00',
                             $this->end_date . ' 23:59:59'
                         ]);
                });
            });
        } elseif ($this->start_date) {
            $query->where(function($q) {
                $q->where('date', '>=', $this->start_date . ' 00:00:00')
                  ->orWhere(function($subQ) {
                      $subQ->whereNull('date')
                           ->where('created_at', '>=', $this->start_date . ' 00:00:00');
                  });
            });
        } elseif ($this->end_date) {
            $query->where(function($q) {
                $q->where('date', '<=', $this->end_date . ' 23:59:59')
                  ->orWhere(function($subQ) {
                      $subQ->whereNull('date')
                           ->where('created_at', '<=', $this->end_date . ' 23:59:59');
                  });
            });
        }

        // Product filter
        if (!empty($this->product_id)) {
            $query->where('product_id', $this->product_id);
        }

        if(!empty($this->salesman_id)) {
            $query->where('created_by', $this->salesman_id);
        }

        if (!empty($this->stock_movement_mode)) {
                $query->where('reason', $this->stock_movement_mode);
            }

        $outsideStockReasons = [
            'vehicle opening stock for outsider',
            'vehicle opening re-stock for outsider',
        ];

        if (strtolower((string) $this->customer_type) === 'outside') {
            $query->whereIn('reason', $outsideStockReasons);
        } else {
            $query->where(function ($reasonQuery) use ($outsideStockReasons) {
                $reasonQuery->whereNull('reason')
                    ->orWhereNotIn('reason', $outsideStockReasons);
            });
        }


        $stocks = $query->orderBy('created_at', 'DESC')->get();
        $balances = [];

        $productIds = $stocks->pluck('product_id')->unique();

        foreach ($productIds as $productId) {

            $stock = 0;

            $movements = StockMovement::where('product_id', $productId)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($movements as $movement) {

                if ($movement->movement_type == 'in') {
                    $stock += $movement->quantity;
                } else {
                    $stock -= $movement->quantity;
                }

                $balances[$movement->id] = $stock;
            }
        }

        $data = [];

       foreach ($stocks as $key => $stock) {

             $dateValue = $stock->date
                 ? \Carbon\Carbon::parse($stock->date)->format('Y-m-d')
                 : $stock->created_at->format('Y-m-d');
         
             $data[] = [
                 'SI.No'         => $key + 1,
                 'Date'          => $dateValue,
                 'Operation'     => $stock->reason ?? '-',
                 'Product'       => $stock->product->product_name ?? '-',
                 'Inward'        => $stock->movement_type === 'in' ? floor($stock->quantity) : 0,
                 'Outward'       => $stock->movement_type === 'out' ? floor($stock->quantity) : 0,
                 'Remaining Qty' => floor($balances[$stock->id] ?? 0),
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

                // Merge A1:F1 for main heading
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'Stock Report');

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
                $sheet->getStyle('A3:F3')->applyFromArray([
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
                $sheet->getStyle('A4:F' . $highestRow)->applyFromArray([
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
