@extends('layouts.admin.base')

@section('title', 'Remittance Receipt')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('vendor/fontawesome/7.3.1/css/all.min.css') }}">
<style>
    html { font-size: 15px; background: #f0f2f5; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .export-box {
        background: #fff;
        max-width: 750px;
        margin: 2rem auto;
        padding: 2rem 2.5rem;
        border-radius: 12px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #ddd;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
    }

    .export-header img {
        height: 50px;
    }

    .export-header h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1c2c52;
    }

    .section-card {
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 1rem;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 0.5rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f2f2f2;
    }

    .info-label {
        font-weight: 600;
        color: #333;
    }

    .info-value {
        color: #555;
        text-align: right;
    }

    .export-footer {
        border-top: 1px solid #ddd;
        margin-top: 2rem;
        padding-top: 1rem;
        text-align: center;
        font-size: 0.85rem;
        color: #666;
    }

    .export-actions {
        max-width: 750px;
        margin: 1rem auto;
        text-align: right;
    }

    .export-actions button {
        margin-left: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="export-actions">
    <button class="btn btn-outline-primary btn-sm" id="exportImage"><i class="fas fa-image"></i> {{ __('ui.export_image') }}</button>
    <button class="btn btn-outline-danger btn-sm" id="exportPdf"><i class="fas fa-file-pdf"></i> {{ __('ui.export_pdf') }}</button>
    @if($remittance->receipt_file)
    <a href="{{ url($remittance->receipt_file) }}" class="btn btn-sm btn-outline-info" target="_blank">
        <i class="bi bi-file-earmark-image"></i> <span>Download Receipt</span>
    </a>
    @endif
</div>

<div id="export-area" class="export-box">
    <div class="export-header">
        <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}">
        <div>
            <h4>Remittance Receipt</h4>
            <div style="font-size: 0.9rem; color: #888;">Generated: {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><i class="fas fa-user-circle me-2"></i> Customer & Remittance Info</div>

        <div class="info-item">
            <div class="info-label">{{ __('ui.customer_name_colon') }}</div>
            <div class="info-value">{{ $remittance->account->name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('ui.amount_colon') }}</div>
            <div class="info-value">{{ number_format($remittance->amount, 2) }} {{ $remittance->currency->code }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Bank Name:</div>
            <div class="info-value">{{ $remittance->bank_name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Account Holder:</div>
            <div class="info-value">{{ $remittance->account_holder }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Account Number:</div>
            <div class="info-value">{{ $remittance->bank_account_number }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('ui.status_colon') }}</div>
            <div class="info-value">
                <span class="badge bg-{{ $remittance->status === 'pending' ? 'warning' : 'success' }}">
                    {{ ucfirst($remittance->status) }}
                </span>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">Created By:</div>
            <div class="info-value">{{ $remittance->creator->name ?? '-' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Created At:</div>
            <div class="info-value">{{ $remittance->created_at->format('Y-m-d H:i:s') }}</div>
        </div>
        @if ($remittance->note)
        <div class="info-item">
            <div class="info-label">{{ __('ui.note_colon') }}</div>
            <div class="info-value">{{ $remittance->note }}</div>
        </div> @endif
    </div>

    <div class="export-footer">
    {{ $setting->company_name }} · 📧 {{ $setting->email }} · 📞 {{ $setting->contact }}
    </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/html2canvas/html2canvas.min.js') }}"></script>
    <script src="{{ asset('vendor/jspdf/jspdf.umd.min.js') }}"></script>
    <script>
        document.getElementById('exportImage').addEventListener('click', () => {
            html2canvas(document.getElementById('export-area'), {
                backgroundColor: '#ffffff',
                useCORS: true,
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'remittance-{{ $remittance->id }}.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        });

        document.getElementById('exportPdf').addEventListener('click', async () => {
            const {
                jsPDF
            } = window.jspdf;
            const canvas = await html2canvas(document.getElementById('export-area'), {
                backgroundColor: '#ffffff',
                useCORS: true,
                scale: 2
            });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const width = pdf.internal.pageSize.getWidth();
            const height = (canvas.height * width) / canvas.width;
            pdf.addImage(imgData, 'PNG', 0, 10, width, height);
            pdf.save('remittance-{{ $remittance->id }}.pdf');
        });
    </script>
@endsection
