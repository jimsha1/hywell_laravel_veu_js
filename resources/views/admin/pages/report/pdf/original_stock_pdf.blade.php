<!DOCTYPE html>
<html>

<head>
    <title>Original Stock PDF</title>
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
            <td><strong>Product:</strong></td>
            <td>{{ $productName ?? 'All' }}</td>
           
        </tr>

    </table>

    <h2> Stock Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Operation</th>
                <th>Product</th>
                <th>Inward</th>
                <th>Outward</th>
                <th>Remaining Qty</th>
                <th>Cost Price</th>

            </tr>
        </thead>

        <tbody>

            @foreach ($stocks as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data->date ? \Carbon\Carbon::parse($data->date)->format('Y-m-d') : \Carbon\Carbon::parse($data->created_at)->format('Y-m-d') }}</td>
                    <td>{{ $data->reason }}</td>
                    <td>{{ $data->product->product_name ?? '-' }}</td>
                    <td>{{ $data->movement_type === 'in' ? floor($data->quantity) : 0 }}</td>
                    <td>{{ $data->movement_type === 'out' ? floor($data->quantity) : 0 }}</td>
                    <td>{{ floor($data->remaining_qty ?? 0) }}</td>
                    <td>{{ $data->cost_price ?? '-' }}</td>

                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
