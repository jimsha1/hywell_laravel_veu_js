<!DOCTYPE html>
<html>

<head>
    <title>Customer List</title>
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
            font-size: 9px;
        }

        .filter-info strong {
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

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Customer List</h1>
    </div>

    @if (
        $filters['search'] ||
            $filters['start_date'] ||
            $filters['end_date'] ||
            $filters['added_by'] ||
            $filters['employee_id']
    )
        <div class="filter-info">
            <strong>Filters Applied:</strong><br>
            @if ($filters['search'])
                <strong>Search:</strong> {{ $filters['search'] }}<br>
            @endif
            @if ($filters['start_date'])
                <strong>Start Date:</strong> {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }}<br>
            @endif
            @if ($filters['end_date'])
                <strong>End Date:</strong> {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}<br>
            @endif
            @if ($filters['added_by'])
                <strong>Added By:</strong> {{ $filters['added_by'] }}<br>
            @endif
            @if ($filters['employee_id'] && $filters['employee_name'])
                <strong>Employee:</strong> {{ $filters['employee_name'] }}<br>
            @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Code</th>
                <th style="width: 25%;">Name</th>
                <th style="width: 15%;">Mobile</th>
                <th style="width: 20%;">Employee</th>
            </tr>
        </thead>
        <tbody>
            @if (count($customers) > 0)
                @foreach ($customers as $index => $customer)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $customer->customer_code ?? 'N/A' }}</td>
                        <td>{{ $customer->customer_name ?? 'N/A' }}</td>
                        <td>{{ $customer->phone_number ?? 'N/A' }}</td>
                        <td>{{ $customer->employee->name ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="text-center">No customers found</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>

</html>
