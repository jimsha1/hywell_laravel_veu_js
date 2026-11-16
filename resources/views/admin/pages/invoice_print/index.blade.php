<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thermal Invoice</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            width: 100%;
            /* For 58mm paper. Change to 80mm if needed */
        }

        .container {
            padding: 28px 38px;
        }

        h4 {
            margin: 0;
        }

        .invoice {
            padding: 5px;
            text-align: center;
        }

        h2 {
            margin: 0px 0;
            font-size: 14px;
        }

        .qr {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }

        td {
            padding: 2px 0;
        }

        .total {
            border-top: 1px dashed #000;
            font-weight: bold;
            margin-top: 5px;
            padding-top: 5px;
        }

        .line {
            border-top: 1px solid #000;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .logo-print {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }

        p {
            margin-top: 5px;
        }

        .customer-container {
            display: flex;
            flex-direction: column;
            width: 50%;
        }

        .customer-container p {
            font-size: 12px;
            margin: 0;
            text-align: left;
        }

        .pno {
            margin-top: 1px;
            font-size: 11px;
            margin-bottom: 0px;
        }

        .customer-container .adress-d {
            font-size: 8px;
        }

        .customer-container.text-right {
            align-items: flex-end;
        }

        .address-d {
            font-size: 9px;
            font-weight: 500;
        }

        .d-flex-hs {
            display: flex;
            justify-content: space-between;
        }

        .customer-container .name {
            font-size: 11px;
            font-weight: 500;
        }

        .customer-container p {
            font-size: 8px;
        }

        .bb {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="invoice">
        <div class="container">
            <img class="logo-print" src="{{ asset('assets/img/512-Photoroom.png') }}" alt="">
            <p class="pno">Ph: 1234567890</p>
            <p class="address-d">
                VAT No: 33758375352875<br>
                VAT Registered Address: Qatrat Sadeem Riyadh
            </p>

            <div class="d-flex-hs">
                <div class="customer-container">
                    <p class="name">{{ $orders->customer->customer_name }}</p>
                    <p class="adress-d">{{ $orders->customer->address }}</p>
                </div>
                <div class="customer-container text-right">

                    {{-- <p>Date: <span class="bb">{{ $orders->order_date?->format('d-m-Y') }}</span></p> --}}
                    <p>Date: <span class="bb">
                            {{ $orders->order_date ? \Carbon\Carbon::parse($orders->order_date)->format('d-m-Y') : $orders->created_at?->format('d-m-Y') }}
                        </span></p>
                    <p>Invoice No: <span class="bb">{{ $orders->order_no }}</span> </p>
                    {{-- <p>
                        Payment:
                        <span class="bb">
                            @if (!empty($payment_modes) && count($payment_modes))
                                {{ collect($payment_modes)->pluck('payment_mode')->implode(', ') }}
                            @else
                                N/A
                            @endif
                        </span>
                    </p> --}}
                </div>
            </div>

            <table>
                <tr>
                    <th class="text-left">Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Free.Qty</th>
                    <th class="text-center">U.Price</th>
                    <th class="text-right">Price</th>
                </tr>
                @foreach ($orders->orderItems as $item)
                    <tr>
                        <td class="text-left">{{ $item->product->product_name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-center">{{ $item->free_quantity }}</td>
                        <td class="text-center">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right">SAR {{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach

            </table>

            <table class="line">
                {{-- <tr>
                <td class="text-left">Subtotal</td>
                <td class="text-right">SAR {{ number_format($orders->subtotal, 2) }}</td>
            </tr> --}}
                {{-- <tr>
                <td class="text-left">VAT 15%</td>
                <td class="text-right">SAR 187.50</td>
            </tr> --}}
                <tr class="total">
                    <td class="text-left">Total</td>
                    <td class="text-right">SAR {{ number_format($orders->total_amount, 2) }}</td>
                </tr>
                @if (!empty($payment_modes))
                    <table class="line">
                        <tr>
                            <td colspan="2" class="text-left"><strong>Payment Breakdown</strong></td>
                        </tr>

                        @foreach ($payment_modes as $mode)
                            <tr>
                                <td class="text-left">{{ strtoupper($mode['payment_mode']) }}</td>
                                <td class="text-right">
                                    SAR {{ number_format($mode['amount'] ?? 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                <tr class="total">
                    <td class="text-left">Paid Amount</td>
                    <td class="text-right">SAR {{ number_format($orders->paid_amount, 2) }}</td>
                </tr>
                <tr class="total">
                    <td class="text-left">Balance Amount</td>
                    <td class="text-right">SAR {{ number_format($orders->balance_amount, 2) }}</td>
                </tr>
            </table>

            <p class="text-center">Thank you!</p>
        </div>
    </div>
</body>

</html>
