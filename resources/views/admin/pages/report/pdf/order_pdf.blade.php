<!DOCTYPE html>
<html>

<head>
    <title>Order PDF</title>
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
            <td><strong>Order Details</strong></td>
            <td>{{ $order ?? 'All' }}</td>
            <td><strong>Customer</strong></td>
            <td>{{ $customer_name ?? 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Order Status</strong></td>
            <td>{{ $order_status ? ucfirst($order_status) : 'All' }}</td>
            <td><strong>Customer Type</strong></td>
    <td>
        {{ $customer_type == 'company' ? 'Company Customers' : ($customer_type == 'outside' ? 'Outside Customers' : 'Company Customers') }}
    </td>
        </tr>

    </table>

    <h2> Order Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Order No</th>
                <th>Order Type</th>
                <th>Order By</th>
                <th>Delivery by</th>
                <th>customer Name</th>
                <th>Total Quantity</th>
                <th>Free Qty</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            {{-- @dd($results); --}}
            @foreach ($results as $index => $result)
           
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->created_at)->format('Y-m-d') }}</td>
                    <td>{{ $result->order_no }}</td>
                    <td>{{ $result->order_type ?? '-' }}</td>
                    <td>{{ $result->orderBy->name ?? '-' }}</td>
                    <td>{{ $result->deliveryBy->name ?? '-' }}</td>
                    <td>{{ $result->display_customer['name'] }} </td>
                    <td>{{ $result->total_quantity ?? '-' }}</td>
                    <td>{{ $result->orderItems->sum('free_quantity') }}</td>
                    <td>{{ $result->total_amount ?? '-' }}</td>
                    <td>{{ ucfirst($result->order_status ?? 'N/A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
