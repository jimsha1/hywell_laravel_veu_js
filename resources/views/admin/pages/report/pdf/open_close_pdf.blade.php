<!DOCTYPE html>
<html>

<head>
    <title>Open/Close PDF</title>
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

        tr {

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

            ul {
                margin: 0;
                padding-left: 18px;
            }

            h2 {
                text-align: center;
                margin-bottom: 20px;
                text-decoration: underline;
            }
        }

        tr {
            th {
                text-align: center;
            }

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

            ul {
                margin: 0;
                padding-left: 18px;
            }

            h2 {
                text-align: center;
                margin-bottom: 20px;
                text-decoration: underline;
            }
        }
    </style>
</head>

<body>
    <p>Filtered By:</p>
    <table style="margin-bottom: 20px; width: 100%; border-collapse: collapse;">
        <tr>
            <td><strong>Start Date:</strong></td>
            <td>
                {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : 'N/A' }}
            </td>
            <td><strong>End Date:</strong></td>
            <td>
                {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'N/A' }}
            </td>
        </tr>
        <tr>
            <td><strong>Stock Code</strong></td>
            <td>{{ $stock_code ?? 'All' }}</td>
            <td><strong>Vehicle ID</strong></td>
            <td>{{ $vehicleName ?? 'All' }}</td>
        </tr>

    </table>

    <h2> Open/Close Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Open Date</th>
                <th>Close Date</th>
                <th>Stock Code</th>
                <th>Vehicle</th>
                <th>Stock By</th>
                <th>Total Load</th>
                <th>Balance Load</th>
                <th>Status</th>


            </tr>
        </thead>

        <tbody>
{{-- @dd($results); --}}
            @foreach ($results as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->created_at)->format('Y-m-d') }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->closing_req_date)->format('Y-m-d') }}</td>
                    <td>{{ $result->v_stock_code }}</td>
                    <td>{{ $result->vehicle->vehicle_name ?? '-' }}</td>
                    <td>{{ $result->user->name ?? '-' }}</td>>
                    <td>
                    {{ $result->vehicleLoadStockItems ?
                        $result->vehicleLoadStockItems->sum(function($item) {
                            return (int) ($item->total_qty ?? 0);
                        }) : 0 }}
                </td>
                <td>
                    {{ $result->vehicleLoadStockItems ?
                        $result->vehicleLoadStockItems->sum(function($item) {
                            return (int) ($item->available_qty ?? 0);
                        }) : 0 }}
                </td>
                <td>
                    @if($result->day_status === 'started')
                        <span style="background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px;">
                            {{ $result->day_status }}
                        </span>
                    @elseif($result->day_status === 'closed')
                        <span style="background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 4px;">
                            {{ $result->day_status }}
                        </span>
                    @else
                        <span>{{ $result->day_status }}</span>
                    @endif
                </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
