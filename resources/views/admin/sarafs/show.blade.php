{{-- resources/views/admin/sarafs/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Saraf Details - ' . $saraf->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('vendor/fontawesome/7.3.1/css/all.min.css') }}">
    <style>
        :root {
            --primary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(0,0,0,0.02), 0 8px 32px rgba(0,0,0,0.04);
            --shadow-hover: 0 1px 3px rgba(0,0,0,0.02), 0 12px 48px rgba(0,0,0,0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-header h1 .accent {
            background: linear-gradient(135deg, var(--primary-color), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .page-header .subtitle {
            color: var(--gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-header .header-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .page-header .header-actions .btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.3s ease;
        }
        .page-header .header-actions .btn:hover {
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .stat-card.purple::before { background: linear-gradient(90deg, var(--primary-color), #a78bfa); }
        .stat-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card.red::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .stat-card .stat-icon.purple {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }
        .stat-card .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }
        .stat-card .stat-icon.red {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #dc2626;
        }
        .stat-card .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
        }
        .stat-card .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }
        .stat-card .stat-value .currency-sm {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-400);
        }

        .info-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
        }
        .info-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .info-card .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-card .info-item .label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-400);
        }
        .info-card .info-item .value {
            font-size: 0.95rem;
            color: var(--gray-800);
            font-weight: 600;
        }

        .export-box {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        .export-box:hover {
            box-shadow: var(--shadow-hover);
        }
        .currency-summary-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .currency-summary-box {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            background-color: var(--gray-50);
            text-align: center;
            transition: all 0.3s ease;
        }
        .currency-summary-box:hover {
            border-color: var(--primary-color);
            background-color: #ede9fe;
        }
        .balance-positive {
            color: var(--success-color) !important;
            font-weight: bold;
        }
        .balance-negative {
            color: var(--danger-color) !important;
            font-weight: bold;
        }
        .currency-flag {
            width: 24px;
            height: 16px;
            object-fit: cover;
            border-radius: 2px;
            margin-right: 4px;
        }

        .table-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .table-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .table-card .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .table-card .card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table-card .card-body {
            padding: 1.5rem;
        }

        .badge-credit {
            background: #d1fae5;
            color: #065f46;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .badge-debit {
            background: #fecaca;
            color: #991b1b;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background: rgba(139, 92, 246, 0.02);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .info-card .info-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">

    {{-- ─── PAGE HEADER ─── --}}
    <div class="page-header">
        <div>
            <h1>
                <i class="bi bi-currency-exchange" style="color: var(--primary-color);"></i>
                {{ __('ui.saraf') }} <span class="accent">{{ __('ui.details') }}</span>
            </h1>
            <p class="subtitle">
                <i class="bi bi-receipt me-1"></i>
                {{ $saraf->name }} · {{ $saraf->code }}
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.sarafs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    {{-- ─── STATS CARDS ─── --}}
    @php
        $totalCredit = 0;
        $totalDebit = 0;
        $totalBalance = 0;
        foreach ($summariesByCurrency as $summary) {
            $totalCredit += $summary->credit;
            $totalDebit += $summary->debit;
            $totalBalance += $summary->balance;
        }
    @endphp

    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="stat-top">
                <div class="stat-icon purple">
                    <i class="bi bi-bank"></i>
                </div>
                <div class="stat-value">{{ $saraf->name }}</div>
            </div>
            <div class="stat-label">{{ __('ui.saraf_name') }}</div>
        </div>

        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-icon green">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="stat-value">
                    <span class="currency-sm">$</span>{{ number_format($totalCredit, 2) }}
                </div>
            </div>
            <div class="stat-label">{{ __('ui.total_credit') }}</div>
        </div>

        <div class="stat-card red">
            <div class="stat-top">
                <div class="stat-icon red">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="stat-value">
                    <span class="currency-sm">$</span>{{ number_format($totalDebit, 2) }}
                </div>
            </div>
            <div class="stat-label">{{ __('ui.total_debit') }}</div>
        </div>

        <div class="stat-card blue">
            <div class="stat-top">
                <div class="stat-icon blue">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="stat-value" style="color: {{ $totalBalance >= 0 ? 'var(--success-color)' : 'var(--danger-color)' }};">
                    <span class="currency-sm">$</span>{{ number_format(abs($totalBalance), 2) }}
                </div>
            </div>
            <div class="stat-label">{{ $totalBalance >= 0 ? 'Net Credit' : 'Net Debit' }}</div>
        </div>
    </div>

    {{-- ─── INFO CARD ─── --}}
    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <div class="label">{{ __('ui.saraf_code') }}</div>
                <div class="value">{{ $saraf->code }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('ui.contact') }}</div>
                <div class="value">{{ $saraf->contact ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('ui.email') }}</div>
                <div class="value">{{ $saraf->email ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('ui.whatsapp') }}</div>
                <div class="value">{{ $saraf->whatsapp ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('ui.company') }}</div>
                <div class="value">{{ $saraf->company ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('ui.status') }}</div>
                <div class="value">
                    <span class="badge {{ $saraf->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $saraf->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
        @if ($saraf->address)
        <div class="mt-3">
            <div class="label">{{ __('ui.address') }}</div>
            <div class="value" style="font-weight: 400;">{{ $saraf->address }}</div>
        </div>
        @endif
        @if ($saraf->notes)
        <div class="mt-2">
            <div class="label">{{ __('ui.notes') }}</div>
            <div class="value" style="font-weight: 400;">{{ $saraf->notes }}</div>
        </div>
        @endif
    </div>

    {{-- ─── EXPORT SECTION ─── --}}
    <div class="export-box" id="export-area">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>{{ __('ui.account_summary') }}</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="exportImage">
                    <i class="bi bi-image"></i> {{ __('ui.image') }}
                </button>
                <button class="btn btn-sm btn-outline-danger" id="exportPdf">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <div class="border rounded p-3 mb-3" style="background: var(--gray-50);">
            <h6 class="mb-2"><i class="bi bi-wallet me-2"></i>{{ __('ui.balance_by_currency') }}</h6>
            <div class="currency-summary-row">
                @forelse($summariesByCurrency as $summary)
                @php
                    $balance = $summary->balance;
                    $balanceClass = $balance >= 0 ? 'balance-positive' : 'balance-negative';
                    $currencyFlag = strtolower(substr($summary->currency, 0, 2));
                @endphp
                <div class="currency-summary-box">
                    <div class="mb-1">
                        <strong>
                            @if (file_exists(public_path('assets/flags/' . $currencyFlag . '.svg')))
                                <img src="/assets/flags/{{ $currencyFlag }}.svg" class="currency-flag" alt="{{ $summary->currency }}">
                            @endif
                            {{ $summary->currency }}
                        </strong>
                    </div>
                    <div class="small">
                        <span class="text-success">CR: {{ number_format($summary->credit, 2) }}</span>
                    </div>
                    <div class="small">
                        <span class="text-danger">DR: {{ number_format($summary->debit, 2) }}</span>
                    </div>
                    <div class="fw-bold {{ $balanceClass }}">
                        Balance: {{ number_format($balance, 2) }}
                    </div>
                </div>
                @empty
                <div class="text-muted text-center py-3">{{ __('ui.no_transactions_found') }}</div> @endforelse
            </div>
        </div>
    </div>

    {{-- ─── TRANSACTIONS TABLE ─── --}}
    <div class="table-card">
    <div class="card-header">
        <h5>
            <i class="bi bi-list-ul" style="color: var(--primary-color);"></i>
            Transaction History
            <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; font-weight: 600;">
                {{ $saraf->transactions->count() ?? 0 }} transactions
            </span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm" id="transactions-table" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>{{ __('ui.amount') }}</th>
                        <th>{{ __('ui.currency') }}</th>
                        <th>{{ __('ui.type') }}</th>
                        <th>{{ __('ui.description') }}</th>
                        <th style="width: 160px;">{{ __('ui.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($saraf->transactions->sortByDesc('created_at') as $transaction)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ number_format($transaction->amount, 2) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $transaction->currency->code ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge {{ $transaction->transaction_type == 'credit' ? 'badge-credit' : 'badge-debit' }}">
                                    {{ ucfirst($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td>{{ $transaction->description ?? 'N/A' }}</td>
                            <td style="font-size: 0.8rem; color: var(--gray-500);">
                                {{ $transaction->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inboxes"
                                    style="font-size: 2rem; color: var(--gray-300); display: block; margin-bottom: 0.5rem;"></i>
                                <span class="text-muted">No transactions found for this saraf</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/html2canvas/html2canvas.min.js') }}"></script>
    <script src="{{ asset('vendor/jspdf/jspdf.umd.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.getElementById('exportImage').addEventListener('click', function() {
                const exportArea = document.getElementById('export-area');

                this.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';
                this.disabled = true;

                html2canvas(exportArea, {
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    scale: 2,
                    logging: false,
                    allowTaint: true,
                    width: exportArea.scrollWidth,
                    height: exportArea.scrollHeight,
                    windowWidth: exportArea.scrollWidth,
                    windowHeight: exportArea.scrollHeight
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = '{{ $saraf->code }}-summary.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();

                    this.innerHTML = '<i class="bi bi-image"></i> Image';
                    this.disabled = false;
                }).catch(error => {
                    console.error('Export error:', error);
                    Swal.fire('Error', 'Failed to generate image. Please try again.', 'error');
                    this.innerHTML = '<i class="bi bi-image"></i> Image';
                    this.disabled = false;
                });
            });

            document.getElementById('exportPdf').addEventListener('click', function() {
                const exportArea = document.getElementById('export-area');

                this.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';
                this.disabled = true;

                html2canvas(exportArea, {
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    scale: 2,
                    logging: false,
                    allowTaint: true,
                    width: exportArea.scrollWidth,
                    height: exportArea.scrollHeight,
                    windowWidth: exportArea.scrollWidth,
                    windowHeight: exportArea.scrollHeight
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');

                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const imgProps = pdf.getImageProperties(imgData);
                    const imgWidth = pageWidth - 20;
                    const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

                    pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                    pdf.save('{{ $saraf->code }}-summary.pdf');

                    this.innerHTML = '<i class="bi bi-file-pdf"></i> PDF';
                    this.disabled = false;
                }).catch(error => {
                    console.error('Export error:', error);
                    Swal.fire('Error', 'Failed to generate PDF. Please try again.', 'error');
                    this.innerHTML = '<i class="bi bi-file-pdf"></i> PDF';
                    this.disabled = false;
                });
            });

        });
    </script>
@endsection
