<!DOCTYPE html>
<html>

<head>
    <title>Supplier Credit Report</title>
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
            <td><strong>Supplier Name:</strong></td>
            <td>{{ $supplier_name ?: 'All' }}</td>
            <td><strong>Supplier Code:</strong></td>
            <td>{{ $supplier_code ?: 'All' }}</td>
        </tr>
        
    </table>

    <h2>Supplier Credit Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Supplier Code</th>
                <th>Supplier Name</th>
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
                    $totalCredit += $result->balance ?? 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $result->supplier_code ?? '-' }}</td>
                    <td>{{ $result->supplier_name ?? '-' }}</td>
                    <td>{{ $result->phone ?? '-' }}</td>
                    <td style="text-align: right;">{{ number_format($result->balance ?? 0, 2) }}</td>
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
