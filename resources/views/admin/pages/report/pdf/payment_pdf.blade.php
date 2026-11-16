<!DOCTYPE html>
<html>

<head>
    <title>Payment Details Report</title>
    <style>
        @page {
            margin: 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #2c3e50;
            color: white;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .filter-info {
            background-color: #ecf0f1;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #bdc3c7;
        }

        .filter-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .filter-info td {
            padding: 4px 8px;
            border: none;
            font-size: 9px;
        }

        .filter-info strong {
            color: #2c3e50;
            min-width: 100px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
        }

        thead {
            background-color: #34495e;
            color: white;
        }

        th {
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #2c3e50;
        }

        td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            text-align: left;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        tfoot {
            background-color: #f39c12;
            color: white;
            font-weight: bold;
        }

        tfoot td {
            padding: 8px 5px;
            border: 1px solid #e67e22;
            text-align: right;
        }

        tfoot td:first-child {
            text-align: center;
        }

        .summary-box {
            margin-top: 15px;
            padding: 12px;
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
        }

        .summary-box h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 11px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .summary-row {
            display: table;
            width: 100%;
            padding: 4px 0;
            border-bottom: 1px solid #bdc3c7;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            display: table-cell;
            font-weight: bold;
            color: #2c3e50;
            width: 50%;
        }

        .summary-value {
            display: table-cell;
            color: #27ae60;
            font-weight: bold;
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Payment Details Report</h1>
    </div>

    <div class="filter-info">
        <table>
            <tr>
                <td><strong>Start Date:</strong></td>
                <td>{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : 'N/A' }}</td>
                <td><strong>End Date:</strong></td>
                <td>{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Customer:</strong></td>
                <td colspan="3">{{ $customerName ?? 'All' }}</td>
            </tr>
            <tr>
                <td><strong>Customer Type:</strong></td>
                <td colspan="3">{{ $customerType ?? 'Company Customer' }}</td>
            </tr>
            @if (isset($employeeName) && $employeeName !== 'All')
                <tr>
                    <td><strong>Employee:</strong></td>
                    <td colspan="3">{{ $employeeName ?? 'All' }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>Generated On:</strong></td>
                <td colspan="3">{{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment Date</th>
                <th>Order No</th>
                <th>Customer Code</th>
                <th>Customer Name</th>
                <th class="text-right">Paid Amount</th>
                {{-- <th class="text-right">Balance Amount</th> --}}
                <th>Payment Mode</th>
                <th>Employee</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalPaid = 0;
                // $totalBalance = 0;
            @endphp

            @foreach ($payments as $index => $payment)
                @php
                    $totalPaid += floatval($payment->paid_amount ?? 0);
                    // $totalBalance += floatval($payment->balance_amount ?? 0);

                    // Determine Order No or Opening Balance
                    $orderNo = '-';
                    if ($payment->order && $payment->order->order_no) {
                        $orderNo = $payment->order->order_no;
                    } elseif (!$payment->order_id || $payment->type === 'opening_balance') {
                        $orderNo = 'Opening Balance';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($payment->created_at)->format('d-m-Y') }}</td>
                    <td class="text-center">{{ $orderNo }}</td>
                    <td class="text-center">
                        @if ($payment->order && $payment->order->display_customer)
                            {{ $payment->order->display_customer['code'] }}
                        @elseif($payment->customer)
                            {{ $payment->customer->customer_code }}
                        @elseif($payment->order && $payment->order->customer)
                            {{ $payment->order->customer->customer_code }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($payment->order && $payment->order->display_customer)
                            {{ $payment->order->display_customer['name'] }}

                            @if (($payment->order->display_customer['type'] ?? '') == 'employee')
                                [Outside Salesman]
                            @endif
                        @elseif($payment->customer)
                            {{ $payment->customer->customer_name }}
                        @elseif($payment->order && $payment->order->customer)
                            {{ $payment->order->customer->customer_name }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($payment->paid_amount ?? 0, 2) }}</td>
                    {{-- <td class="text-right">{{ number_format($payment->balance_amount ?? 0, 2) }}</td> --}}
                    <td class="text-center">{{ $payment->payment_mode ?? '-' }}</td>
                    <td class="text-center">{{ $payment->user ? $payment->user->name : '-' }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" class="text-center"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($totalPaid, 2) }}</strong></td>
                {{-- <td class="text-right"><strong>{{ number_format($totalBalance, 2) }}</strong></td> --}}
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-box">
        <h3>Summary</h3>
        <div class="summary-row">
            <span class="summary-label">Total Records:</span>
            <span class="summary-value">{{ count($payments) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Paid Amount:</span>
            <span class="summary-value">{{ number_format($totalPaid, 2) }}</span>
        </div>
        {{-- <div class="summary-row">
            <span class="summary-label">Total Balance Amount:</span>
            <span class="summary-value">{{ number_format($totalBalance, 2) }}</span>
        </div> --}}
    </div>

    <div class="footer">
        {{-- <p>Generated on {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</p> --}}
    </div>
</body>

</html>
