<!DOCTYPE html>
<html>

<head>
    <title>Product Wise Sales PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }

        th {
            text-align: center;
            background-color: #f2f2f2;
        }

        td {
            text-align: left;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <p>Filtered By:</p>
    <table style="margin-bottom: 20px; width: 100%; border-collapse: collapse;">
        <tr>
            <td><strong>Start Date:</strong></td>
            <td>{{ $startDate }}</td>
            <td><strong>End Date:</strong></td>
            <td>{{ $endDate }}</td>
        </tr>
        <tr>
            <td><strong>Product Name:</strong></td>
            <td>{{ $product_name ?: 'All' }}</td>
            <td><strong>Category:</strong></td>
            <td>{{ $category_id ? 'Selected' : 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Product ID:</strong></td>
            <td>{{ $product_id ?: 'All' }}</td>
            <td><strong>Customer:</strong></td>
            <td>{{ $customer_name ?: 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Delivery:</strong></td>
            <td>{{ $delivery_man_name ?: 'All' }}</td>
            <td><strong>Order By:</strong></td>
            <td>{{ $order_by ? ucwords(str_replace('_', ' ', $order_by)) : 'Default' }}</td>
        </tr>
    </table>

    <h2>Product Wise Sales Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Free Quantity</th>
                <th>Returned Quantity</th>
            </tr>
        </thead>
        <tbody>
            @php
                $resultsCollection = is_array($results) ? collect($results) : $results;
            @endphp
            @if($resultsCollection && count($resultsCollection) > 0)
                @foreach ($resultsCollection as $index => $result)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $result['product_code'] ?? 'N/A' }}</td>
                        <td>{{ $result['product_name'] ?? 'N/A' }}</td>
                        <td>{{ $result['total_quantity'] ?? 0 }}</td>
                        <td>{{ $result['total_free_quantity'] ?? 0 }}</td>
                        <td>{{ $result['total_returned_quantity'] ?? 0 }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="3" style="text-align: right;">Grand Total:</td>
                    <td>{{ $resultsCollection->sum('total_quantity') }}</td>
                    <td>{{ $resultsCollection->sum('total_free_quantity') }}</td>
                    <td>{{ $resultsCollection->sum('total_returned_quantity') }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="6" style="text-align: center;">No data found</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
