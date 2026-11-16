<!DOCTYPE html>
<html>

<head>
    <title>Commission PDF</title>
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

    {{-- <p>Filtered By:</p>
    <table style="margin-bottom: 20px; width: 100%;">
        <tr>
            <td><strong>Order No :</strong></td>
            <td>{{ $order_no ?? 'All' }}</td>
            <td><strong>Start Date:</strong></td>
            <td>{{ $startDate ?? 'N/A' }}</td>
            <td><strong>End Date:</strong></td>
            <td>{{ $endDate ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td><strong>Customer :</strong></td>
            <td>{{ $customer ?? 'All' }}</td>
            <td><strong>Sales man:</strong></td>
            <td>{{$sales_man?? 'N/A' }}</td>
            <td><strong>Payment mode:</strong></td>
            <td>{{ $payment_mode ?? 'N/A' }}</td>
        </tr>
    </table> --}}


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
            <td><strong>Employee:</strong></td>
            <td>{{ $employee ?? 'All' }}</td>
            <td><strong>Product:</strong></td>
            <td>{{ $productName ?? 'All' }}</td>
        </tr>

    </table>

    <h2> Commission Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Employee</th>
                <th>Order No</th>
                <th>Product</th>
                <th>Commission</th>


            </tr>
        </thead>

        <tbody>

            {{-- @foreach ($results as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->created_at)->format('Y-m-d') }}</td>
                    <td>{{ $result->orderBy->name}}</td>
                    <td>{{ $result->product->product_name [$result->product->product_code ]}}</td>
                    <td>{{ $result->commission_amount }}</td>
                </tr>
            @endforeach --}}

            @foreach ($results as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->created_at)->format('Y-m-d') }}</td>
                    {{-- <td>{{ $result->orderedBy->name }}</td> Assuming 'orderedBy' is the correct relationship --}}
                    <td>{{ optional($result->orderBy)->name }}</td>
                    <td>{{ optional($result->order)->order_no }}</td>
                    <td>{{ $result->product->product_name }} [{{ $result->product->product_code }}]</td>
                    <td>{{ $result->commission_amount }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
