<!DOCTYPE html>
<html>

<head>
    <title>Customer Credit Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            margin: 0 auto;
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
            border-color: #ccc;
            padding: 8px;
        }

        td {
            text-align: left;
        }

        th {
            text-align: center;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <p>Filtered By:</p>
    <table style="margin-bottom: 20px; width: 100%; border-collapse: collapse;">
        <tr>
            <td><strong>Customer Name:</strong></td>
            <td>{{ $customer_name ?: 'All' }}</td>
            <td><strong>Customer Code:</strong></td>
            <td>{{ $customer_code ?: 'All' }}</td>
        </tr>
        {{-- <tr>
            <td><strong>Customer Type:</strong></td>
            <td>{{ $customer_type }}</td>
            <td></td>
            <td></td>
        </tr> --}}
        @if (!empty($employee_name))
        <tr>
            <td><strong>Employee:</strong></td>
            <td>{{ $employee_name }}</td>
            <td></td>
            <td></td>
        </tr>
        @endif
    </table>

    <h2>Customer Credit Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer Code</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Credit Balance</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalCredit = 0;
            @endphp
            @foreach ($results as $index => $result)
                @php
                    $totalCredit += $result->balance_amount ?? 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $result->customer_code ?? '-' }}</td>
                    <td>{{ $result->customer_name ?? '-' }}</td>
                    <td>{{ $result->phone_number ?? '-' }}</td>
                    <td style="text-align: right;">{{ number_format($result->balance_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>Total Credit:</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
