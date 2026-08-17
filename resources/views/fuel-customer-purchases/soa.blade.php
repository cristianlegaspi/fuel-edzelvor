<!DOCTYPE html>
<html>
<head>
    <title>Customer SOA - {{ $purchase->sales_order_no ?? 'SOA' }}</title>

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
            --soft-bg: #f8fafc;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef5f1;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
        }

        body {
            padding: 10px;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 8mm;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        .page::before {
            content: "";
            position: absolute;
            top: -70px;
            right: -80px;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background: rgba(97, 185, 70, 0.10);
            z-index: 0;
        }

        .page::after {
            content: "";
            position: absolute;
            bottom: -95px;
            left: -80px;
            width: 360px;
            height: 210px;
            border-radius: 50%;
            background: rgba(7, 89, 165, 0.10);
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .print-actions {
            text-align: right;
            margin-bottom: 8px;
        }

        .print-actions button {
            padding: 7px 12px;
            border: none;
            background: var(--brand-blue);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
        }

        .header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 8px;
            border: 1px solid var(--border);
            border-left: 6px solid var(--brand-green);
            border-radius: 10px;
            background: linear-gradient(135deg, #ffffff 0%, #f4fbf1 50%, #eef7ff 100%);
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .logo-wrap {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
            padding: 4px;
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
            font-size: 18px;
            font-weight: bold;
        }

        .company-title {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--brand-blue-dark);
            margin: 0;
            line-height: 1.1;
        }

        .company-subtitle {
            font-size: 8px;
            color: var(--brand-green);
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-weight: bold;
            margin-top: 2px;
        }

        .document-box {
            text-align: right;
            flex: 0 0 auto;
        }

        .document-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--brand-blue);
            line-height: 1.1;
        }

        .document-subtitle {
            margin-top: 3px;
            font-size: 7.5px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 7px;
            background: #ffffff;
        }

        .summary-header {
            background: var(--brand-blue);
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 7px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: none;
            border-bottom: 1px solid #edf2f7;
            padding: 4px 6px;
            font-size: 8px;
            vertical-align: middle;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            width: 135px;
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
            margin: 7px 0 4px;
            padding: 4px 7px;
            background: linear-gradient(90deg, var(--brand-blue), var(--brand-green));
            color: #ffffff;
            border-radius: 6px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 3px 4px;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        th {
            background: #eaf2fb;
            color: var(--brand-blue-dark);
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            font-size: 6.8px;
            line-height: 1.1;
        }

        td {
            font-size: 7.4px;
            line-height: 1.15;
            background: #ffffff;
        }

        .center {
            text-align: center;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .total-row td {
            font-weight: 800;
            background: #f1f8ee;
            color: var(--brand-blue-dark);
        }

        .payment-table th {
            background: #eef7ff;
        }

        .alert {
            border: 1px solid #991b1b;
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            font-weight: bold;
            border-radius: 8px;
        }

        .footer-note {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 2px solid var(--brand-green);
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: var(--muted);
            font-size: 7px;
        }

        @media print {
            @page {
                size: A4 portrait;
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
                font-size: 7.5px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                page-break-after: avoid;
            }

            .print-actions {
                display: none;
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
            <button onclick="window.print()">Print Customer SOA</button>
        </div>

        @php
            use App\Models\FuelCustomerPurchase;
            use Carbon\Carbon;

            $purchase = $purchase ?? $record ?? null;

            if (is_numeric($purchase)) {
                $purchase = FuelCustomerPurchase::query()
                    ->with(['items', 'payments', 'salesOrder'])
                    ->find($purchase);
            }

            if (! $purchase) {
                $routeRecord = request()->route('record') ?? request()->route('id');

                if ($routeRecord instanceof FuelCustomerPurchase) {
                    $purchase = $routeRecord;
                } elseif (is_numeric($routeRecord)) {
                    $purchase = FuelCustomerPurchase::query()
                        ->with(['items', 'payments', 'salesOrder'])
                        ->find($routeRecord);
                }
            }

            if ($purchase) {
                $purchase->loadMissing(['items', 'payments', 'salesOrder']);
            }

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

            $items = $purchase?->items?->values() ?? collect();
            $payments = $purchase?->payments?->values() ?? collect();

            $formatDate = function ($date) {
                if (! $date) {
                    return '-';
                }

                return Carbon::parse($date)->format('d-M');
            };

            $formatFullDate = function ($date) {
                if (! $date) {
                    return '-';
                }

                return Carbon::parse($date)->format('m/d/Y');
            };

            $money = fn ($amount) => '₱ ' . number_format((float) $amount, 2);
            $number = fn ($amount) => number_format((float) $amount, 2);

            $totalNoPurchaseOrder = (int) ($purchase?->total_no_purchase_order ?? 1);

            $totalPayables = (float) (
                $purchase?->total_payables
                ?? $items->sum('payables')
            );

            $totalPaid = (float) (
                $purchase?->payment_amount
                ?? $payments->sum('amount')
            );

            $remainingBalance = (float) (
                $purchase?->remaining_balance
                ?? max($totalPayables - $totalPaid, 0)
            );
        @endphp

        @if (! $purchase)
            <div class="alert">
                No customer purchase data found. Please check the SOA route or selected customer purchase record.
            </div>
        @else

            {{-- HEADER --}}
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
                    <div class="document-title">Statement of Account</div>
                    <div class="document-subtitle">Customer SOA • A4 Portrait</div>
                </div>
            </div>

            {{-- REQUIRED SUMMARY ONLY --}}
            <div class="summary-card">
                <div class="summary-header">Customer Statement Summary</div>

                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Customer Name</td>
                        <td class="summary-value">{{ $purchase->customer ?? '-' }}</td>

                        <td class="summary-label">Date SOA</td>
                        <td class="summary-value">{{ now()->format('m/d/Y') }}</td>
                    </tr>

                    <tr>
                        <td class="summary-label">Total No. of Purchase Order</td>
                        <td class="summary-value">{{ number_format($totalNoPurchaseOrder) }}</td>

                        <td class="summary-label">Total Amount Payables</td>
                        <td class="summary-value">{{ $money($totalPayables) }}</td>
                    </tr>

                    <tr>
                        <td class="summary-label">Total Paid Payments</td>
                        <td class="summary-value">{{ $money($totalPaid) }}</td>

                        <td class="summary-label">Remaining Balance</td>
                        <td class="summary-value">{{ $money($remainingBalance) }}</td>
                    </tr>
                </table>
            </div>

            {{-- PURCHASE DETAILS --}}
            <div class="section-title">Purchase Details</div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Sales Order No.</th>
                        <th style="width: 9%;">ATL Date</th>
                        <th style="width: 12%;">Order No.</th>
                        <th style="width: 8%;">ATL No.</th>
                        <th style="width: 13%;">Fuel Product</th>
                        <th style="width: 12%;">Liters</th>
                        <th style="width: 12%;">Selling Price</th>
                        <th style="width: 12%;">EWT</th>
                        <th style="width: 12%;">Total Amount Payables</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="center">
                                {{ $index === 0 ? ($purchase->sales_order_no ?? '-') : '' }}
                            </td>

                            <td class="center">
                                {{ $index === 0 ? $formatDate($purchase->atl_date) : '' }}
                            </td>

                            <td class="center">
                                {{ $index === 0 ? ($purchase->order_no_details ?? '-') : '' }}
                            </td>

                            <td class="center">
                                {{ $index === 0 ? ($purchase->atl_no ?? '-') : '' }}
                            </td>

                            <td class="center">
                                {{ $item->fuel_product ?? '-' }}
                            </td>

                            <td class="amount">
                                {{ $number($item->liters ?? 0) }}
                            </td>

                            <td class="amount">
                                {{ $money($item->selling_price ?? 0) }}
                            </td>

                            <td class="amount">
                                {{ $money($item->less_ewt_rate ?? 0) }}
                            </td>

                            <td class="amount">
                                {{ $money($item->payables ?? 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="center">
                                No customer purchase items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" class="amount">Total</td>
                        <td class="amount">{{ $number($items->sum('liters')) }}</td>
                        <td></td>
                        <td class="amount">{{ $money($items->sum('less_ewt_rate')) }}</td>
                        <td class="amount">{{ $money($totalPayables) }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- PAYMENT HISTORY AT THE BOTTOM --}}
            <div class="section-title">Payment History</div>

            <table class="payment-table">
                <thead>
                    <tr>
                        <th style="width: 13%;">Date</th>
                        <th style="width: 15%;">Amount Paid</th>
                        <th style="width: 15%;">Bank Name</th>
                        <th style="width: 15%;">Cheque No.</th>
                        <th style="width: 22%;">Transaction / Reference No.</th>
                        <th style="width: 20%;">Remarks</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="center">
                                {{ $formatFullDate($payment->payment_date) }}
                            </td>

                            <td class="amount">
                                {{ $money($payment->amount ?? 0) }}
                            </td>

                            <td class="center">
                                {{ $payment->bank_name ?? $payment->bank ?? '-' }}
                            </td>

                            <td class="center">
                                {{ $payment->cheque_no ?? $payment->check_no ?? '-' }}
                            </td>

                            <td class="center">
                                {{ $payment->transaction_reference_no ?? $payment->transaction_no ?? $payment->reference_no ?? '-' }}
                            </td>

                            <td>
                                {{ $payment->remarks ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="center">
                                No payment history found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td class="amount">Total Paid</td>
                        <td class="amount">{{ $money($totalPaid) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="footer-note">
                <span>Generated on {{ now()->format('m/d/Y h:i A') }}</span>
                <span>EDZELVOR FUEL TRADING • Customer Statement of Account</span>
            </div>

        @endif

        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>

    </div>
</div>
</body>
</html>