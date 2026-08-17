<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Tanker Summary Report -
        {{ strtolower((string) $selectedTanker) === 'all'
            ? 'All Tankers'
            : $selectedTanker }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --brand-blue: #0759a5;
            --brand-blue-dark: #003f7d;
            --brand-green: #61b946;
            --brand-green-light: #eaf7e6;
            --border: #cbd5e1;
            --text: #111827;
            --muted: #64748b;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef5f1;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

        body {
            padding: 14px;
        }

        .page {
            position: relative;
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 10px;
            background: #ffffff;
            padding: 10mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        .page::before {
            position: absolute;
            top: -70px;
            right: -80px;
            z-index: 0;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(97, 185, 70, 0.10);
            content: "";
        }

        .page::after {
            position: absolute;
            bottom: -95px;
            left: -80px;
            z-index: 0;
            width: 390px;
            height: 230px;
            border-radius: 50%;
            background: rgba(7, 89, 165, 0.10);
            content: "";
        }

        .content {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 185mm;
            flex-direction: column;
        }

        .print-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 10px;
        }

        .print-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            border: none;
            border-radius: 7px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        .print-button {
            background: var(--brand-blue);
            color: #ffffff;
        }

        .close-button {
            border: 1px solid var(--border) !important;
            background: #ffffff;
            color: var(--brand-blue-dark);
        }

        .header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 9px;
            border: 1px solid var(--border);
            border-left: 8px solid var(--brand-green);
            border-radius: 12px;
            background:
                linear-gradient(
                    135deg,
                    #ffffff 0%,
                    #f4fbf1 50%,
                    #eef7ff 100%
                );
            padding: 14px 16px;
        }

        .brand-area {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
        }

        .logo-wrap {
            display: flex;
            width: 66px;
            height: 66px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #ffffff;
            padding: 5px;
        }

        .logo-wrap img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-placeholder {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--brand-green-light);
            color: var(--brand-blue);
            font-size: 22px;
            font-weight: bold;
        }

        .company-title {
            margin: 0;
            color: var(--brand-blue-dark);
            font-size: 18px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .company-subtitle {
            margin-top: 4px;
            color: var(--brand-green);
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1.7px;
            text-transform: uppercase;
        }

        .document-box {
            flex: 0 0 auto;
            text-align: right;
        }

        .document-title {
            color: var(--brand-blue);
            font-size: 16px;
            font-weight: 800;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .document-subtitle {
            margin-top: 5px;
            color: var(--muted);
            font-size: 8.5px;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .summary-card {
            overflow: hidden;
            margin-bottom: 9px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #ffffff;
        }

        .summary-header {
            background: var(--brand-blue);
            color: #ffffff;
            padding: 6px 9px;
            font-size: 9.5px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: none;
            border-bottom: 1px solid #edf2f7;
            padding: 6px 9px;
            font-size: 9px;
            vertical-align: middle;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            width: 145px;
            color: var(--muted);
            font-weight: bold;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .summary-value {
            color: var(--text);
            font-weight: bold;
        }

        .section-title {
            margin: 8px 0 5px;
            border-radius: 7px;
            background:
                linear-gradient(
                    90deg,
                    var(--brand-blue),
                    var(--brand-green)
                );
            color: #ffffff;
            padding: 6px 9px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .table-holder {
            width: 100%;
            margin-bottom: 10px;
            overflow: visible;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            overflow-wrap: break-word;
            border: 1px solid var(--border);
            padding: 5px;
            vertical-align: middle;
        }

        th {
            background: #eaf2fb;
            color: var(--brand-blue-dark);
            font-size: 7px;
            font-weight: 800;
            line-height: 1.15;
            text-align: center;
            text-transform: uppercase;
        }

        td {
            background: #ffffff;
            font-size: 7.6px;
            line-height: 1.2;
        }

        .date-column {
            width: 8%;
        }

        .supplier-column {
            width: 10%;
        }

        .customer-column {
            width: 12%;
        }

        .liters-column {
            width: 7%;
        }

        .freight-column {
            width: 7%;
        }

        .income-column {
            width: 8%;
        }

        .driver-column {
            width: 9%;
        }

        .salary-column {
            width: 8%;
        }

        .expense-column {
            width: 8%;
        }

        .net-income-column {
            width: 8%;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .positive {
            color: #059669;
            font-weight: 800;
        }

        .negative {
            color: #dc2626;
            font-weight: 800;
        }

        .zero {
            color: #64748b;
            font-weight: 700;
        }

        .tanker-row td {
            border-color: var(--brand-blue);
            background: var(--brand-blue);
            color: #ffffff;
            padding: 6px 8px;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-align: left;
            text-transform: uppercase;
        }

        .tanker-total-row td {
            background: #f1f8ee;
            color: var(--brand-blue-dark);
            font-weight: 800;
        }

        .grand-total-row td {
            background: #dceeff;
            color: var(--brand-blue-dark);
            font-weight: 800;
        }

        .empty-row td {
            padding: 25px;
            color: var(--muted);
            font-size: 9px;
            text-align: center;
        }

        .footer-note {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
            border-top: 2px solid var(--brand-green);
            padding-top: 8px;
            color: var(--muted);
            font-size: 8px;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 7mm;
            }

            html,
            body {
                width: auto;
                height: auto;
                background: #ffffff;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 8.5px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                width: 100%;
                min-height: 196mm;
                margin: 0;
                overflow: visible;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }

            .content {
                min-height: 196mm;
            }

            .print-actions {
                display: none;
            }

            .header-card {
                margin-bottom: 8px;
                padding: 12px 14px;
            }

            .logo-wrap {
                width: 60px;
                height: 60px;
            }

            .company-title {
                font-size: 16px;
            }

            .document-title {
                font-size: 14px;
            }

            .summary-table td {
                padding: 5px 8px;
                font-size: 8.5px;
            }

            th {
                padding: 4px 3px;
                font-size: 6.5px;
            }

            td {
                padding: 4px 3px;
                font-size: 7px;
            }

            .section-title {
                margin-top: 7px;
                margin-bottom: 4px;
                padding: 5px 8px;
            }

            .header-card,
            .summary-card,
            .section-title,
            th,
            td,
            .tanker-row td,
            .tanker-total-row td,
            .grand-total-row td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            table,
            tr,
            td,
            th {
                page-break-inside: avoid;
            }

            .tanker-row {
                page-break-after: avoid;
            }

            .tanker-total-row {
                page-break-before: avoid;
            }
        }
    </style>
</head>

<body>
<div class="page">
    <div class="content">

        @php
            $logoPaths = [
                public_path('images/logo.png'),
                public_path('logo.png'),
            ];

            $logoFile = null;

            foreach ($logoPaths as $path) {
                if (file_exists($path)) {
                    $logoFile = $path;
                    break;
                }
            }

            $logoSrc = null;

            if ($logoFile) {
                $logoMime = function_exists('mime_content_type')
                    ? mime_content_type($logoFile)
                    : 'image/png';

                $logoSrc =
                    'data:' .
                    $logoMime .
                    ';base64,' .
                    base64_encode(file_get_contents($logoFile));
            }

            $money = static fn ($amount): string =>
                '₱ ' . number_format((float) $amount, 2);

            $number = static fn ($amount): string =>
                number_format((float) $amount, 2);

            $amountClass = static function ($amount): string {
                $amount = round((float) $amount, 2);

                if ($amount > 0) {
                    return 'positive';
                }

                if ($amount < 0) {
                    return 'negative';
                }

                return 'zero';
            };

            $tankerFilterLabel =
                strtolower(trim((string) $selectedTanker)) === 'all'
                    ? 'All Tankers'
                    : $selectedTanker;

            $totalRecords = $records->count();
            $totalTankers = $groupedRecords->count();

            $grandLiters = (float) ($grandTotals['liters'] ?? 0);
            $grandIncome = (float) ($grandTotals['income'] ?? 0);

            $grandDriverSalary =
                (float) ($grandTotals['driver_salary'] ?? 0);

            $grandOtherExpenses =
                (float) ($grandTotals['other_expenses'] ?? 0);

            $grandNetIncome =
                (float) ($grandTotals['net_income'] ?? 0);
        @endphp

        <div class="print-actions">
            <button
                type="button"
                onclick="window.print()"
                class="print-button"
            >
                Print Tanker Summary
            </button>

            <button
                type="button"
                onclick="window.close()"
                class="close-button"
            >
                Close
            </button>
        </div>

        <div class="header-card">
            <div class="brand-area">
                <div class="logo-wrap">
                    @if ($logoSrc)
                        <img
                            src="{{ $logoSrc }}"
                            alt="Company Logo"
                        >
                    @else
                        <div class="logo-placeholder">
                            E
                        </div>
                    @endif
                </div>

                <div>
                    <div class="company-title">
                        EDZELVOR FUEL TRADING
                    </div>

                    <div class="company-subtitle">
                        Cleaner Solutions • Greener Tomorrow
                    </div>
                </div>
            </div>

            <div class="document-box">
                <div class="document-title">
                    Tanker Summary Report
                </div>

                <div class="document-subtitle">
                    {{ $tankerFilterLabel }}
                    •
                    {{ now()->format('F Y') }}
                </div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-header">
                Report Summary
            </div>

            <table class="summary-table">
                <tr>
                    <td class="summary-label">
                        Tanker Filter
                    </td>

                    <td class="summary-value">
                        {{ $tankerFilterLabel }}
                    </td>

                    <td class="summary-label">
                        Total Tankers
                    </td>

                    <td class="summary-value">
                        {{ number_format($totalTankers) }}
                    </td>
                </tr>

                <tr>
                    <td class="summary-label">
                        Total Delivery Records
                    </td>

                    <td class="summary-value">
                        {{ number_format($totalRecords) }}
                    </td>

                    <td class="summary-label">
                        Total Liters
                    </td>

                    <td class="summary-value">
                        {{ $number($grandLiters) }} L
                    </td>
                </tr>

                <tr>
                    <td class="summary-label">
                        Total Freight Income
                    </td>

                    <td class="summary-value {{ $amountClass($grandIncome) }}">
                        {{ $money($grandIncome) }}
                    </td>

                    <td class="summary-label">
                        Total Driver Salary
                    </td>

                    <td class="summary-value {{ $amountClass($grandDriverSalary) }}">
                        {{ $money($grandDriverSalary) }}
                    </td>
                </tr>

                <tr>
                    <td class="summary-label">
                        Total Other Expenses
                    </td>

                    <td class="summary-value {{ $amountClass($grandOtherExpenses) }}">
                        {{ $money($grandOtherExpenses) }}
                    </td>

                    <td class="summary-label">
                        Total Net Income
                    </td>

                    <td class="summary-value {{ $amountClass($grandNetIncome) }}">
                        {{ $money($grandNetIncome) }}
                    </td>
                </tr>

                <tr>
                    <td class="summary-label">
                        Generated Date
                    </td>

                    <td class="summary-value">
                        {{ now()->format('m/d/Y h:i A') }}
                    </td>

                    <td class="summary-label">
                        Report Type
                    </td>

                    <td class="summary-value">
                        Tanker Delivery and Income Summary
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">
            Tanker Delivery, Driver Payment and Income Details
        </div>

        <div class="table-holder">
            <table>
                <thead>
                    <tr>
                        <th class="date-column">
                            Date Ordered
                        </th>

                        <th class="date-column">
                            Date Delivered
                        </th>

                        <th class="supplier-column">
                            Supplier
                        </th>

                        <th class="customer-column">
                            Customer / Delivered To
                        </th>

                        <th class="liters-column">
                            Liters
                        </th>

                        <th class="freight-column">
                            Freight / Liter
                        </th>

                        <th class="income-column">
                            Income
                        </th>

                        <th class="driver-column">
                            Driver Name
                        </th>

                        <th class="salary-column">
                            Driver Salary
                        </th>

                        <th class="date-column">
                            Date Paid Driver
                        </th>

                        <th class="expense-column">
                            Other Expenses
                        </th>

                        <th class="net-income-column">
                            Net Income
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($groupedRecords as $tanker => $tankerRecords)
                        @php
                            $tankerLiters = $tankerRecords->sum(
                                fn ($record): float =>
                                    (float) (
                                        $record->customerPurchase
                                            ?->total_liters ?? 0
                                    )
                            );

                            $tankerIncome = $tankerRecords->sum(
                                fn ($record): float =>
                                    (float) $record->getFreightIncome()
                            );

                            $tankerDriverSalary = $tankerRecords->sum(
                                fn ($record): float =>
                                    (float) ($record->driver_salary ?? 0)
                            );

                            $tankerOtherExpenses = $tankerRecords->sum(
                                fn ($record): float =>
                                    (float) ($record->other_expenses ?? 0)
                            );

                            $tankerNetIncome = $tankerRecords->sum(
                                fn ($record): float =>
                                    (float) ($record->net_income ?? 0)
                            );
                        @endphp

                        <tr class="tanker-row">
                            <td colspan="12">
                                Tanker:
                                {{ $tanker }}

                                &nbsp; | &nbsp;

                                Delivery Records:
                                {{ number_format($tankerRecords->count()) }}
                            </td>
                        </tr>

                        @foreach ($tankerRecords as $record)
                            @php
                                $purchase = $record->customerPurchase;

                                $freightPerLiter =
                                    $record->getFreightPerLiter();

                                $freightIncome =
                                    $record->getFreightIncome();
                            @endphp

                            <tr>
                                <td class="center">
                                    {{ $purchase?->date_ordered
                                        ?->format('m/d/Y') ?? '-' }}
                                </td>

                                <td class="center">
                                    {{ $record->date_delivered
                                        ?->format('m/d/Y') ?? '-' }}
                                </td>

                                <td class="left">
                                    {{ $purchase?->supplier ?: '-' }}
                                </td>

                                <td class="left">
                                    {{ $purchase?->customer ?: '-' }}
                                </td>

                                <td class="amount">
                                    {{ $number(
                                        $purchase?->total_liters ?? 0
                                    ) }}
                                </td>

                                <td class="amount">
                                    {{ $money($freightPerLiter) }}
                                </td>

                                <td class="amount {{ $amountClass($freightIncome) }}">
                                    {{ $money($freightIncome) }}
                                </td>

                                <td class="left">
                                    {{ $record->driver_name ?: '-' }}
                                </td>

                                <td class="amount {{ $amountClass($record->driver_salary) }}">
                                    {{ $money($record->driver_salary ?? 0) }}
                                </td>

                                <td class="center">
                                    {{ $record->date_paid_driver
                                        ?->format('m/d/Y') ?? '-' }}
                                </td>

                                <td class="amount {{ $amountClass($record->other_expenses) }}">
                                    {{ $money($record->other_expenses ?? 0) }}
                                </td>

                                <td class="amount {{ $amountClass($record->net_income) }}">
                                    {{ $money($record->net_income ?? 0) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="tanker-total-row">
                            <td colspan="4" class="left">
                                TOTAL — {{ $tanker }}
                            </td>

                            <td class="amount">
                                {{ $number($tankerLiters) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($tankerIncome) }}">
                                {{ $money($tankerIncome) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($tankerDriverSalary) }}">
                                {{ $money($tankerDriverSalary) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($tankerOtherExpenses) }}">
                                {{ $money($tankerOtherExpenses) }}
                            </td>

                            <td class="amount {{ $amountClass($tankerNetIncome) }}">
                                {{ $money($tankerNetIncome) }}
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="12">
                                No tanker records found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($records->isNotEmpty())
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="4" class="left">
                                GRAND TOTAL
                            </td>

                            <td class="amount">
                                {{ $number($grandLiters) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($grandIncome) }}">
                                {{ $money($grandIncome) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($grandDriverSalary) }}">
                                {{ $money($grandDriverSalary) }}
                            </td>

                            <td></td>

                            <td class="amount {{ $amountClass($grandOtherExpenses) }}">
                                {{ $money($grandOtherExpenses) }}
                            </td>

                            <td class="amount {{ $amountClass($grandNetIncome) }}">
                                {{ $money($grandNetIncome) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="footer-note">
            <span>
                Generated on {{ now()->format('m/d/Y h:i A') }}
            </span>

            <span>
                EDZELVOR FUEL TRADING • Tanker Summary Report
            </span>
        </div>

        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    </div>
</div>
</body>
</html>