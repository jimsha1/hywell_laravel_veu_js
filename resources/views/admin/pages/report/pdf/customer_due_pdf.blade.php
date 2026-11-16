<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Customer Due Report</title>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .filter-title {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .filter-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .filter-table td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        .filter-label {
            font-weight: bold;
            width: 25%;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #ccc;
            padding: 7px;
        }

        .report-table th {
            text-align: center;
            background-color: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

    </style>

</head>

<body>

    <h2>Customer Due Report</h2>

    {{-- ========================================================= --}}
    {{-- FILTERS --}}
    {{-- ========================================================= --}}

    <div class="filter-title">
        Filtered By:
    </div>

    <table class="filter-table">

        {{-- Customer Name --}}

        @if (!empty($customer_name))

            <tr>

                <td class="filter-label">
                    Customer Name:
                </td>

                <td>
                    {{ $customer_name }}
                </td>

            </tr>

        @endif


        {{-- Start Date --}}

        @if (!empty($start_date))

            <tr>

                <td class="filter-label">
                    Start Date:
                </td>

                <td>
                    {{ \Carbon\Carbon::parse($start_date)->format('d-m-Y') }}
                </td>

            </tr>

        @endif


        {{-- End Date --}}

        @if (!empty($end_date))

            <tr>

                <td class="filter-label">
                    End Date:
                </td>

                <td>
                    {{ \Carbon\Carbon::parse($end_date)->format('d-m-Y') }}
                </td>

            </tr>

        @endif


        {{-- Employee --}}

        @if (!empty($employee_name))

            <tr>

                <td class="filter-label">
                    Employee:
                </td>

                <td>
                    {{ $employee_name }}
                </td>

            </tr>

        @endif


        {{-- Due Days --}}

        @if (!empty($due_label))

            <tr>

                <td class="filter-label">
                    Due:
                </td>

                <td>
                    {{ $due_label }}
                </td>

            </tr>

        @endif

    </table>


    {{-- ========================================================= --}}
    {{-- REPORT TABLE --}}
    {{-- ========================================================= --}}

    <table class="report-table">

        <thead>

            <tr>

                <th style="width: 7%;">
                    #
                </th>

                <th style="width: 35%;">
                    Customer Name
                </th>

                <th style="width: 18%;">
                    Date
                </th>

                <th style="width: 22%;">
                    Balance Amount
                </th>

                <th style="width: 18%;">
                    Due
                </th>

            </tr>

        </thead>


        <tbody>

            @php
                $totalBalance = 0;
            @endphp


            @forelse ($results as $index => $item)

                @php

                    $balanceAmount =
                        (float) ($item->balance_amount ?? 0);

                    $totalBalance += $balanceAmount;


                    $customerName = '-';

                    if (
                        isset($item->customer) &&
                        !empty($item->customer)
                    ) {

                        $customerName =
                            $item->customer->customer_name ?? '-';
                    }


                    $date =
                        $item->due_date
                        ?? $item->delivered_date
                        ?? null;


                    $dueDays =
                        (int) ($item->due_days ?? 0);

                @endphp


                <tr>

                    {{-- SI No --}}

                    <td class="text-center">

                        {{ $index + 1 }}

                    </td>


                    {{-- Customer --}}

                    <td class="text-left">

                        {{ $customerName }}

                    </td>


                    {{-- Date --}}

                    <td class="text-center">

                        {{
                            $date
                                ? \Carbon\Carbon::parse($date)->format('d-m-Y')
                                : '-'
                        }}

                    </td>


                    {{-- Balance Amount --}}

                    <td class="text-right">

                        {{ number_format($balanceAmount, 2) }}

                    </td>


                    {{-- Due Days --}}

                    <td class="text-center">

                        {{ $dueDays }}

                    </td>

                </tr>


            @empty

                <tr>

                    <td colspan="5" class="text-center">

                        No records found.

                    </td>

                </tr>

            @endforelse

        </tbody>


        {{-- ===================================================== --}}
        {{-- TOTAL --}}
        {{-- ===================================================== --}}

        @if ($results->count() > 0)

            <tfoot>

                <tr class="total-row">

                    <td
                        colspan="3"
                        class="text-right"
                    >
                        Total Due:
                    </td>

                    <td class="text-right">

                        {{ number_format($totalBalance, 2) }}

                    </td>

                    <td></td>

                </tr>

            </tfoot>

        @endif

    </table>

</body>

</html>