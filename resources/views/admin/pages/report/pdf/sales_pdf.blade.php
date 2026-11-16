<!DOCTYPE html>
<html>

<head>
    <title>Sales PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th,
        td {
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

        ul {
            margin: 0;
            padding-left: 18px;
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
            <td><strong>Invoice No</strong></td>
            <td>{{ $invoice_no ?? 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Customer</strong></td>
            <td>{{ $customer_name ?? 'All' }}</td>
            <td><strong>Payment Status</strong></td>
            <td>{{ $payment_status ?? 'All' }}</td>
            <td><strong>Payment Mode</strong></td>
            <td>{{ $payment_mode ?? 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Customer Type</strong></td>
            <td>{{ $customer_type ?? 'Company Customers' }}</td>
            <td><strong>Order By</strong></td>
            <td>{{ $order_by ?? 'All' }}</td>
            <td><strong>Delivery By</strong></td>
            <td>{{ $delivery_by ?? 'All' }}</td>
        </tr>

    </table>
         <h2> Sales Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Order By</th>
                <th>Seller</th>
                <th>Name</th>
                <th>Total Qty</th>
                <th>Free Qty</th>
                <th>Total Amt</th>
                <th>Paid Amt</th>
                <th>Refund Amt</th>
                <th>Refund Order Balance</th>
                <th>Balance Amt</th>
                <th>Payment Status</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($results as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->created_at)->format('d-m-Y') }}</td>
                    <td>
                        {{ $result->order_no ?? '-' }}
                        @if ($result->is_returned == 1)
                            <span style="color: #c2185b;">(Returned)</span>
                        @endif
                    </td>
                    <td>{{ $result->orderBy->name ?? '-' }}</td>
                    <td>{{ $result->deliveryBy->name ?? '-' }}</td>
                    <td>{{ $result->display_customer['name'] ?? '-' }}</td>
                    <td>{{ $result->total_quantity ?? 0 }}</td>
                    <td>{{ $result->orderItems->sum('free_quantity') }}</td>
                    <td>{{ number_format($result->total_amount ?? 0, 2) }}</td>

                    <td>
                        @php
                            $matchingPayments = collect($result->payments ?? [])->where(
                                'payment_mode',
                                $payment_mode ?? null,
                            );
                            $filteredAmount = $matchingPayments->sum('amount');
                        @endphp

                        @if (!empty($payment_mode) && $matchingPayments->isNotEmpty())
                            {{ number_format($filteredAmount, 2) }}
                        @else
                            {{ number_format($result->paid_amount ?? 0, 2) }}
                        @endif
                    </td>

                    <td>{{ number_format($result->total_refunded_amount ?? 0, 2) }}</td>
                    <td>{{ number_format($result->total_refund_from_order_balance ?? 0, 2) }}</td>
                    <td>{{ number_format($result->balance_amount ?? 0, 2) }}</td>
                    <td>{{ $result->payment_status ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
