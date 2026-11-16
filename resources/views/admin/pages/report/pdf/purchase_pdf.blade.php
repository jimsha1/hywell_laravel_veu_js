<!DOCTYPE html>
<html>

<head>
    <title>Purchase PDF</title>
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
            <td><strong>Purchase No</strong></td>
            <td>{{ $purchase_no ?? 'All' }}</td>
        </tr>
        <tr>
            <td><strong>Supplier</strong></td>
            <td>{{ $supplier ?? 'All' }}</td>
            <td><strong>Product</strong></td>
            <td>{{ $productName ?? 'All' }}</td>
            <td><strong>Payment Mode</strong></td>
            <td>{{ $payment_mode ?? 'All' }}</td>
        </tr>

    </table>

    <h2> Purchase Report</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Purchase No</th>
                <th>Purchase Date</th>
                <th>Supplier Name</th>
                <th>Payment Mode</th>
                <th>Purchase Amount</th>
                <th>Paid Amount</th>
                <th>Refund Amount</th>
                <th>Refund Order Balance</th>
                <th>Balance Amount</th>
            </tr>
        </thead>

        <tbody>

            @foreach ($purchases as $index => $purchase)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $purchase->purchase_no ?? '-' }}
                        @if ($purchase->is_returned == 1)
                            <span style="color: #c2185b;">(Returned)</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $purchase->supplier->supplier_name ?? '-' }}</td>

                    {{-- Support multiple payment modes --}}
                    <td>
                        @if($purchase->purchasePayment && count($purchase->purchasePayment))
                            <ul>
                                @foreach ($purchase->purchasePayment as $payment)
                                    <li>{{ $payment->payment_mode }} ({{ number_format($payment->amount_taken, 2) }})</li>
                                @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>

                    <td>{{ number_format($purchase->total, 2) }}</td>
                    <td>{{ number_format($purchase->payment_total ?? 0, 2) }}</td>
                    <td>{{ number_format($purchase->total_refund_amount ?? 0, 2) }}</td>
                    <td>{{ number_format($purchase->refund_amount_from_order_balance ?? 0, 2) }}</td>
                    <td>{{ number_format($purchase->balance_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach

        </tbody>
    </table>
</body>

</html>
