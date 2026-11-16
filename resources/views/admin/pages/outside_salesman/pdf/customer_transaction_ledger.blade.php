<!DOCTYPE html>
<html>

<head>
    <title>Customer Transaction</title>
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

        .customer-info {
            background-color: #ecf0f1;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #bdc3c7;
        }

        .customer-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .customer-info td {
            padding: 4px 8px;
            border: none;
            font-size: 9px;
        }

        .customer-info strong {
            color: #2c3e50;
            min-width: 120px;
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

        .summary-section {
            margin-top: 25px;
        }

        .summary-section table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            border: 2px solid #2c3e50;
        }

        .summary-section thead {
            background-color: #34495e;
            color: white;
        }

        .summary-section th {
            padding: 10px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #2c3e50;
        }

        .summary-section tbody tr {
            background-color: #ffffff;
        }

        .summary-section tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .summary-section td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-section td:first-child {
            width: 50%;
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        .summary-section td:last-child {
            text-align: right;
            width: 50%;
            color: #2c3e50;
            font-size: 12px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #2c3e50;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Customer Transaction Ledger</h1>
    </div>

    <div class="customer-info">
        <table>
            <tr>
                <td><strong>Customer Name:</strong> {{ $customer->customer_name }}</td>
                <td><strong>Customer Code:</strong> {{ $customer->customer_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Phone Number:</strong> {{ $customer->phone_number ?? 'N/A' }}</td>
                <td><strong>Address:</strong> {{ $customer->address ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Current Balance:</strong> {{ $final_balance }} SR</td>
                <td><strong>Generated On:</strong> {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Date</th>
                {{-- <th style="width: 5%;">ID</th> --}}
                <th style="width: 20%;">Transaction Type</th>
                <th style="width: 12%;" class="text-right">Debit</th>
                <th style="width: 12%;" class="text-right">Credit</th>
                <th style="width: 12%;" class="text-right">Balance</th>
                <th style="width: 31%;">Description</th>
            </tr>
        </thead>
        <tbody>
            @if(count($transactions) > 0)
                @foreach($transactions as $transaction)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>
                        {{-- <td class="text-center">{{ $transaction['id'] ?: '-' }}</td> --}}
                        <td>{{ $transaction['type'] }}</td>
                        <td class="text-right">{{ $transaction['debit'] }}</td>
                        <td class="text-right">{{ $transaction['credit'] }}</td>
                        <td class="text-right">{{ $transaction['balance'] }}</td>
                        <td>{{ $transaction['description'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="text-center">No transactions found</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary-section">
        <table>
            <thead>
                <tr>
                    <th colspan="2" style="text-align: center; font-size: 12px;">SUMMARY</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Opening Balance:</td>
                    <td>{{ $opening_balance }} SR</td>
                </tr>
                <tr>
                    <td>Total Order Amount:</td>
                    <td>{{ $total_order_amount }} SR</td>
                </tr>
                <tr>
                    <td>Total Paid Amount:</td>
                    <td>{{ $total_paid_amount }} SR</td>
                </tr>
                {{-- <tr>
                    <td>Total Sales Return:</td>
                    <td>{{ $total_sales_return_amount }} SR</td>
                </tr> --}}
                <tr>
                    <td>Balance Amount:</td>
                    <td>{{ $balance_amount }} SR</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        {{-- <p>This is a computer-generated report. No signature required.</p> --}}
    </div>
</body>

</html>

