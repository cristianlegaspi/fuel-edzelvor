<!DOCTYPE html>
<html>
<head>
    <title>Customer Net Income Summary - {{ $year }}</title>

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
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        .page::before {
            content: "";
            position: absolute;
            top: -70px;
            right: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(97, 185, 70, 0.10);
            z-index: 0;
        }

        .page::after {
            content: "";
            position: absolute;
            bottom: -95px;
            left: -80px;
            width: 390px;
            height: 230px;
            border-radius: 50%;
            background: rgba(7, 89, 165, 0.10);
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
            min-height: 185mm;
            display: flex;
            flex-direction: column;
        }

        .print-actions {
            text-align: right;
            margin-bottom: 10px;
        }

        .print-actions button {
            padding: 8px 14px;
            border: none;
            background: var(--brand-blue);
            color: #ffffff;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        .header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 16px;
            margin-bottom: 9px;
            border: 1px solid var(--border);
            border-left: 8px solid var(--brand-green);
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #f4fbf1 50%, #eef7ff 100%);
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .logo-wrap {
            width: 66px;
            height: 66px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
            padding: 5px;
        }

        .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            background: var(--brand-green-light);
            color: var(--brand-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
        }

        .company-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--brand-blue-dark);
            margin: 0;
            line-height: 1.1;
        }

        .company-subtitle {
            font-size: 8.5px;
            color: var(--brand-green);
            text-transform: uppercase;
            letter-spacing: 1.7px;
            font-weight: bold;
            margin-top: 4px;
        }

        .document-box {
            text-align: right;
            flex: 0 0 auto;
        }

        .document-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--brand-blue);
            line-height: 1.1;
        }

        .document-subtitle {
            margin-top: 5px;
            font-size: 8.5px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .summary-card {
            border: 1px solid var(--border);
            border-radius: 9px;
            overflow: hidden;
            margin-bottom: 9px;
            background: #ffffff;
        }

        .summary-header {
            background: var(--brand-blue);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 6px 9px;
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
            width: 155px;
            color: var(--muted);
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .summary-value {
            font-weight: bold;
            color: var(--text);
        }

        .section-title {
            margin: 8px 0 5px;
            padding: 6px 9px;
            background: linear-gradient(90deg, var(--brand-blue), var(--brand-green));
            color: #ffffff;
            border-radius: 7px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.7px;
        }

        .table-holder {
            width: 100%;
            overflow: visible;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 5px 5px;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        th {
            background: #eaf2fb;
            color: var(--brand-blue-dark);
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            font-size: 7.7px;
            line-height: 1.15;
        }

        td {
            font-size: 8.2px;
            line-height: 1.2;
            background: #ffffff;
        }

        .customer-cell {
            width: 14%;
            font-weight: 800;
            color: var(--brand-blue-dark);
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .center {
            text-align: center;
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

        .total-row td {
            font-weight: 800;
            background: #f1f8ee;
            color: var(--brand-blue-dark);
        }

        .footer-note {
            margin-top: auto;
            padding-top: 8px;
            border-top: 2px solid var(--brand-green);
            display: flex;
            justify-content: space-between;
            gap: 10px;
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
                padding: 0;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
            }

            .content {
                min-height: 196mm;
            }

            .print-actions {
                display: none;
            }

            .header-card {
                padding: 12px 14px;
                margin-bottom: 8px;
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
                font-size: 7.1px;
                padding: 4px 4px;
            }

            td {
                font-size: 7.5px;
                padding: 4px 4px;
            }

            .section-title {
                margin-top: 7px;
                margin-bottom: 4px;
                padding: 5px 8px;
            }

            .table-holder {
                page-break-inside: avoid;
            }

            .header-card,
            .summary-card,
            .section-title,
            th,
            td,
            .total-row td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table,
            tr,
            td,
            th {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
<div class="page">
    <div class="content">

        <div class="print-actions">
            <button onclick="window.print()">Print Customer Net Income</button>
        </div>

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

                $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFile));
            }

            $money = fn ($amount) => '₱ ' . number_format((float) $amount, 2);

            $amountClass = function ($amount): string {
                $amount = (float) $amount;

                if ($amount > 0) {
                    return 'positive';
                }

                if ($amount < 0) {
                    return 'negative';
                }

                return 'zero';
            };

            $customerFilterLabel = strtolower((string) $selectedCustomer) === 'all'
                ? 'All Customers'
                : $selectedCustomer;

            $monthGroups = [
                'January to June' => array_intersect_key($months, array_flip([1, 2, 3, 4, 5, 6])),
                'July to December' => array_intersect_key($months, array_flip([7, 8, 9, 10, 11, 12])),
            ];
        @endphp

        <div class="header-card">
            <div class="brand-area">
                <div class="logo-wrap">
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Company Logo">
                    @else
                        <div class="logo-placeholder">G</div>
                    @endif
                </div>

                <div>
                    <div class="company-title">EDZELVOR FUEL TRADING</div>
                    <div class="company-subtitle">Cleaner Solutions • Greener Tomorrow</div>
                </div>
            </div>

            <div class="document-box">
                <div class="document-title">Customer Net Income Summary Report</div>
                <div class="document-subtitle">Year {{ $year }} • {{ $customerFilterLabel }}</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-header">Report Summary</div>

            <table class="summary-table">
                <tr>
                    <td class="summary-label">Year</td>
                    <td class="summary-value">{{ $year }}</td>

                    <td class="summary-label">Customer Filter</td>
                    <td class="summary-value">{{ $customerFilterLabel }}</td>
                </tr>

                <tr>
                    <td class="summary-label">Total Net Income</td>
                    <td class="summary-value {{ $amountClass($grandTotal['net_income'] ?? 0) }}">
                        {{ $money($grandTotal['net_income'] ?? 0) }}
                    </td>

                    <td class="summary-label">Total Balance</td>
                    <td class="summary-value {{ $amountClass($grandTotal['balance'] ?? 0) }}">
                        {{ $money($grandTotal['balance'] ?? 0) }}
                    </td>
                </tr>

                <tr>
                    <td class="summary-label">Generated Date</td>
                    <td class="summary-value">{{ now()->format('m/d/Y h:i A') }}</td>

                    <td class="summary-label">Total Customers</td>
                    <td class="summary-value">{{ number_format($summaryRows->count()) }}</td>
                </tr>
            </table>
        </div>

        @foreach ($monthGroups as $groupTitle => $groupMonths)
            <div class="section-title">
                Customer Net Income Details - {{ $groupTitle }}
            </div>

            <div class="table-holder">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" class="customer-cell">Customer Name</th>
                            <th colspan="2">Total Per Customer</th>

                            @foreach ($groupMonths as $monthName)
                                <th colspan="2">{{ $monthName }}</th>
                            @endforeach
                        </tr>

                        <tr>
                            <th>Net Income</th>
                            <th>Balance</th>

                            @foreach ($groupMonths as $monthName)
                                <th>Net Income</th>
                                <th>Balance</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($summaryRows as $row)
                            <tr>
                                <td class="customer-cell">
                                    {{ $row['customer'] }}
                                </td>

                                <td class="amount {{ $amountClass($row['total_net_income'] ?? 0) }}">
                                    {{ $money($row['total_net_income'] ?? 0) }}
                                </td>

                                <td class="amount {{ $amountClass($row['total_balance'] ?? 0) }}">
                                    {{ $money($row['total_balance'] ?? 0) }}
                                </td>

                                @foreach ($groupMonths as $monthNo => $monthName)
                                    <td class="amount {{ $amountClass($row['monthly'][$monthNo]['net_income'] ?? 0) }}">
                                        {{ $money($row['monthly'][$monthNo]['net_income'] ?? 0) }}
                                    </td>

                                    <td class="amount {{ $amountClass($row['monthly'][$monthNo]['balance'] ?? 0) }}">
                                        {{ $money($row['monthly'][$monthNo]['balance'] ?? 0) }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + (count($groupMonths) * 2) }}" class="center">
                                    No customer net income records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="total-row">
                            <td class="customer-cell">GRAND TOTAL</td>

                            <td class="amount {{ $amountClass($grandTotal['net_income'] ?? 0) }}">
                                {{ $money($grandTotal['net_income'] ?? 0) }}
                            </td>

                            <td class="amount {{ $amountClass($grandTotal['balance'] ?? 0) }}">
                                {{ $money($grandTotal['balance'] ?? 0) }}
                            </td>

                            @foreach ($groupMonths as $monthNo => $monthName)
                                <td class="amount {{ $amountClass($grandMonthly[$monthNo]['net_income'] ?? 0) }}">
                                    {{ $money($grandMonthly[$monthNo]['net_income'] ?? 0) }}
                                </td>

                                <td class="amount {{ $amountClass($grandMonthly[$monthNo]['balance'] ?? 0) }}">
                                    {{ $money($grandMonthly[$monthNo]['balance'] ?? 0) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        <div class="footer-note">
            <span>Generated on {{ now()->format('m/d/Y h:i A') }}</span>
            <span>EDZELVOR FUEL TRADING • Customer Net Income Summary Report</span>
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