<?php

namespace App\Exports;

use App\Models\OpeningBalance;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerDueExcelGenerate implements
    FromCollection,
    WithHeadings,
    WithEvents,
    ShouldAutoSize,
    WithCustomStartCell
{
    protected $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /* ---------------------------------
     | HEADINGS
     |---------------------------------*/
    public function headings(): array
    {
        return [
            'SI.No',

            'Customer Name',
            'Date',
            'Balance Amount',
            'Due(in days)'
        ];
    }

    /* ---------------------------------
     | START CELL
     |---------------------------------*/
    // public function startCell(): string
    // {
    //     return 'A3';
    // }

public function startCell(): string
{
    $data = $this->data;

    // Row 1 = main heading
    // Filters start from row 2
    $headingRow = 2;

    if (!empty($data->customer_name)) {
        $headingRow++;
    }

    if (!empty($data->start_date)) {
        $headingRow++;
    }

    if (!empty($data->end_date)) {
        $headingRow++;
    }

    if (!empty($data->employee_id)) {
        $headingRow++;
    }

    if (!empty($data->due_time)) {
        $headingRow++;
    }

    return 'A' . $headingRow;
}
    /* ---------------------------------
     | DATA COLLECTION
     |---------------------------------*/
    public function collection(): Collection
    {
        $data = $this->data;
        $orderQuery = Order::with(['customer', 'payments'])
            ->where('order_status', 'delivered')
            ->where('payment_status', 'Partial')
            ->where('is_outside', 0);

        // $orderQuery = $this->applyFiltersForSalesReport($orderQuery, $data);
        $orderQuery = app(\App\Services\ReportService::class)->applyFiltersForSalesReport($orderQuery, $data);


        $openingBalanceQuery = OpeningBalance::with('customer')
            ->whereRaw('(amount - COALESCE(paid_amount, 0)) > 0');;
        if (!empty($data->customer_name)) {

            $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                $q->where('customer_name', 'LIKE', "%{$data->customer_name}%");
            });
        }

        if (!empty($data->employee_id)) {

            $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                $q->where('employee_id', $data->employee_id);
            });
        }

        if (!empty($data->customer_code)) {

            $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                $q->where('customer_code', 'LIKE', "%{$data->customer_code}%");
            });
        }



        $orders = $orderQuery->get()->map(function ($order) {

            $order->record_type = 'order';
            $order->due_date = $order->delivered_date;

            return $order;
        });

        $openingBalances = $openingBalanceQuery->get()->map(function ($opening) {

            return (object)[
                'id' => 'OB-' . $opening->id,
                'record_type' => 'opening_balance',

                'order_no' => 'Opening Balance',

                'customer' => $opening->customer,

                'balance_amount' => $opening->amount - $opening->paid_amount,

                'delivered_date' => $opening->date,

                'due_date' => $opening->date,

                'payments' => collect(),

                'due_days' => abs(
                    Carbon::parse($opening->date)
                        ->startOfDay()
                        ->diffInDays(now()->startOfDay(), false)
                ),
            ];
        });




        $result = $orders
            ->concat($openingBalances)
            ->sortBy('due_date')
            ->values();

        $today = now()->startOfDay();

        if (!empty($data->due_time)) {

            switch ($data->due_time) {

                case 'ninty':
                    $result = $result->filter(function ($item) use ($today) {
                        return Carbon::parse($item->due_date)
                            ->startOfDay()
                            ->lte($today->copy()->subDays(90));
                    });
                    break;

                case 'sixty':
                    $result = $result->filter(function ($item) use ($today) {
                        $days = Carbon::parse($item->due_date)
                            ->startOfDay()
                            ->diffInDays($today);

                        return $days >= 60 && $days <= 89;
                    });
                    break;

                case 'thirty':
                    $result = $result->filter(function ($item) use ($today) {
                        $days = Carbon::parse($item->due_date)
                            ->startOfDay()
                            ->diffInDays($today);

                        return $days >= 30 && $days <= 59;
                    });
                    break;

                case 'thirty_less':
                    $result = $result->filter(function ($item) use ($today) {
                        $days = Carbon::parse($item->due_date)
                            ->startOfDay()
                            ->diffInDays($today);

                        return $days >= 0 && $days <= 29;
                    });
                    break;
            }
        }

        $result = $result->values();
        $result->transform(function ($item) {

            if (!isset($item->due_days)) {

                $item->due_days = (int) abs(Carbon::parse($item->delivered_date)
                    ->diffInDays(now()));
            }

            return $item;
        });
        $totalBalanceAmount = $result->sum('balance_amount');
        $excelRows = collect();

        $serialNo = 1;

        foreach ($result as $item) {

            $customerName = '-';

            if (
                isset($item->customer) &&
                $item->customer
            ) {
                $customerName =
                    $item->customer->customer_name ?? '-';
            }

            $date = $item->due_date
                ?? $item->delivered_date
                ?? null;

            $balanceAmount =
                (float) ($item->balance_amount ?? 0);

            $dueDays =
                (int) ($item->due_days ?? 0);

            $excelRows->push([
                $serialNo++,

                $customerName,

                $date
                    ? Carbon::parse($date)->format('d-m-Y')
                    : '-',

                $balanceAmount,

                $dueDays,
            ]);
        }

        return $excelRows;
    }

    /* ---------------------------------
     | EVENTS & STYLES
     |---------------------------------*/
    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {

    //             $sheet = $event->sheet->getDelegate();
    //             $highestRow = $sheet->getHighestRow();

    //             /* ===== Main Heading ===== */
    //             $sheet->mergeCells('A1:E1');
    //             $sheet->setCellValue('A1', 'Customer Due Report');

    //             $sheet->getStyle('A1')->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 16,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /* ===== Table Headings ===== */
    //             $sheet->getStyle('A3:E3')->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 12,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /* ===== Data Rows ===== */
    //             if ($highestRow >= 4) {
    //                 $sheet->getStyle('A4:E' . $highestRow)->applyFromArray([
    //                     'alignment' => [
    //                         'horizontal' => Alignment::HORIZONTAL_LEFT,
    //                     ],
    //                     'font' => [
    //                         'size' => 11,
    //                     ],
    //                 ]);

    //                 // Credit column alignment
    //                 $sheet->getStyle('E4:E' . $highestRow)->applyFromArray([
    //                     'alignment' => [
    //                         'horizontal' => Alignment::HORIZONTAL_RIGHT,
    //                     ],
    //                 ]);

    //                 // NUMBER FORMAT (IMPORTANT)
    //                 $sheet->getStyle('E4:E' . $highestRow)
    //                     ->getNumberFormat()
    //                     ->setFormatCode('#,##0.00');
    //             }

    //             /* ===== Total Row ===== */
    //             if ($highestRow > 3) {
    //                 $totalRow = $highestRow + 1;

    //                 $sheet->setCellValue('C' . $totalRow, 'Total:');
    //                 $sheet->setCellValue('D' . $totalRow, '=SUM(D4:D' . $highestRow . ')');

    //                 $sheet->getStyle('C' . $totalRow . ':D' . $totalRow)->applyFromArray([
    //                     'font' => [
    //                         'bold' => true,
    //                     ],
    //                     'alignment' => [
    //                         'horizontal' => Alignment::HORIZONTAL_RIGHT,
    //                     ],
    //                 ]);

    //                 // Format total cell
    //                 $sheet->getStyle('D' . $totalRow)
    //                     ->getNumberFormat()
    //                     ->setFormatCode('#,##0.00');
    //             }
    //         },
    //     ];
    // }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {

    //             $sheet = $event->sheet->getDelegate();

    //             $highestRow = $sheet->getHighestRow();

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Main Heading
    //         |--------------------------------------------------------------------------
    //         */

    //             $sheet->mergeCells('A1:E1');

    //             $sheet->setCellValue(
    //                 'A1',
    //                 'Customer Due Report'
    //             );

    //             $sheet->getStyle('A1')->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 16,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Table Headings - Row 3
    //         |--------------------------------------------------------------------------
    //         */

    //             $sheet->getStyle('A3:E3')->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 12,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Data Rows - Starting From Row 4
    //         |--------------------------------------------------------------------------
    //         */

    //             if ($highestRow >= 4) {

    //                 /*
    //             | SI.No
    //             */
    //                 $sheet->getStyle('A4:A' . $highestRow)
    //                     ->getAlignment()
    //                     ->setHorizontal(
    //                         Alignment::HORIZONTAL_CENTER
    //                     );

    //                 /*
    //             | Customer Name
    //             */
    //                 $sheet->getStyle('B4:B' . $highestRow)
    //                     ->getAlignment()
    //                     ->setHorizontal(
    //                         Alignment::HORIZONTAL_LEFT
    //                     );

    //                 /*
    //             | Date
    //             */
    //                 $sheet->getStyle('C4:C' . $highestRow)
    //                     ->getAlignment()
    //                     ->setHorizontal(
    //                         Alignment::HORIZONTAL_CENTER
    //                     );

    //                 /*
    //             | Balance Amount
    //             */
    //                 $sheet->getStyle('D4:D' . $highestRow)
    //                     ->getAlignment()
    //                     ->setHorizontal(
    //                         Alignment::HORIZONTAL_RIGHT
    //                     );

    //                 $sheet->getStyle('D4:D' . $highestRow)
    //                     ->getNumberFormat()
    //                     ->setFormatCode('#,##0.00');

    //                 /*
    //             | Due
    //             */
    //                 $sheet->getStyle('E4:E' . $highestRow)
    //                     ->getAlignment()
    //                     ->setHorizontal(
    //                         Alignment::HORIZONTAL_CENTER
    //                     );
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Total Row
    //         |--------------------------------------------------------------------------
    //         */

    //             if ($highestRow >= 4) {

    //                 $totalRow = $highestRow + 1;

    //                 /*
    //             | Put "Total:" in C
    //             */
    //                 $sheet->setCellValue(
    //                     'C' . $totalRow,
    //                     'Total:'
    //                 );

    //                 /*
    //             | Sum Balance Amount - Column D
    //             */
    //                 $sheet->setCellValue(
    //                     'D' . $totalRow,
    //                     '=SUM(D4:D' . $highestRow . ')'
    //                 );

    //                 /*
    //             | Style Total
    //             */
    //                 $sheet->getStyle(
    //                     'C' . $totalRow . ':D' . $totalRow
    //                 )->applyFromArray([
    //                     'font' => [
    //                         'bold' => true,
    //                     ],
    //                     'alignment' => [
    //                         'horizontal' =>
    //                         Alignment::HORIZONTAL_RIGHT,
    //                     ],
    //                 ]);

    //                 /*
    //             | Total number format
    //             */
    //                 $sheet->getStyle(
    //                     'D' . $totalRow
    //                 )
    //                     ->getNumberFormat()
    //                     ->setFormatCode('#,##0.00');
    //             }
    //         },
    //     ];
    // }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {

    //             $sheet = $event->sheet->getDelegate();

    //             $data = $this->data;

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Main Heading
    //         |--------------------------------------------------------------------------
    //         */

    //             $sheet->mergeCells('A1:E1');

    //             $sheet->setCellValue(
    //                 'A1',
    //                 'Customer Due Report'
    //             );

    //             $sheet->getStyle('A1')->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 16,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /*
    //         |--------------------------------------------------------------------------
    //         | FILTERS
    //         |--------------------------------------------------------------------------
    //         */

    //             $filterRow = 2;

    //             // Customer Name
    //             if (!empty($data->customer_name)) {

    //                 $sheet->setCellValue(
    //                     'A' . $filterRow,
    //                     'Customer Name:'
    //                 );

    //                 $sheet->mergeCells(
    //                     'B' . $filterRow . ':E' . $filterRow
    //                 );

    //                 $sheet->setCellValue(
    //                     'B' . $filterRow,
    //                     $data->customer_name
    //                 );

    //                 $filterRow++;
    //             }

    //             // Start Date
    //             if (!empty($data->start_date)) {

    //                 $sheet->setCellValue(
    //                     'A' . $filterRow,
    //                     'Start Date:'
    //                 );

    //                 $sheet->mergeCells(
    //                     'B' . $filterRow . ':E' . $filterRow
    //                 );

    //                 $sheet->setCellValue(
    //                     'B' . $filterRow,
    //                     Carbon::parse($data->start_date)->format('d-m-Y')
    //                 );

    //                 $filterRow++;
    //             }

    //             // End Date
    //             if (!empty($data->end_date)) {

    //                 $sheet->setCellValue(
    //                     'A' . $filterRow,
    //                     'End Date:'
    //                 );

    //                 $sheet->mergeCells(
    //                     'B' . $filterRow . ':E' . $filterRow
    //                 );

    //                 $sheet->setCellValue(
    //                     'B' . $filterRow,
    //                     Carbon::parse($data->end_date)->format('d-m-Y')
    //                 );

    //                 $filterRow++;
    //             }

    //             // Employee ID
    //             if (!empty($data->employee_id)) {

    //                 $employee = \App\Models\User::find($data->employee_id);

    //                 $employeeName = $employee
    //                     ? $employee->name
    //                     : 'N/A';

    //                 $sheet->setCellValue(
    //                     'A' . $filterRow,
    //                     'Employee:'
    //                 );

    //                 $sheet->mergeCells(
    //                     'B' . $filterRow . ':E' . $filterRow
    //                 );

    //                 $sheet->setCellValue(
    //                     'B' . $filterRow,
    //                     $employeeName
    //                 );

    //                 $filterRow++;
    //             }

    //             // Due Time
    //             if (!empty($data->due_time)) {

    //                 $dueLabel = match ($data->due_time) {

    //                     'ninty' => '90 Days and Above',

    //                     'sixty' => '60 - 89 Days',

    //                     'thirty' => '30 - 59 Days',

    //                     'thirty_less' => '0 - 29 Days',

    //                     default => $data->due_time,
    //                 };

    //                 $sheet->setCellValue(
    //                     'A' . $filterRow,
    //                     'Due:'
    //                 );

    //                 $sheet->mergeCells(
    //                     'B' . $filterRow . ':E' . $filterRow
    //                 );

    //                 $sheet->setCellValue(
    //                     'B' . $filterRow,
    //                     $dueLabel
    //                 );

    //                 $filterRow++;
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Filter Styling
    //         |--------------------------------------------------------------------------
    //         */

    //             if ($filterRow > 2) {

    //                 $sheet->getStyle(
    //                     'A2:E' . ($filterRow - 1)
    //                 )->applyFromArray([
    //                     'font' => [
    //                         'size' => 11,
    //                     ],
    //                 ]);

    //                 $sheet->getStyle(
    //                     'A2:A' . ($filterRow - 1)
    //                 )->getFont()->setBold(true);
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Table Heading
    //         |--------------------------------------------------------------------------
    //         |
    //         | Always start table at row 3.
    //         |
    //         */

    //             $tableHeadingRow = 3;

    //             $sheet->getStyle(
    //                 'A' . $tableHeadingRow . ':E' . $tableHeadingRow
    //             )->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'size' => 12,
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                 ],
    //             ]);

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Data Rows
    //         |--------------------------------------------------------------------------
    //         */

    //             $highestRow = $sheet->getHighestRow();

    //             if ($highestRow >= 4) {

    //                 // SI.No
    //                 $sheet->getStyle(
    //                     'A7:A' . $highestRow
    //                 )->getAlignment()->setHorizontal(
    //                     Alignment::HORIZONTAL_CENTER
    //                 );

    //                 // Customer Name
    //                 $sheet->getStyle(
    //                     'B7:B' . $highestRow
    //                 )->getAlignment()->setHorizontal(
    //                     Alignment::HORIZONTAL_LEFT
    //                 );

    //                 // Date
    //                 $sheet->getStyle(
    //                     'C7:C' . $highestRow
    //                 )->getAlignment()->setHorizontal(
    //                     Alignment::HORIZONTAL_CENTER
    //                 );

    //                 // Balance Amount
    //                 $sheet->getStyle(
    //                     'D7:D' . $highestRow
    //                 )->getAlignment()->setHorizontal(
    //                     Alignment::HORIZONTAL_RIGHT
    //                 );

    //                 $sheet->getStyle(
    //                     'D7:D' . $highestRow
    //                 )->getNumberFormat()->setFormatCode(
    //                     '#,##0.00'
    //                 );

    //                 // Due
    //                 $sheet->getStyle(
    //                     'E7:E' . $highestRow
    //                 )->getAlignment()->setHorizontal(
    //                     Alignment::HORIZONTAL_CENTER
    //                 );
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Total Row
    //         |--------------------------------------------------------------------------
    //         */

    //             if ($highestRow >= 4) {

    //                 $totalRow = $highestRow + 1;

    //                 $sheet->setCellValue(
    //                     'C' . $totalRow,
    //                     'Total:'
    //                 );

    //                 $sheet->setCellValue(
    //                     'D' . $totalRow,
    //                     '=SUM(D7:D' . $highestRow . ')'
    //                 );

    //                 $sheet->getStyle(
    //                     'C' . $totalRow . ':D' . $totalRow
    //                 )->applyFromArray([
    //                     'font' => [
    //                         'bold' => true,
    //                     ],
    //                     'alignment' => [
    //                         'horizontal' =>
    //                         Alignment::HORIZONTAL_RIGHT,
    //                     ],
    //                 ]);

    //                 $sheet->getStyle(
    //                     'D' . $totalRow
    //                 )->getNumberFormat()->setFormatCode(
    //                     '#,##0.00'
    //                 );
    //             }
    //         },
    //     ];
    // }


    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            $data = $this->data;

            /*
            |--------------------------------------------------------------------------
            | Calculate Column Heading Row
            |--------------------------------------------------------------------------
            */

            $headingRow = 2;

            if (!empty($data->customer_name)) {
                $headingRow++;
            }

            if (!empty($data->start_date)) {
                $headingRow++;
            }

            if (!empty($data->end_date)) {
                $headingRow++;
            }

            if (!empty($data->employee_id)) {
                $headingRow++;
            }

            if (!empty($data->due_time)) {
                $headingRow++;
            }

            /*
            |--------------------------------------------------------------------------
            | Real Data Starts After Column Heading
            |--------------------------------------------------------------------------
            */

            $dataStartRow = $headingRow + 1;

            /*
            |--------------------------------------------------------------------------
            | Main Heading
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells('A1:E1');

            $sheet->setCellValue(
                'A1',
                'Customer Due Report'
            );

            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            $filterRow = 2;

            // Customer Name
            if (!empty($data->customer_name)) {

                $sheet->setCellValue(
                    'A' . $filterRow,
                    'Customer Name:'
                );

                $sheet->mergeCells(
                    'B' . $filterRow . ':E' . $filterRow
                );

                $sheet->setCellValue(
                    'B' . $filterRow,
                    $data->customer_name
                );

                $filterRow++;
            }

            // Start Date
            if (!empty($data->start_date)) {

                $sheet->setCellValue(
                    'A' . $filterRow,
                    'Start Date:'
                );

                $sheet->mergeCells(
                    'B' . $filterRow . ':E' . $filterRow
                );

                $sheet->setCellValue(
                    'B' . $filterRow,
                    Carbon::parse($data->start_date)
                        ->format('d-m-Y')
                );

                $filterRow++;
            }

            // End Date
            if (!empty($data->end_date)) {

                $sheet->setCellValue(
                    'A' . $filterRow,
                    'End Date:'
                );

                $sheet->mergeCells(
                    'B' . $filterRow . ':E' . $filterRow
                );

                $sheet->setCellValue(
                    'B' . $filterRow,
                    Carbon::parse($data->end_date)
                        ->format('d-m-Y')
                );

                $filterRow++;
            }

            // Employee
            if (!empty($data->employee_id)) {

                $employee = \App\Models\User::find(
                    $data->employee_id
                );

                $employeeName = $employee
                    ? $employee->name
                    : 'N/A';

                $sheet->setCellValue(
                    'A' . $filterRow,
                    'Employee:'
                );

                $sheet->mergeCells(
                    'B' . $filterRow . ':E' . $filterRow
                );

                $sheet->setCellValue(
                    'B' . $filterRow,
                    $employeeName
                );

                $filterRow++;
            }

            // Due
            if (!empty($data->due_time)) {

                switch ($data->due_time) {

                    case 'ninty':
                        $dueLabel = '90 Days and Above';
                        break;

                    case 'sixty':
                        $dueLabel = '60 - 89 Days';
                        break;

                    case 'thirty':
                        $dueLabel = '30 - 59 Days';
                        break;

                    case 'thirty_less':
                        $dueLabel = '0 - 29 Days';
                        break;

                    default:
                        $dueLabel = $data->due_time;
                        break;
                }

                $sheet->setCellValue(
                    'A' . $filterRow,
                    'Due:'
                );

                $sheet->mergeCells(
                    'B' . $filterRow . ':E' . $filterRow
                );

                $sheet->setCellValue(
                    'B' . $filterRow,
                    $dueLabel
                );

                $filterRow++;
            }

            /*
            |--------------------------------------------------------------------------
            | Filter Style
            |--------------------------------------------------------------------------
            */

            if ($headingRow > 2) {

                $sheet->getStyle(
                    'A2:E' . ($headingRow - 1)
                )->applyFromArray([
                    'font' => [
                        'size' => 11,
                    ],
                ]);

                $sheet->getStyle(
                    'A2:A' . ($headingRow - 1)
                )->getFont()->setBold(true);
            }

            /*
            |--------------------------------------------------------------------------
            | Column Headings
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                'A' . $headingRow . ':E' . $headingRow
            )->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Last Actual Data Row
            |--------------------------------------------------------------------------
            */

            $highestRow = $sheet->getHighestRow();

            /*
            |--------------------------------------------------------------------------
            | Data Formatting
            |--------------------------------------------------------------------------
            */

            if ($highestRow >= $dataStartRow) {

                // SI.No
                $sheet->getStyle(
                    'A' . $dataStartRow . ':A' . $highestRow
                )->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

                // Customer Name
                $sheet->getStyle(
                    'B' . $dataStartRow . ':B' . $highestRow
                )->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_LEFT
                );

                // Date
                $sheet->getStyle(
                    'C' . $dataStartRow . ':C' . $highestRow
                )->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

                // Balance Amount
                $sheet->getStyle(
                    'D' . $dataStartRow . ':D' . $highestRow
                )->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_RIGHT
                );

                $sheet->getStyle(
                    'D' . $dataStartRow . ':D' . $highestRow
                )->getNumberFormat()->setFormatCode(
                    '#,##0.00'
                );

                // Due
                $sheet->getStyle(
                    'E' . $dataStartRow . ':E' . $highestRow
                )->getAlignment()->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                */

                $totalRow = $highestRow + 1;

                $sheet->setCellValue(
                    'C' . $totalRow,
                    'Total:'
                );

                $sheet->setCellValue(
                    'D' . $totalRow,
                    '=SUM(D' . $dataStartRow . ':D' . $highestRow . ')'
                );

                $sheet->getStyle(
                    'C' . $totalRow . ':D' . $totalRow
                )->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                $sheet->getStyle(
                    'D' . $totalRow
                )->getNumberFormat()->setFormatCode(
                    '#,##0.00'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Column Width
            |--------------------------------------------------------------------------
            */

            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(15);
        },
    ];
}
}
