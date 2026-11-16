<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Models\Products;
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

class OpenCloseExcelGenerate implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithCustomStartCell
{
    protected $vehicle_id;
    protected $stock_code;
    private $start_date;
    private $end_date;


    public function __construct($vehicle_id, $start_date, $end_date, $stock_code)
    {
        $this->vehicle_id = $vehicle_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->stock_code = $stock_code;
    }

    public function headings(): array
    {
        return [
            'SI.No',
        'Open Date',
        'Close Date',
        'Stock Code',
        'Vehicle',
        'Stock By',
        'Total Load',
        'Balance Load',
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

        $result = VehicleLoadStock::with('vehicleLoadStockItems.product', 'vehicle', 'user')
            ->where('load_status',  'approved');

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

        if (!empty($this->stock_code)) {
            $result->where('v_stock_code', 'like', '%' . $this->stock_code . '%');
        }

        if (!empty($this->vehicle_id)) {
            $result->where('vehicle_id', $this->vehicle_id);
        }
        // $VehicleName = '';
        //    if (!empty($data->vehicle_id)) {
        //     $vehicle = Vehicle::find($data->vehicle_id);
        //     $VehicleName = $vehicle ? $vehicle->vehicle_name : 'N/A';
        //    }


        $results = $result->orderBy('created_at', 'DESC')->get();
        // dd($results->first()->toArray());


        $data = [];
        // @dd($results);
        foreach ($results as $key => $result) {

            $data[] = [
                'SI.No'          => $key + 1,
                'Open Date'      => optional($result->created_at)->format('Y-m-d'),
                'Close Date'     => $result->closing_req_date
                                    ? \Carbon\Carbon::parse($result->closing_req_date)->format('Y-m-d')
                                    : '',
                'Stock Code'     => $result->v_stock_code,
                'Vehicle'        => $result->vehicle->vehicle_name ?? '-',
                'Stock By'       => $result->user->name ?? '-',
                'Total Load'     => $result->vehicleLoadStockItems
                                        ? $result->vehicleLoadStockItems->sum(fn($item) => (int)($item->total_qty ?? 0))
                                        : 0,
                'Balance Load'   => $result->vehicleLoadStockItems
                                        ? $result->vehicleLoadStockItems->sum(fn($item) => (int)($item->available_qty ?? 0))
                                        : 0,
                'Status'         => $result->day_status,
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
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'Open Close Report');

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
                $sheet->getStyle('A3:I3')->applyFromArray([
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
                $sheet->getStyle('A4:I' . $highestRow)->applyFromArray([
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
