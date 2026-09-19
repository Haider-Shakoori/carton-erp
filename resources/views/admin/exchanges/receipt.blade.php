<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ui.exchange_receipt') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap-5.3.2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link href="{{ asset('vendor/fonts/poppins/poppins.css') }}" rel="stylesheet">
    <script src="{{ asset('vendor/html2canvas/html2canvas.min.js') }}"></script>
    <script src="{{ asset('vendor/jspdf/jspdf.umd.min.js') }}"></script>
    <style>
        /* Scoped styles - only affect elements within receipt-container */
        #receipt-container {
            font-family: 'Poppins', sans-serif;
            padding: 1rem;
        }
        #receipt-container .receipt-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 1.25rem; /* reduced from 2.5rem */
            max-width: 500px; /* reduced from 720px */
            margin: auto;
            border-top: 6px solid #0d6efd;
            position: relative;
            overflow: hidden;
        }
        #receipt-container .receipt-box::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: rgba(108, 92, 231, 0.1);
            border-radius: 0 0 0 100%;
        }
        #receipt-container .receipt-header {
            border-bottom: 2px dashed #e0e0e0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #receipt-container .receipt-title {
            color: #2d3436;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        #receipt-container .receipt-date {
            color: #636e72;
            font-size: 0.85rem;
        }
        #receipt-container .receipt-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        #receipt-container .receipt-section-title {
            color: #0d6efd;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #receipt-container .text-label {
            color: #636e72;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        #receipt-container .text-value {
            font-weight: 500;
            color: #2d3436;
            font-size: 1rem;
        }
        #receipt-container .amount-highlight {
            font-weight: 600;
            font-size: 1.1rem;
            color: #0d6efd;
        }
        #receipt-container .profit-highlight {
            font-weight: 600;
            color: #00b894;
        }
        #receipt-container .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: #e3f9e5;
            color: #00b894;
        }
        #receipt-container .footer-note {
            text-align: center;
            font-size: 0.85rem;
            color: #636e72;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #e0e0e0;
        }
        #receipt-container .company-name {
            color: #0d6efd;
            font-weight: 600;
        }
        #receipt-container .divider-dot {
            color: #b2bec3;
            margin: 0 0.5rem;
        }
        #receipt-container .download-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1rem auto;
            max-width: 720px;
        }
        #receipt-container .btn-download {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        #receipt-container .btn-download:hover {
            background: #5649c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        #receipt-container .btn-download:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Wrap everything in a container with specific ID -->
    <div id="receipt-container">
        <div class="download-buttons">
            <button class="btn-download" id="download-pdf">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download PDF
            </button>
            <button class="btn-download" id="download-image">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.502 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                    <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.17 1.821l-.865-.433A1 1 0 0 0 13 13.998V5a1 1 0 0 0-1-1h-10a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h10a1 1 0 0 0 .831-.415l.625.813A1.98 1.98 0 0 1 14 13z"/>
                </svg>
                Download Image
            </button>
        </div>

        <div class="receipt-box" id="receipt-content">
            <div class="receipt-header">
                <div>
                    <img src="{{ asset('images/' . $setting->logo) }}" height="60" alt="Logo">
                </div>
                <div class="text-end">
                    <div class="receipt-title">{{ __('ui.exchange_sale_receipt') }}</div>
                    <div class="receipt-date">{{ $exchange->created_at->format('F j, Y \a\t g:i A') }}</div>
                </div>
            </div>

            <div class="receipt-section">
                <div class="receipt-section-title">{{ __('ui.customer_information') }}</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.customer_code') }}</div>
                        <div class="text-value">{{ $exchange->customerAccount->code }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.customer_name') }}</div>
                        <div class="text-value">{{ $exchange->customerAccount->name }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.contact') }}</div>
                        <div class="text-value">{{ $exchange->customerAccount->contact }}</div>
                    </div>
                </div>
            </div>

            <div class="receipt-section">
                <div class="receipt-section-title">{{ __('ui.transaction_details') }}</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.base_amount') }}</div>
                        <div class="amount-highlight">{{ number_format($exchange->base_amount, 0) }} {{ $exchange->baseCurrency->symbol }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.exchange_rate') }}</div>
                        <div class="text-value">{{ number_format($exchange->rate, 2) }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-label">{{ __('ui.amount_received') }}</div>
                        <div class="amount-highlight text-danger">
                             - {{ number_format($exchange->target_amount, 0) }} {{ $exchange->targetCurrency->symbol }}
                        </div>
                    </div>
                </div>
            </div>

            @if ($exchange->note)
                <div class="receipt-section">
                    <div class="receipt-section-title">Note</div>
                    <div class="text-value">{{ $exchange->note }}</div>
                </div>
            @endif

            <div class="footer-note">
                <span class="company-name">{{ $setting->company_name }}</span>
                <span class="divider-dot">•</span>
                {{ $setting->email }}
                <span class="divider-dot">•</span>
                {{ $setting->contact }}
                <div class="mt-2">Thank you for your business!</div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const receipt = document.getElementById('receipt-content');

            html2canvas(receipt, {
                scale: 2,
                logging: false,
                useCORS: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 190;
                const imgHeight = canvas.height * imgWidth / canvas.width;

                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save('exchange-receipt.pdf');
            });
        });

        document.getElementById('download-image').addEventListener('click', function() {
            const receipt = document.getElementById('receipt-content');

            html2canvas(receipt, {
                scale: 2,
                logging: false,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'exchange-receipt.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    </script>
</body>
</html>
