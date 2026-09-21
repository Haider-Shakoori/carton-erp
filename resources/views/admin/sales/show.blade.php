{{-- resources/views/admin/sales/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Sale Order #' . $sale->sale_no)

@section('css')
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        :root {
            --sale-primary: #4f46e5;
            --sale-primary-dark: #4338ca;
            --sale-success: #10b981;
            --sale-danger: #ef4444;
            --sale-warning: #f59e0b;
            --sale-gray-50: #f8fafc;
            --sale-gray-100: #f1f5f9;
            --sale-gray-200: #e2e8f0;
            --sale-gray-300: #cbd5e1;
            --sale-gray-400: #94a3b8;
            --sale-gray-500: #64748b;
            --sale-gray-600: #475569;
            --sale-gray-700: #334155;
            --sale-gray-800: #1e293b;
            --sale-gray-900: #0f172a;
            --sale-radius: 16px;
            --sale-radius-sm: 10px;
            --sale-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            --sale-shadow-hover: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.08);
        }
        /* ─── Horizontal Confirmation Modal Styles ─── */
        #confirmModal .modal-dialog {
            max-width: 95vw;
        }

        /* ─── BOM Calculator Modal Styles ─── */
        .bom-calculator-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            max-height: 95vh;
        }
        .bom-calculator-modal .modal-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 1.25rem 2rem;
            flex-shrink: 0;
        }
        .bom-calculator-modal .modal-body {
            padding: 1.25rem 2rem;
            background: #fafbfc;
            flex: 1;
            overflow-y: auto;
            max-height: calc(95vh - 180px);
        }
        .bom-calculator-modal .modal-footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 0.75rem 2rem;
            border-radius: 0 0 20px 20px;
            flex-shrink: 0;
        }

        .bom-item-row-modal {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            position: relative;
            transition: all 0.3s ease;
        }
        .bom-item-row-modal:hover {
            border-color: #4f46e5;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
        }
        .bom-item-row-modal .remove-item-modal {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .bom-item-row-modal .remove-item-modal:hover {
            background: #fecaca;
            color: #dc2626;
        }
        .modal-item-counter {
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .modal-reel-dimensions-bar {
            background: #f0f9ff;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            margin-bottom: 0.5rem;
            display: none;
        }
        .modal-calculation-details {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            border: 1px solid #e5e7eb;
            display: none;
        }
        .modal-formula-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 600;
        }
        .modal-formula-badge.fixed { background: #f3f4f6; color: #6b7280; }
        .modal-formula-badge.carton_3d { background: #dbeafe; color: #1e40af; }
        .modal-formula-badge.cut_roll { background: #d1fae5; color: #065f46; }
        .modal-formula-badge.fixed_percentage { background: #fef3c7; color: #92400e; }
        .modal-formula-badge.fixed_rate { background: #fce4ec; color: #dc2626; }

        .modal-formula-config-section {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
        }
        .modal-formula-config-card {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .modal-formula-config-card .config-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f1f5f9;
        }
        .modal-formula-config-card .config-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }
        .modal-formula-config-card .config-title i {
            color: #4f46e5;
            margin-right: 0.5rem;
        }
        .modal-formula-config-card .config-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .modal-formula-config-card .formula-hint-box {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border-left: 4px solid #4f46e5;
            margin-bottom: 1rem;
        }
        .modal-formula-config-card .form-group-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .modal-formula-config-card .form-group-row .form-label-sm {
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.25rem;
            display: block;
        }
        .modal-formula-config-card .form-group-row .form-control-sm-custom {
            width: 100%;
            padding: 0.375rem 0.625rem;
            font-size: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: border-color 0.15s ease;
        }
        .modal-formula-config-card .form-group-row .form-control-sm-custom:focus {
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .modal-total-cost-display {
            background: #d1fae5;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: #065f46;
            font-weight: 600;
        }
        .modal-currency-badge {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .modal-currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }
        .modal-currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }

        /* ─── Calculator Styles ─── */
        .formula-config-section {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
        }
        .formula-config-section .formula-title {
            font-weight: 600;
            font-size: 0.8rem;
            color: #0284c7;
            margin-bottom: 0.5rem;
        }
        .formula-config-section .formula-title i {
            margin-right: 0.3rem;
        }
        .form-label-sm {
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.25rem;
            display: block;
        }
        .form-control-sm-custom {
            width: 100%;
            padding: 0.375rem 0.625rem;
            font-size: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: border-color 0.15s ease;
            height: 34px;
        }
        .form-control-sm-custom:focus {
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .reel-info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
        }
        .reel-info-box .reel-label {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
        }
        .reel-info-box .reel-value {
            font-weight: 700;
            color: #0284c7;
            font-size: 1rem;
        }
        .reel-info-box .reel-formula {
            font-size: 0.6rem;
            color: #94a3b8;
        }
        .calculation-step-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.4rem;
            transition: all 0.2s ease;
        }
        .calculation-step-card:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .calculation-step-card .step-number {
            font-weight: 600;
            color: #4f46e5;
            font-size: 0.75rem;
        }
        .calculation-step-card .step-result {
            font-weight: 600;
            color: #0f766e;
            font-size: 0.85rem;
        }

        /* ─── Manual BOM Fields Grid ─── */
        .manual-input-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(120px, 1fr));
            gap: 0.75rem;
            min-width: 0;
        }
        @media (max-width: 1200px) {
            .manual-input-grid {
                grid-template-columns: repeat(4, minmax(120px, 1fr));
            }
        }
        @media (max-width: 992px) {
            .manual-input-grid {
                grid-template-columns: repeat(3, minmax(120px, 1fr));
            }
        }
        @media (max-width: 768px) {
            .manual-input-grid {
                grid-template-columns: repeat(2, minmax(120px, 1fr));
            }
        }
        @media (max-width: 480px) {
            .manual-input-grid {
                grid-template-columns: 1fr;
            }
        }
        .manual-field {
            min-width: 0;
        }
        .manual-field label {
            display: block;
            margin-bottom: .2rem;
            font-size: .58rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .manual-field input,
        .manual-field select {
            width: 100%;
            min-width: 0;
            height: 34px;
            border: 1px solid #dbe3f0;
            border-radius: 8px;
            padding: .3rem .5rem;
            font-size: .75rem;
            background: #fff;
            transition: border-color 0.2s ease;
        }
        .manual-field input:focus,
        .manual-field select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }
        .manual-field input[readonly] {
            background: #f1f5f9;
            cursor: not-allowed;
        }

        #confirmModal .modal-body .col-lg-7::-webkit-scrollbar {
            width: 4px;
        }

        #confirmModal .modal-body .col-lg-7::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        #confirmModal .modal-body .col-lg-7::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        #confirmModal .modal-body .col-lg-7::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        #confirmModal .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        #confirmModal .form-check-input:checked {
            background-color: #059669;
            border-color: #059669;
        }

        #confirmModal .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        #confirmModal .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.35);
        }

        #confirmModal .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        /* ─── Responsive Adjustments ─── */
        @media (max-width: 992px) {
            #confirmModal .modal-dialog {
                max-width: 100%;
                margin: 0.5rem;
            }

            #confirmModal .modal-body .col-lg-7 {
                max-height: 300px !important;
                overflow-y: auto !important;
                padding-right: 0.5rem !important;
            }

            #confirmModal .modal-body .col-lg-5 {
                margin-top: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            #confirmModal .modal-body {
                padding: 0.75rem 1rem !important;
            }

            #confirmModal .modal-header {
                padding: 0.75rem 1rem !important;
            }

            #confirmModal .modal-footer {
                padding: 0.5rem 1rem !important;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            #confirmModal .modal-footer .btn {
                flex: 1;
                justify-content: center;
            }
        }
        /* ─── Select2 Custom Styles ─── */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: var(--sale-radius-sm);
            border: 1.5px solid var(--sale-gray-200);
            font-size: 0.875rem;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0.3rem 0.75rem;
            color: var(--sale-gray-800);
            line-height: 1.5;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: var(--sale-radius-sm);
            border-color: var(--sale-gray-200);
            box-shadow: var(--sale-shadow-hover);
            background: white !important;
        }
        .select2-container--bootstrap-5 .select2-results {
            background: white !important;
        }
        .select2-container--bootstrap-5 .select2-results>.select2-results__options {
            max-height: 200px !important;
            overflow-y: auto !important;
            background: white !important;
        }
        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: white !important;
            color: var(--sale-gray-800) !important;
            border-bottom: 1px solid var(--sale-gray-50);
        }
        .select2-container--bootstrap-5 .select2-results__option:last-child {
            border-bottom: none;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background: var(--sale-primary) !important;
            color: white !important;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted .select2-product-name {
            color: white !important;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted .select2-product-category {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .select2-container--bootstrap-5 .select2-results__option[aria-selected="true"] {
            background: #eef2ff !important;
            color: var(--sale-primary) !important;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected="true"] {
            background: var(--sale-primary) !important;
            color: white !important;
        }
        .select2-container--bootstrap-5 .select2-results__option .select2-product-image {
            width: 28px;
            height: 28px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 0.625rem;
            flex-shrink: 0;
        }
        .select2-container--bootstrap-5 .select2-results__option .select2-product-info {
            display: flex;
            flex-direction: column;
        }
        .select2-container--bootstrap-5 .select2-results__option .select2-product-name {
            font-weight: 500;
            color: var(--sale-gray-800);
            font-size: 0.8125rem;
        }
        .select2-container--bootstrap-5 .select2-results__option .select2-product-category {
            font-size: 0.6875rem;
            color: var(--sale-gray-400);
        }

        .sale-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .sale-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--sale-gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .sale-header h1 .accent {
            background: linear-gradient(135deg, var(--sale-primary), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sale-header .subtitle {
            color: var(--sale-gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sale-header .header-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .sale-header .header-actions .btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--sale-radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.3s ease;
        }
        .sale-header .header-actions .btn:hover {
            transform: translateY(-2px);
        }
        .sale-header .header-actions .action-divider {
            width: 1px;
            height: 30px;
            background: var(--sale-gray-200);
        }

        .sale-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .sale-status-badge.draft {
            background: var(--sale-gray-100);
            color: var(--sale-gray-600);
            border: 1px solid var(--sale-gray-200);
        }
        .sale-status-badge.confirmed {
            background: #e0e7ff;
            color: var(--sale-primary);
            border: 1px solid #a5b4fc;
        }
        .sale-status-badge.shipped {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .sale-status-badge.delivered {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .production-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .production-badge.produced {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .production-badge.in-progress {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #7dd3fc;
        }
        .production-badge.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .production-badge.not-started {
            background: var(--sale-gray-100);
            color: var(--sale-gray-500);
            border: 1px solid var(--sale-gray-200);
        }

        .sale-stats-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1024px) {
            .sale-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .sale-info-card {
            background: white;
            border-radius: var(--sale-radius);
            padding: 1.5rem;
            box-shadow: var(--sale-shadow);
            border: 1px solid var(--sale-gray-200);
            transition: all 0.3s ease;
        }
        .sale-info-card:hover {
            box-shadow: var(--sale-shadow-hover);
        }
        .sale-info-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--sale-gray-200);
            margin-bottom: 0.75rem;
        }
        .sale-info-card .card-header .title {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--sale-gray-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sale-info-card .card-header .title i {
            color: var(--sale-primary);
        }
        .sale-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem 1.5rem;
        }
        .sale-info-item .label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--sale-gray-400);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .sale-info-item .value {
            font-size: 0.875rem;
            color: var(--sale-gray-800);
            font-weight: 600;
        }

        .sale-summary-card {
            background: white;
            border-radius: var(--sale-radius);
            box-shadow: var(--sale-shadow);
            border: 1px solid var(--sale-gray-200);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sale-summary-card:hover {
            box-shadow: var(--sale-shadow-hover);
        }
        .sale-summary-card .summary-header {
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--sale-gray-200);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sale-summary-card .summary-header .icon {
            width: 32px;
            height: 32px;
            border-radius: var(--sale-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        .sale-summary-card .summary-header .icon-usd {
            background: linear-gradient(135deg, #059669, #10b981);
        }
        .sale-summary-card .summary-header .icon-afn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .sale-summary-card .summary-header h6 {
            margin: 0;
            font-weight: 700;
            font-size: 0.8125rem;
            color: var(--sale-gray-800);
            flex: 1;
        }
        .sale-summary-table {
            width: 100%;
            border-collapse: collapse;
            flex: 1;
        }
        .sale-summary-table tr {
            border-bottom: 1px solid var(--sale-gray-100);
        }
        .sale-summary-table tr:last-child {
            border-bottom: none;
        }
        .sale-summary-table td {
            padding: 0.5rem 1.25rem;
            font-size: 0.8125rem;
            background: white;
        }
        .sale-summary-table .label {
            color: var(--sale-gray-500);
            font-weight: 500;
        }
        .sale-summary-table .value {
            text-align: right;
            font-weight: 600;
            color: var(--sale-gray-800);
        }
        .sale-summary-table .grand-total td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-weight: 700;
            border-top: 2px solid var(--sale-gray-200);
        }
        .sale-summary-table .grand-total .value {
            font-size: 1.125rem;
        }

        .sale-section {
            background: white;
            border-radius: var(--sale-radius);
            box-shadow: var(--sale-shadow);
            border: 1px solid var(--sale-gray-200);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .sale-section:hover {
            box-shadow: var(--sale-shadow-hover);
        }
        .sale-section .section-header {
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--sale-gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .sale-section .section-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--sale-gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sale-section .section-header h5 i {
            color: var(--sale-primary);
        }
        .sale-section .section-body {
            padding: 1.5rem;
        }

        .product-select-wrapper {
            display: grid;
            grid-template-columns: 1fr 0.8fr 0.8fr auto;
            gap: 0.75rem;
            align-items: end;
        }
        @media (max-width: 768px) {
            .product-select-wrapper {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .product-select-wrapper {
                grid-template-columns: 1fr;
            }
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--sale-gray-600);
            margin-bottom: 0.35rem;
        }
        .form-label .text-danger {
            color: #ef4444;
        }
        .form-control, .form-select {
            border-radius: var(--sale-radius-sm);
            border: 1.5px solid var(--sale-gray-200);
            padding: 0.45rem 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            height: 38px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--sale-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }
        .form-control[readonly] {
            background: var(--sale-gray-50);
            cursor: not-allowed;
        }

        .currency-badge {
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            background: var(--sale-gray-100);
            color: var(--sale-gray-600);
        }

        .sale-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .sale-table thead th {
            padding: 0.625rem 0.75rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: var(--sale-gray-600);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid var(--sale-gray-200);
            white-space: nowrap;
        }
        .sale-table tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--sale-gray-100);
            vertical-align: middle;
        }
        .sale-table tbody tr:hover {
            background: rgba(79, 70, 229, 0.02);
        }
        .sale-table .product-cell .name {
            font-weight: 600;
            color: var(--sale-gray-800);
        }
        .sale-table .product-cell .meta {
            font-size: 0.6875rem;
            color: var(--sale-gray-400);
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .profit-positive {
            color: var(--sale-success);
            font-weight: 700;
        }
        .profit-negative {
            color: var(--sale-danger);
            font-weight: 700;
        }
        .profit-badge {
            font-size: 0.6875rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        .profit-badge.positive {
            background: #d1fae5;
            color: #065f46;
        }
        .profit-badge.negative {
            background: #fecaca;
            color: #991b1b;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            border-radius: var(--sale-radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            color: var(--sale-gray-400);
        }
        .action-btn:hover {
            background: var(--sale-gray-100);
            color: var(--sale-danger);
        }
        .action-btn.danger:hover {
            background: #fecaca;
            color: var(--sale-danger);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
        }
        .empty-state .icon {
            font-size: 2.5rem;
            color: var(--sale-gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }
        .empty-state .title {
            font-weight: 600;
            color: var(--sale-gray-700);
            font-size: 0.9375rem;
        }
        .empty-state .subtitle {
            color: var(--sale-gray-400);
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sale-primary), var(--sale-primary-dark));
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: var(--sale-radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: var(--sale-radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            color: white;
        }
        .btn-outline-secondary {
            border: 1.5px solid var(--sale-gray-200);
            color: var(--sale-gray-600);
            background: transparent;
            padding: 0.6rem 1.5rem;
            border-radius: var(--sale-radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: var(--sale-gray-100);
            border-color: var(--sale-gray-300);
        }
        .btn-print {
            background: white;
            border: 1.5px solid var(--sale-gray-200);
            color: var(--sale-gray-600);
        }
        .btn-print:hover {
            background: var(--sale-gray-50);
            border-color: var(--sale-gray-300);
            transform: translateY(-2px);
        }

        .bom-preview-card {
            background: #f8fafc;
            border: 1px solid var(--sale-gray-200);
            border-radius: var(--sale-radius-sm);
            padding: 1rem;
            margin-top: 1rem;
        }
        .bom-preview-card .bom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--sale-gray-200);
            margin-bottom: 0.75rem;
        }
        .bom-material-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--sale-gray-100);
            font-size: 0.8125rem;
        }
        .bom-material-item:last-child {
            border-bottom: none;
        }
        .bom-material-item .mat-name {
            font-weight: 500;
            color: var(--sale-gray-700);
        }
        .bom-material-item .mat-details {
            color: var(--sale-gray-500);
            font-size: 0.75rem;
        }
        .bom-material-item .mat-cost {
            font-weight: 600;
            color: var(--sale-primary);
        }
        .bom-cost-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        @media (max-width: 768px) {
            .bom-cost-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .bom-cost-item {
            background: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--sale-gray-200);
            text-align: center;
        }
        .bom-cost-item .cost-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            color: var(--sale-gray-400);
            font-weight: 600;
        }
        .bom-cost-item .cost-value {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--sale-gray-800);
        }
        .bom-cost-item .cost-value.text-primary {
            color: var(--sale-primary);
        }
        .bom-cost-item .cost-value.text-success {
            color: var(--sale-success);
        }
        .bom-cost-item .cost-value.text-info {
            color: #0ea5e9;
        }
        .bom-cost-item .cost-value.text-warning {
            color: #d97706;
        }

        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        .btn-print {
            background: white;
            border: 1.5px solid var(--sale-gray-200);
            color: var(--sale-gray-600);
        }
        .btn-print:hover {
            background: var(--sale-gray-50);
            border-color: var(--sale-gray-300);
            transform: translateY(-2px);
        }

        /* ─── Delete Modal Styles ─── */
        .delete-modal .modal-content {
            border-radius: var(--sale-radius);
            overflow: hidden;
            border: none;
        }
        .delete-modal .modal-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            border: none;
            padding: 1.25rem 1.5rem;
        }
        .delete-modal .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .delete-modal .modal-body {
            padding: 1.5rem;
        }
        .delete-warning-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--sale-radius-sm);
            padding: 1rem;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .delete-warning-box .warning-icon {
            color: #dc2626;
            font-size: 1.5rem;
            margin-right: 0.75rem;
            float: left;
        }
        .delete-warning-box .warning-text {
            overflow: hidden;
        }
        .delete-warning-box .warning-text strong {
            color: #991b1b;
        }
        .delete-warning-box .warning-text ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.25rem;
            font-size: 0.875rem;
            color: #7f1d1d;
        }
        .delete-warning-box .warning-text ul li {
            margin-bottom: 0.25rem;
        }
        .delete-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--sale-gray-700);
            padding: 0.75rem;
            background: var(--sale-gray-50);
            border-radius: var(--sale-radius-sm);
            border: 1px solid var(--sale-gray-200);
            transition: all 0.3s ease;
        }
        .delete-checkbox-label:hover {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .delete-checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: #dc2626;
            flex-shrink: 0;
        }
        .delete-checkbox-label .checkbox-text strong {
            color: #dc2626;
        }
        .btn-delete-sale {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: var(--sale-radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-delete-sale:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            color: white;
        }
        .btn-delete-sale:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-delete-sale .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .btn-delete-sale.loading .spinner {
            display: inline-block;
        }
        .btn-delete-sale.loading .btn-text {
            display: none;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .password-input-group {
            position: relative;
        }
        .password-input-group .form-control {
            padding-right: 45px;
            border: 1.5px solid var(--sale-gray-200);
            border-radius: var(--sale-radius-sm);
            height: 45px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        .password-input-group .form-control:focus {
            border-color: var(--sale-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }
        .password-input-group .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--sale-gray-400);
            cursor: pointer;
            padding: 0;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        .password-input-group .toggle-password:hover {
            color: var(--sale-gray-600);
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .sale-summary-card .summary-header .icon-afn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        /* ─── Live Excel Cost Estimator ─── */
        .quote-estimator {
            margin-top: 1rem;
            width: 100%;
            min-width: 0;
            border: 1px solid #c7d2fe;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 18px 45px rgba(79, 70, 229, 0.08);
        }
        .quote-estimator-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #312e81, #4f46e5 55%, #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .quote-estimator-header .eyebrow {
            font-size: .62rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .75;
            font-weight: 800;
        }
        .quote-estimator-header h6 { margin: .1rem 0 0; font-weight: 800; }
        .formula-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            padding: 1rem 1.25rem;
            background: #eef2ff;
            border-bottom: 1px solid #c7d2fe;
        }
        .formula-step {
            background: rgba(255,255,255,.9);
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: .65rem .75rem;
        }
        .formula-step small { display:block; color:#64748b; font-size:.62rem; font-weight:700; text-transform:uppercase; }
        .formula-step strong { display:block; margin-top:.15rem; color:#312e81; font-size:.76rem; }
        .manual-materials-list {
            display: grid;
            gap: 1rem;
            width: 100%;
            min-width: 0;
        }
        .manual-material-card {
            width: 100%;
            min-width: 0;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .manual-material-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .9rem 1rem;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        .manual-material-title { min-width: 0; }
        .manual-material-name {
            font-size: .9rem;
            font-weight: 800;
            color: #1e293b;
            overflow-wrap: anywhere;
        }
        .manual-material-subtitle { color: #64748b; font-size: .7rem; margin-top: .15rem; }
        .latest-cost-chip { display:inline-flex; align-items:center; gap:.25rem; padding:.2rem .5rem; border-radius:999px; background:#dcfce7; color:#166534; font-size:.62rem; font-weight:800; white-space:nowrap; }
        .manual-material-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 230px;
            gap: 1rem;
            padding: 1rem;
            min-width: 0;
        }
        .manual-input-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(105px, 1fr));
            gap: .75rem;
            min-width: 0;
        }
        .manual-field { min-width: 0; }
        .manual-field label {
            display: block;
            margin-bottom: .3rem;
            font-size: .61rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .manual-field input {
            width: 100%;
            min-width: 0;
            height: 38px;
            border: 1px solid #dbe3f0;
            border-radius: 9px;
            padding: .4rem .55rem;
            font-size: .79rem;
            text-align: right;
            background: #fff;
        }
        .manual-field input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
        .latest-cost-field { min-width: 0; }
        .latest-cost-display {
            min-height: 74px;
            padding: .65rem .75rem;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: linear-gradient(135deg, #f0fdf4, #ffffff);
            overflow: hidden;
        }
        .latest-cost-main {
            color: #166534;
            font-size: .95rem;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        .latest-cost-main span { font-size: .65rem; font-weight: 600; color: #64748b; }
        .latest-cost-source {
            margin-top: .25rem;
            color: #15803d;
            font-size: .64rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .3rem;
        }
        .latest-cost-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .55rem;
            margin-top: .35rem;
            padding-top: .35rem;
            border-top: 1px dashed #bbf7d0;
            color: #64748b;
            font-size: .58rem;
            line-height: 1.35;
        }
        .latest-cost-meta span { display: inline-flex; align-items: center; gap: .2rem; min-width: 0; overflow-wrap: anywhere; }
        .manual-result-panel {
            display: grid;
            gap: .55rem;
            align-content: center;
            background: linear-gradient(135deg, #312e81, #4f46e5);
            border-radius: 13px;
            padding: .85rem;
            color: #fff;
            min-width: 0;
        }
        .manual-result-row { display:flex; justify-content:space-between; align-items:center; gap:.5rem; font-size:.68rem; color:rgba(255,255,255,.78); }
        .manual-result-row strong { color:#fff; font-size:.84rem; white-space:nowrap; }
        .manual-result-row.total { margin-top:.15rem; padding-top:.55rem; border-top:1px solid rgba(255,255,255,.2); }
        .manual-result-row.total strong { font-size:1rem; }
        .manual-empty-state { text-align:center; padding:2rem 1rem; color:#64748b; background:#fff; border:1px dashed #cbd5e1; border-radius:14px; }
        .quote-estimator, .quote-estimator * { box-sizing: border-box; }
        .quote-estimator { max-width:100%; overflow:hidden; }
        .quote-estimator .p-3, .quote-estimator .p-lg-4 { min-width:0; }
        @media (max-width: 1200px) {
            .manual-input-grid { grid-template-columns: repeat(3, minmax(105px, 1fr)); }
        }
        @media (max-width: 900px) {
            .manual-material-body { grid-template-columns: 1fr; }
            .manual-result-panel { grid-template-columns: 1fr 1fr; }
            .manual-result-row.total { margin-top:0; padding-top:0; border-top:0; }
        }
        @media (max-width: 700px) {
            .manual-input-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .manual-result-panel { grid-template-columns:1fr; }
            .manual-result-row.total { margin-top:.15rem; padding-top:.55rem; border-top:1px solid rgba(255,255,255,.2); }
        }
        @media (max-width: 430px) {
            .manual-input-grid { grid-template-columns: 1fr; }
            .manual-material-header, .manual-material-body { padding:.8rem; }
        }
        .quote-summary-grid {
            display:grid;
            grid-template-columns: repeat(6, minmax(0,1fr));
            gap:.75rem;
            padding:1rem 1.25rem 1.25rem;
        }
        .quote-summary-card { background:white; border:1px solid #e2e8f0; border-radius:12px; padding:.8rem; }
        .quote-summary-card.highlight { background:linear-gradient(135deg,#ecfdf5,#f0fdf4); border-color:#86efac; }
        .quote-summary-card .label { font-size:.62rem; color:#64748b; text-transform:uppercase; font-weight:800; letter-spacing:.04em; }
        .quote-summary-card .value { font-size:1rem; font-weight:900; color:#0f172a; margin-top:.2rem; }
        .quote-summary-card.highlight .value { color:#047857; font-size:1.12rem; }
        @media (max-width: 992px) {
            .formula-strip { grid-template-columns:1fr 1fr; }
            .quote-summary-grid { grid-template-columns:1fr 1fr; }
        }
        @media (max-width: 576px) {
            .formula-strip, .quote-summary-grid { grid-template-columns:1fr; }
        }

        .pricing-mode-notice {
            margin-top: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #bbf7d0;
            background: linear-gradient(135deg, #f0fdf4, #ffffff);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: .85rem;
        }
        .pricing-mode-notice.manual { border-color:#c7d2fe; background:linear-gradient(135deg,#eef2ff,#fff); }
        .pricing-mode-notice .mode-icon { width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#d1fae5;color:#059669;font-size:1.15rem; }
        .pricing-mode-notice.manual .mode-icon { background:#e0e7ff;color:#4f46e5; }
        .pricing-mode-notice .mode-title { font-weight:800;color:#1e293b;font-size:.9rem; }
        .pricing-mode-notice .mode-copy { color:#64748b;font-size:.75rem;margin-top:.15rem; }
        .pricing-mode-notice .mode-price { font-weight:900;color:#059669;font-size:1.15rem;white-space:nowrap; }
        .pricing-mode-notice.manual .mode-price { color:#4f46e5; }
        #bomSelect + .select2-container .select2-selection__rendered { font-weight:600; }
        @media (max-width:576px){ .pricing-mode-notice{align-items:flex-start;flex-wrap:wrap}.pricing-mode-notice .mode-price{width:100%;padding-left:3.55rem;} }
    </style>
@endsection

@section('content')
    @php
        $ps = $profitSummary ?? [];
        $actualAvailable = (bool) ($ps['actual_available'] ?? false);
        $saleCurrencySymbol = $currencyCode === 'USD' ? '$' : '؋';
        $grossSales = $currencyCode === 'USD'
            ? (float) ($ps['gross_sales_usd'] ?? $sale->usd_grand_total ?? 0)
            : (float) ($ps['gross_sales_afn'] ?? $sale->grand_total ?? 0);
        $estimatedCost = $currencyCode === 'USD'
            ? (float) ($ps['estimated_cost_usd'] ?? 0)
            : (float) ($ps['estimated_cost_afn'] ?? 0);
        $estimatedProfit = $currencyCode === 'USD'
            ? (float) ($ps['estimated_profit_usd'] ?? 0)
            : (float) ($ps['estimated_profit_afn'] ?? 0);
        $estimatedMargin = $grossSales > 0 ? ($estimatedProfit / $grossSales) * 100 : 0;
        $actualCost = $currencyCode === 'USD'
            ? (float) ($ps['actual_production_cost_usd'] ?? 0)
            : (float) ($ps['actual_production_cost_afn'] ?? 0);
        $actualProfit = $currencyCode === 'USD'
            ? (float) ($ps['actual_profit_usd'] ?? 0)
            : (float) ($ps['actual_profit_afn'] ?? 0);
        $statusLabels = [
            'draft' => ['label' => __('ui.draft'), 'class' => 'draft'],
            'confirmed' => ['label' => __('ui.confirmed'), 'class' => 'confirmed'],
            'shipped' => ['label' => __('ui.shipped'), 'class' => 'confirmed'],
            'delivered' => ['label' => __('ui.delivered'), 'class' => 'delivered'],
        ];
        $statusUi = $statusLabels[$sale->status] ?? $statusLabels['draft'];
        $productionUi = $sale->is_produced
            ? ['label' => __('ui.completed'), 'class' => 'done']
            : (($sale->productionOrder && $sale->productionOrder->status === 'in_progress')
                ? ['label' => __('ui.in_progress'), 'class' => 'progress']
                : ['label' => __('ui.not_started'), 'class' => 'pending']);
    @endphp

    <style>
        .so2-shell{max-width:1600px;margin:0 auto}.so2-header{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem}.so2-title{margin:0;font-size:1.85rem;font-weight:850;color:#0f172a;letter-spacing:-.035em}.so2-title .accent{color:#4f46e5}.so2-subtitle{margin-top:.28rem;color:#64748b;font-size:.78rem}.so2-header-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}.so2-btn{border-radius:10px;font-size:.76rem;font-weight:700;padding:.56rem .9rem;display:inline-flex;align-items:center;gap:.42rem}.so2-status{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.36rem .72rem;font-size:.7rem;font-weight:800}.so2-status.draft{background:#eef2f7;color:#475569}.so2-status.confirmed{background:#dcfce7;color:#166534}.so2-status.delivered,.so2-status.done{background:#d1fae5;color:#065f46}.so2-status.pending{background:#fff3d6;color:#a85700;border:1px solid #fed991}.so2-status.progress{background:#e0f2fe;color:#0369a1}.so2-top-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.8rem;margin-bottom:1rem}.so2-stat{background:#fff;border:1px solid #e2e8f0;border-radius:13px;padding:.95rem 1rem;display:flex;align-items:center;gap:.75rem;min-height:86px;box-shadow:0 2px 9px rgba(15,23,42,.03)}.so2-stat-icon{width:40px;height:40px;border-radius:10px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex:0 0 auto}.so2-stat-icon.green{background:#ecfdf5;color:#059669}.so2-stat-icon.blue{background:#eff6ff;color:#2563eb}.so2-stat-label{font-size:.66rem;color:#64748b;margin-bottom:.15rem}.so2-stat-value{font-size:.9rem;font-weight:800;color:#172033;line-height:1.25}.so2-stat-help{font-size:.64rem;color:#64748b;margin-top:.15rem}.so2-main-grid{display:grid;grid-template-columns:minmax(0,1fr) 285px;gap:.9rem;margin-bottom:.9rem}.so2-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 2px 10px rgba(15,23,42,.035);overflow:hidden}.so2-card-head{padding:.9rem 1rem;border-bottom:1px solid #e7edf5;display:flex;align-items:center;justify-content:space-between;gap:.7rem}.so2-card-title{display:flex;align-items:center;gap:.55rem;font-size:.94rem;font-weight:850;color:#172033}.so2-card-title i{color:#4f46e5;font-size:1rem}.so2-card-sub{font-size:.66rem;color:#64748b;margin-top:.1rem}.so2-table-toolbar{display:flex;gap:.45rem;align-items:center}.so2-search{width:270px;position:relative}.so2-search input{height:35px;border:1px solid #dbe3ef;border-radius:9px;padding:.4rem .7rem .4rem 2rem;font-size:.72rem}.so2-search i{position:absolute;left:.65rem;top:.58rem;color:#64748b;font-size:.8rem}.so2-items{width:100%;border-collapse:collapse;font-size:.72rem}.so2-items th{padding:.63rem .62rem;background:#f8fafc;border-bottom:1px solid #e5ebf3;color:#64748b;font-size:.6rem;font-weight:800;white-space:nowrap;text-align:left}.so2-items td{padding:.7rem .62rem;border-bottom:1px solid #eef2f7;vertical-align:middle}.so2-items tbody tr:last-child td{border-bottom:0}.so2-items tbody tr.selected{background:#f5f3ff}.so2-product{font-weight:800;color:#172033;line-height:1.25}.so2-product-meta{font-size:.61rem;color:#64748b;margin-top:.16rem}.so2-price-input{width:108px;height:34px;border:1px solid #dbe3ef;border-radius:8px;padding:.35rem .55rem;font-size:.72rem;font-weight:700}.so2-price-input:focus{outline:0;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}.so2-manual-tag{display:inline-flex;align-items:center;gap:.25rem;margin-top:.2rem;color:#4f46e5;background:#eef2ff;padding:.12rem .4rem;border-radius:5px;font-size:.55rem;font-weight:800}.so2-money-good{color:#059669;font-weight:850}.so2-info-strip{margin:.15rem .75rem .75rem;padding:.55rem .7rem;border-radius:8px;background:#f3f2ff;color:#4f46e5;font-size:.65rem}.so2-summary{padding:.9rem 1rem}.so2-summary-row{display:flex;justify-content:space-between;gap:1rem;padding:.46rem 0;color:#526079;font-size:.72rem}.so2-summary-row strong{color:#172033}.so2-summary-row.profit strong,.so2-summary-row.margin strong{color:#059669}.so2-summary-note{margin-top:.7rem;padding:.75rem;border-radius:9px;background:#f3f2ff;color:#55627a;font-size:.64rem;line-height:1.45}.so2-tabs-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 2px 10px rgba(15,23,42,.035)}.so2-tabs{display:flex;gap:1.6rem;border-bottom:1px solid #e5ebf3;padding:0 1rem}.so2-tab{border:0;background:none;padding:.8rem .15rem .7rem;color:#526079;font-size:.72rem;font-weight:750;border-bottom:2px solid transparent}.so2-tab.active{color:#4f46e5;border-bottom-color:#4f46e5}.so2-tab i{margin-right:.38rem}.so2-panel{display:none;padding:.8rem}.so2-panel.active{display:block}.so2-overview-grid{display:grid;grid-template-columns:1fr 1fr 1.05fr;gap:.7rem}.so2-mini-card{border:1px solid #e2e8f0;border-radius:10px;padding:.85rem}.so2-mini-head{font-size:.74rem;font-weight:850;color:#172033;margin-bottom:.6rem;display:flex;align-items:center;gap:.45rem}.so2-mini-head i{color:#4f46e5}.so2-kv{display:grid;grid-template-columns:130px 1fr;gap:.38rem;font-size:.67rem;margin-bottom:.32rem}.so2-kv span:first-child{color:#64748b}.so2-kv span:last-child{color:#172033;font-weight:650}.so2-pricing-note{background:#f4f3ff;border-radius:8px;padding:.62rem .7rem;font-size:.65rem;color:#4b556b;line-height:1.45}.so2-breakdown-head{display:flex;justify-content:space-between;align-items:center;gap:.7rem;margin-bottom:.6rem}.so2-breakdown-title{font-size:.83rem;font-weight:850;color:#172033}.so2-selected-chip{background:#dcfce7;color:#047857;border-radius:999px;padding:.25rem .55rem;font-size:.59rem;font-weight:800}.so2-breakdown-table{width:100%;border-collapse:collapse;font-size:.68rem}.so2-breakdown-table th{background:#f8fafc;color:#64748b;font-size:.58rem;padding:.48rem .55rem;text-align:left}.so2-breakdown-table td{padding:.45rem .55rem;border-bottom:1px solid #edf1f6}.so2-breakdown-total{display:flex;justify-content:flex-end;align-items:center;gap:.75rem;padding:.65rem;background:#fafbff;font-size:.67rem}.so2-breakdown-total strong{font-size:.8rem;color:#059669}.so2-empty{padding:2.2rem;text-align:center;color:#64748b;font-size:.72rem}.so2-empty i{display:block;font-size:1.65rem;color:#cbd5e1;margin-bottom:.5rem}.so2-cost-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem}.so2-cost-box{border:1px solid #e2e8f0;border-radius:10px;padding:.8rem}.so2-cost-box .label{font-size:.61rem;color:#64748b}.so2-cost-box .value{font-size:1rem;font-weight:850;margin-top:.2rem}.so2-drawer.offcanvas{width:min(660px,96vw)}.so2-drawer .offcanvas-header{border-bottom:1px solid #e5ebf3}.so2-mode-tabs{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.8rem}.so2-mode-btn{border:1px solid #dbe3ef;border-radius:9px;background:#f8fafc;padding:.62rem;font-size:.72rem;font-weight:800;color:#526079}.so2-mode-btn.active{border-color:#6366f1;background:#eef2ff;color:#4f46e5}.so2-add-pane{display:none}.so2-add-pane.active{display:block}.so2-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}.so2-form-grid .full{grid-column:1/-1}.so2-drawer label{font-size:.68rem;font-weight:750;color:#334155;margin-bottom:.25rem}.so2-drawer .form-control,.so2-drawer .form-select{font-size:.74rem;border-radius:8px;border-color:#dbe3ef}.so2-drawer-footer{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5ebf3;padding:.8rem;margin:1rem -.9rem -.9rem;display:flex;justify-content:flex-end;gap:.5rem}.so2-hidden-desc{display:none;margin-top:.35rem;min-width:240px;font-size:.68rem}.so2-advanced{margin-top:.75rem}.so2-quick-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-top:.7rem}.so2-quick-box{background:#f8fafc;border:1px solid #e5ebf3;border-radius:8px;padding:.55rem}.so2-quick-box small{display:block;color:#64748b;font-size:.56rem}.so2-quick-box strong{font-size:.72rem}.so2-production-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.7rem}
        @media(max-width:1200px){.so2-top-grid{grid-template-columns:repeat(3,1fr)}.so2-main-grid{grid-template-columns:1fr}.so2-overview-grid{grid-template-columns:1fr 1fr}.so2-items{min-width:980px}.so2-card .table-responsive{overflow-x:auto}}
        @media(max-width:768px){.so2-header{flex-direction:column}.so2-header-actions{justify-content:flex-start}.so2-top-grid{grid-template-columns:1fr 1fr}.so2-overview-grid,.so2-cost-grid{grid-template-columns:1fr}.so2-tabs{overflow-x:auto;gap:1rem}.so2-tab{white-space:nowrap}.so2-search{display:none}.so2-form-grid{grid-template-columns:1fr}.so2-form-grid .full{grid-column:auto}}
        @media(max-width:480px){.so2-top-grid{grid-template-columns:1fr}.so2-title{font-size:1.45rem}}
    </style>

    <div class="container-fluid px-3 px-md-4">
        <div class="so2-shell">
            <div class="so2-header">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="so2-title">{{ __('ui.sale_order') }} <span class="accent">#{{ $sale->sale_no }}</span></h1>
                        <span class="so2-status {{ $statusUi['class'] }}">{{ $statusUi['label'] }}</span>
                        <span class="so2-status {{ $productionUi['class'] }}"><i class="bi bi-gear"></i>{{ __('ui.production') }}: {{ $productionUi['label'] }}</span>
                    </div>
                    <div class="so2-subtitle">
                        Created {{ optional($sale->created_at)->format('M d, Y') }}
                        <span class="mx-2">·</span>
                        Last updated {{ optional($sale->updated_at)->diffForHumans() }}
                    </div>
                </div>
                <div class="so2-header-actions">
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary so2-btn"><i class="bi bi-arrow-left"></i>{{ __('ui.back') }}</a>
                    @if($sale->items->isNotEmpty())
                        <div class="dropdown">
                            <button class="btn btn-outline-primary so2-btn dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-printer"></i> Print</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" target="_blank" href="{{ route('admin.sales.quotation', $sale->id) }}"><i class="bi bi-file-earmark-text me-2"></i>Print Quotation</a></li>
                                <li><a class="dropdown-item" target="_blank" href="{{ route('admin.sales.print', $sale->id) }}"><i class="bi bi-receipt me-2"></i>Print Invoice</a></li>
                            </ul>
                        </div>
                    @endif
                    @can('update sales')
                        @if($sale->status === 'draft')
                            <button class="btn btn-primary so2-btn" onclick="updateStatus('confirmed')"><i class="bi bi-check2"></i>Confirm Order</button>
                        @elseif($sale->status === 'confirmed' && !$sale->is_produced && $sale->productionOrder && $sale->productionOrder->status === 'pending')
                            <a href="{{ route('production-orders.show', $sale->productionOrder) }}" class="btn btn-warning so2-btn"><i class="bi bi-calculator"></i>Set Production Qty</a>
                        @elseif($sale->is_produced && $sale->status !== 'delivered')
                            <form action="{{ route('admin.sales.deliver', $sale->id) }}" method="POST" class="d-inline">@csrf
                                <button type="submit" class="btn btn-success so2-btn" onclick='return confirm(@json(__('ui.deliver_order_confirm')))'><i class="bi bi-truck"></i>Deliver</button>
                            </form>
                        @endif
                    @endcan
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary so2-btn dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-grid-3x3-gap"></i>More</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if($sale->productionOrder)
                                <li><a class="dropdown-item" href="{{ route('production-orders.show', $sale->productionOrder) }}"><i class="bi bi-gear me-2"></i>{{ __('ui.view_production') }}</a></li>
                            @endif
                            @if($sale->status === 'delivered' && $sale->gatePass)
                                <li><a class="dropdown-item" target="_blank" href="{{ route('admin.sales.gate-pass', $sale->id) }}"><i class="bi bi-door-open me-2"></i>Gate Pass</a></li>
                            @endif
                            @can('delete sales')
                                @if($sale->status !== 'delivered')
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash3 me-2"></i>{{ __('ui.delete') }}</button></li>
                                @endif
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>

            <div class="so2-top-grid">
                <div class="so2-stat"><div class="so2-stat-icon"><i class="bi bi-person"></i></div><div><div class="so2-stat-label">{{ __('ui.customer') }}</div><div class="so2-stat-value">{{ $sale->customer->name ?? 'No Customer' }}</div><div class="so2-stat-help">{{ $sale->customer->account_no ?? '' }}</div></div></div>
                <div class="so2-stat"><div class="so2-stat-icon blue"><i class="bi bi-calendar3"></i></div><div><div class="so2-stat-label">Order Date</div><div class="so2-stat-value">{{ $sale->sale_date ? date('M d, Y', strtotime($sale->sale_date)) : '-' }}</div></div></div>
                <div class="so2-stat"><div class="so2-stat-icon"><i class="bi bi-currency-exchange"></i></div><div><div class="so2-stat-label">{{ __('ui.currency') }} / {{ __('ui.exchange_rate') }}</div><div class="so2-stat-value">{{ $currencyCode }}</div><div class="so2-stat-help">1 USD = {{ number_format($exchangeRate, 4) }} AFN</div></div></div>
                <div class="so2-stat"><div class="so2-stat-icon green"><i class="bi bi-box-seam"></i></div><div><div class="so2-stat-label">{{ __('ui.total_items') }}</div><div class="so2-stat-value">{{ $sale->items->count() }} {{ __('ui.products') }}</div></div></div>
                <div class="so2-stat"><div class="so2-stat-icon green"><i class="bi bi-coin"></i></div><div><div class="so2-stat-label">{{ __('ui.grand_total') }}</div><div class="so2-stat-value text-success">{{ $saleCurrencySymbol }} {{ number_format($grossSales, 2) }}</div></div></div>
            </div>

            <div class="so2-main-grid">
                <div class="so2-card">
                    <div class="so2-card-head">
                        <div><div class="so2-card-title"><i class="bi bi-box"></i>Sale Items</div><div class="so2-card-sub">Carton products in this order</div></div>
                        <div class="so2-table-toolbar">
                            <div class="so2-search"><i class="bi bi-search"></i><input id="so2ItemSearch" class="form-control" placeholder="Search products, SKU, or BOM..."></div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary so2-btn dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-funnel"></i><span id="so2FilterLabel">Filter</span></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><button class="dropdown-item so2-filter-option" data-so2-filter="all">All Items</button></li>
                                    <li><button class="dropdown-item so2-filter-option" data-so2-filter="manual">Manual Overrides</button></li>
                                    <li><button class="dropdown-item so2-filter-option" data-so2-filter="bom">BOM-linked Items</button></li>
                                </ul>
                            </div>
                            @if($sale->status === 'draft')
                                <button class="btn btn-primary so2-btn" data-bs-toggle="offcanvas" data-bs-target="#addCartonOffcanvas"><i class="bi bi-plus-lg"></i>Add Carton</button>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="so2-items">
                            <thead><tr>
                                <th style="width:34px"></th><th>{{ __('ui.product') }}</th><th>{{ __('ui.quantity') }}</th><th>Standard Price<br>({{ $currencyCode }})</th><th>Manual Override Price<br>({{ $currencyCode }})</th><th>Effective Selling Price<br>({{ $currencyCode }})</th><th>Estimated Cost<br>({{ $currencyCode }})</th><th>Estimated Profit<br>({{ $currencyCode }})</th><th>{{ __('ui.status') }}</th><th>{{ __('ui.action') }}</th>
                            </tr></thead>
                            <tbody id="saleItemsTableBody">
                            @forelse($sale->items as $item)
                                @php
                                    $standardPrice = (float) ($item->base_price ?: $item->original_unit_price ?: $item->unit_price);
                                    $effectivePrice = (float) $item->unit_price;
                                    $manualApplied = $item->price_adjustment_type === 'manual';
                                    $estimatedUnitCost = $currencyCode === 'USD'
                                        ? (float) ($item->cost_per_unit_usd ?? 0)
                                        : (float) ($item->cost_per_unit_usd ?? 0) * $exchangeRate;
                                    $estimatedLineProfit = (($effectivePrice - $estimatedUnitCost) * (float) $item->qty);
                                    $bomCode = $item->bom?->code;
                                @endphp
                                <tr data-so2-item-row="{{ $item->id }}" data-search="{{ strtolower(($item->product->name ?? '').' '.$bomCode) }}" data-manual="{{ $manualApplied ? '1' : '0' }}" data-has-bom="{{ $bomCode ? '1' : '0' }}">
                                    <td><input class="form-check-input so2-item-selector" type="checkbox" value="{{ $item->id }}" data-label="{{ $item->product->name ?? 'Item' }}" data-bom="{{ $bomCode ?? '' }}"></td>
                                    <td>
                                        <div class="so2-product">{{ $item->product->name ?? '-' }}</div>
                                        <div class="so2-product-meta">{{ $bomCode ?: 'No linked BOM' }}</div>
                                        @if($item->quotation_description)<div class="so2-product-meta"><i class="bi bi-card-text me-1"></i>{{ $item->quotation_description }}</div>@endif
                                        @if($sale->status === 'draft')
                                            <textarea class="form-control quotation-description-input so2-hidden-desc" data-id="{{ $item->id }}" rows="2" maxlength="2000" placeholder="Quotation description">{{ $item->quotation_description }}</textarea>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format((float)$item->qty, 2) }}</strong></td>
                                    <td>{{ number_format($standardPrice, 4) }}</td>
                                    <td>
                                        @if($sale->status === 'draft')
                                            <input type="number" class="so2-price-input manual-line-price" data-id="{{ $item->id }}" value="{{ $manualApplied ? number_format($effectivePrice, 4, '.', '') : '' }}" placeholder="Use system price" min="0.0001" step="0.0001">
                                            @if($manualApplied)<div class="so2-manual-tag"><i class="bi bi-pencil-square"></i>Manual Override Applied</div>@endif
                                        @else
                                            {{ $manualApplied ? number_format($effectivePrice, 4) : '—' }}
                                        @endif
                                    </td>
                                    <td class="so2-money-good">{{ number_format($effectivePrice, 4) }}</td>
                                    <td>{{ number_format($estimatedUnitCost, 4) }}</td>
                                    <td class="{{ $estimatedLineProfit >= 0 ? 'so2-money-good' : 'text-danger fw-bold' }}">{{ number_format($estimatedLineProfit, 2) }}</td>
                                    <td><span class="so2-status {{ $statusUi['class'] }}">{{ $statusUi['label'] }}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button type="button" class="dropdown-item so2-edit-description" data-id="{{ $item->id }}"><i class="bi bi-card-text me-2"></i>Edit quotation description</button></li>
                                                @if($sale->status === 'draft')
                                                    <li><button type="button" class="dropdown-item text-danger remove-item" data-id="{{ $item->id }}"><i class="bi bi-x-lg me-2"></i>{{ __('ui.remove') }}</button></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10"><div class="so2-empty"><i class="bi bi-box-seam"></i>No items added yet. Use <strong>Add Carton</strong> to add the first item.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="so2-info-strip"><i class="bi bi-info-circle me-2"></i>Operators can manually override an item price while the order is in draft. Leave the field empty to use the latest BOM/system price.</div>
                </div>

                <aside class="so2-card">
                    <div class="so2-card-head"><div class="so2-card-title"><i class="bi bi-receipt"></i>Order Summary</div></div>
                    <div class="so2-summary">
                        <div class="so2-summary-row"><span>Subtotal</span><strong>{{ $saleCurrencySymbol }} {{ number_format($grossSales, 2) }}</strong></div>
                        <div class="so2-summary-row"><span>Estimated Production Cost</span><strong>{{ $saleCurrencySymbol }} {{ number_format($estimatedCost, 2) }}</strong></div>
                        <hr class="my-2">
                        <div class="so2-summary-row profit"><span>Estimated Profit</span><strong>{{ $saleCurrencySymbol }} {{ number_format($estimatedProfit, 2) }}</strong></div>
                        <div class="so2-summary-row margin"><span>Margin</span><strong>{{ number_format($estimatedMargin, 1) }}%</strong></div>
                        @if($actualAvailable)
                            <hr class="my-2"><div class="so2-summary-row"><span>Actual Production Cost</span><strong>{{ $saleCurrencySymbol }} {{ number_format($actualCost, 2) }}</strong></div><div class="so2-summary-row profit"><span>Actual Profit</span><strong>{{ $saleCurrencySymbol }} {{ number_format($actualProfit, 2) }}</strong></div>
                        @endif
                        <div class="so2-summary-note"><i class="bi bi-info-circle me-1"></i>Totals update when a manual override is saved. Estimated profit becomes final only after actual FIFO production consumption is recorded.</div>
                    </div>
                </aside>
            </div>

            <div class="so2-tabs-card">
                <div class="so2-tabs">
                    <button class="so2-tab active" data-so2-tab="overview"><i class="bi bi-file-earmark-text"></i>Overview</button>
                    <button class="so2-tab" data-so2-tab="bom"><i class="bi bi-box"></i>BOM Breakdown</button>
                    <button class="so2-tab" data-so2-tab="costing"><i class="bi bi-layers"></i>Costing</button>
                    <button class="so2-tab" data-so2-tab="production"><i class="bi bi-gear"></i>Production</button>
                    <button class="so2-tab" data-so2-tab="pricing"><i class="bi bi-card-text"></i>Pricing Notes</button>
                </div>

                <div class="so2-panel active" data-so2-panel="overview">
                    <div class="so2-overview-grid">
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-file-earmark-text"></i>Order Information</div>
                            <div class="so2-kv"><span>Order Number</span><span>{{ $sale->sale_no }}</span></div><div class="so2-kv"><span>Customer</span><span>{{ $sale->customer->name ?? '-' }}</span></div><div class="so2-kv"><span>Order Date</span><span>{{ $sale->sale_date ? date('M d, Y', strtotime($sale->sale_date)) : '-' }}</span></div><div class="so2-kv"><span>Status</span><span>{{ ucfirst($sale->status) }}</span></div><div class="so2-kv"><span>Production Status</span><span>{{ $productionUi['label'] }}</span></div><div class="so2-kv"><span>Remarks</span><span>{{ $sale->notes ?: '—' }}</span></div>
                        </div>
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-info-circle"></i>Additional Information</div>
                            <div class="so2-kv"><span>Currency</span><span>{{ $currencyCode }}</span></div><div class="so2-kv"><span>Exchange Rate</span><span>1 USD = {{ number_format($exchangeRate,4) }} AFN</span></div><div class="so2-kv"><span>Total Items</span><span>{{ $sale->items->count() }} Products</span></div><div class="so2-kv"><span>Created</span><span>{{ optional($sale->created_at)->format('M d, Y H:i') }}</span></div><div class="so2-kv"><span>Last Updated</span><span>{{ optional($sale->updated_at)->format('M d, Y H:i') }}</span></div>
                        </div>
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-file-earmark-check"></i>Pricing Notes</div>
                            <div class="so2-pricing-note"><ul class="mb-0 ps-3"><li>Item prices can be manually overridden before order confirmation.</li><li>If no override is entered, the latest BOM/system price is used.</li><li>Manual override changes the selling price only; production cost still comes from BOM/inventory costing.</li><li>Estimated profit and margin use the effective selling price and may change after production.</li></ul></div>
                        </div>
                    </div>
                </div>

                <div class="so2-panel" data-so2-panel="bom">
                    <div id="so2BomEmpty" class="so2-empty"><i class="bi bi-check2-square"></i>Select an item using the checkbox above to view its BOM material breakdown.</div>
                    <div id="so2BomBreakdownBody" style="display:none"></div>
                </div>

                <div class="so2-panel" data-so2-panel="costing">
                    <div class="so2-cost-grid">
                        <div class="so2-cost-box"><div class="label">Customer Quotation Total</div><div class="value">{{ $saleCurrencySymbol }} {{ number_format($grossSales,2) }}</div></div>
                        <div class="so2-cost-box"><div class="label">Estimated Production Cost</div><div class="value">{{ $saleCurrencySymbol }} {{ number_format($estimatedCost,2) }}</div></div>
                        <div class="so2-cost-box"><div class="label">Estimated Profit</div><div class="value text-success">{{ $saleCurrencySymbol }} {{ number_format($estimatedProfit,2) }}</div></div>
                        <div class="so2-cost-box"><div class="label">Estimated Margin</div><div class="value text-success">{{ number_format($estimatedMargin,1) }}%</div></div>
                    </div>
                    @if($actualAvailable && !empty($productionVariance))
                        <div class="alert alert-light border mt-3 mb-0 small"><i class="bi bi-check-circle text-success me-2"></i>Actual FIFO production costs are available for this order. Production variance remains authoritative in the Production Order record.</div>
                    @else
                        <div class="alert alert-light border mt-3 mb-0 small"><i class="bi bi-info-circle text-primary me-2"></i>These values are estimates until production records actual FIFO material consumption.</div>
                    @endif
                </div>

                <div class="so2-panel" data-so2-panel="production">
                    <div class="so2-mini-card">
                        <div class="so2-mini-head"><i class="bi bi-gear"></i>Production Status</div>
                        <div class="d-flex align-items-center gap-2"><span class="so2-status {{ $productionUi['class'] }}">{{ $productionUi['label'] }}</span>@if($sale->productionOrder)<span class="text-muted small">Production Order #{{ $sale->productionOrder->id }}</span>@endif</div>
                        <div class="so2-production-actions">
                            @if($sale->productionOrder)<a class="btn btn-outline-primary btn-sm" href="{{ route('production-orders.show',$sale->productionOrder) }}"><i class="bi bi-eye me-1"></i>Open Production Order</a>@endif
                            @if($sale->status === 'confirmed' && !$sale->is_produced && $sale->productionOrder && $sale->productionOrder->status === 'pending')<a class="btn btn-warning btn-sm" href="{{ route('production-orders.show',$sale->productionOrder) }}"><i class="bi bi-calculator me-1"></i>Set Production Qty</a>@endif
                        </div>
                    </div>
                </div>

                <div class="so2-panel" data-so2-panel="pricing">
                    <div class="so2-overview-grid">
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-pencil-square"></i>Manual Override</div><div class="so2-pricing-note">Available only while the order is Draft. Enter a price on the item row to replace the selling price. Clear it to restore the saved system/BOM price.</div></div>
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-box"></i>BOM Cost</div><div class="so2-pricing-note">BOM and landed inventory costs remain independent of the selling-price override, preserving production costing and profit traceability.</div></div>
                        <div class="so2-mini-card"><div class="so2-mini-head"><i class="bi bi-printer"></i>Documents</div><div class="so2-pricing-note">Use the Print menu in the header to print either the customer quotation or the invoice after items have been added.</div></div>
                    </div>
                </div>
            </div>

            @foreach($sale->items as $item)
                <template id="so2-bom-template-{{ $item->id }}">
                    @php
                        $manualRows = is_array($item->manual_bom_snapshot ?? null) ? $item->manual_bom_snapshot : [];
                        $regularRows = $item->bom?->items ?? collect();
                        $breakdownTotal = 0;
                    @endphp
                    <div class="so2-breakdown-head">
                        <div class="so2-breakdown-title"><i class="bi bi-box text-primary me-2"></i>BOM Material Breakdown — {{ $item->product->name ?? 'Item' }} @if($item->bom)({{ $item->bom->code }})@endif</div>
                        <span class="so2-selected-chip">Showing BOM for selected item</span>
                    </div>
                    @if(count($manualRows) > 0)
                        <div class="table-responsive"><table class="so2-breakdown-table"><thead><tr><th>#</th><th>Material</th><th>Usage / Carton</th><th>Usage + Waste</th><th>Unit</th><th>Rate ({{ $currencyCode }})</th><th>Total Cost ({{ $currencyCode }})</th><th>Remark</th></tr></thead><tbody>
                        @foreach($manualRows as $idx => $row)
                            @php
                                $usage = (float)($row['kg_per_finished_unit'] ?? $row['quantity'] ?? 0);
                                $usageWaste = (float)($row['kg_with_wastage'] ?? $usage);
                                $costUsd = (float)($row['physical_cost_usd'] ?? 0);
                                $rateUsd = $usageWaste > 0 ? $costUsd / $usageWaste : 0;
                                $rateDisplay = $currencyCode === 'USD' ? $rateUsd : $rateUsd * $exchangeRate;
                                $rowCost = $usageWaste * $rateDisplay;
                                $breakdownTotal += $rowCost;
                            @endphp
                            <tr><td>{{ $idx+1 }}</td><td>{{ $row['material_name'] ?? 'Material' }}</td><td>{{ number_format($usage,6) }}</td><td>{{ number_format($usageWaste,6) }}</td><td>kg</td><td>{{ number_format($rateDisplay,4) }}</td><td>{{ number_format($rowCost,4) }}</td><td>{{ ($row['formula_type'] ?? '') === 'adhesive_mix' ? 'Adhesive mix' : 'Manual BOM snapshot' }}</td></tr>
                        @endforeach
                        </tbody></table></div>
                        <div class="so2-breakdown-total"><span>Total Material Cost (per carton)</span><strong>{{ $saleCurrencySymbol }} {{ number_format($breakdownTotal,4) }}</strong></div>
                    @elseif($regularRows->count())
                        <div class="table-responsive"><table class="so2-breakdown-table"><thead><tr><th>#</th><th>Material</th><th>Usage / Carton</th><th>Usage + Waste</th><th>Unit</th><th>Rate ({{ $currencyCode }})</th><th>Total Cost ({{ $currencyCode }})</th><th>Remark</th></tr></thead><tbody>
                        @foreach($regularRows as $idx => $bomItem)
                            @php
                                $usage = (float)$bomItem->calculateStockKgPerUnit();
                                $usageWaste = (float)$bomItem->calculateStockRequirement(1, true);
                                $rateUsd = (float)($bomItem->cost_per_unit_usd ?? 0);
                                $rateDisplay = $currencyCode === 'USD' ? $rateUsd : $rateUsd * $exchangeRate;
                                $rowCost = $usageWaste * $rateDisplay;
                                $breakdownTotal += $rowCost;
                                $unitLabel = $bomItem->stock_consumption_unit ?: ($bomItem->unit ?: 'kg');
                            @endphp
                            <tr><td>{{ $idx+1 }}</td><td>{{ $bomItem->material->name ?? 'Material' }}</td><td>{{ number_format($usage,6) }}</td><td>{{ number_format($usageWaste,6) }}</td><td>{{ $unitLabel }}</td><td>{{ number_format($rateDisplay,4) }}</td><td>{{ number_format($rowCost,4) }}</td><td>{{ $bomItem->formula_label }}</td></tr>
                        @endforeach
                        </tbody></table></div>
                        <div class="so2-breakdown-total"><span>Total Material Cost (per carton)</span><strong>{{ $saleCurrencySymbol }} {{ number_format($breakdownTotal,4) }}</strong></div>
                    @else
                        <div class="so2-empty"><i class="bi bi-exclamation-circle"></i>This sale item has no linked BOM material breakdown.</div>
                    @endif
                </template>
            @endforeach
        </div>
    </div>

    @if($sale->status === 'draft')
    <div class="offcanvas offcanvas-end so2-drawer" tabindex="-1" id="addCartonOffcanvas">
        <div class="offcanvas-header">
            <div><h5 class="offcanvas-title fw-bold"><i class="bi bi-box text-primary me-2"></i>Add Carton</h5><div class="text-muted small">Add a carton to this sale order</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="so2-mode-tabs"><button type="button" class="so2-mode-btn active" data-so2-add-mode="existing"><i class="bi bi-box me-1"></i>Existing BOM</button><button type="button" class="so2-mode-btn" data-so2-add-mode="quick"><i class="bi bi-pencil me-1"></i>Quick Quotation</button></div>

            <div class="so2-add-pane active" data-so2-add-pane="existing">
                <div class="so2-form-grid">
                    <div class="full"><label>{{ __('ui.product') }} *</label><select id="productSelect" class="form-select"><option value="">{{ __('ui.choose_product') }}</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                    <div class="full"><label>BOM / Pricing Method *</label><select id="bomSelect" class="form-select" disabled><option value="">{{ __('ui.select_product_first') }}</option></select><small class="text-muted" id="pricingModeHelp">{{ __('ui.choose_pricing_help') }}</small></div>
                    <div><label>{{ __('ui.quantity') }} *</label><input id="itemQty" type="number" class="form-control" value="1" min="1" step="1" oninput="recalculateLiveEstimate();updateAddTotals();"></div>
                    <div><label>Manual Unit Price Override ({{ $currencyCode }})</label><input id="manualUnitPrice" type="number" class="form-control" min="0" step="0.0001" placeholder="Leave empty for system price"></div>
                    <div><label>System / BOM Price ({{ $currencyCode }})</label><input id="unitPrice" type="number" class="form-control" value="0" readonly><small class="text-muted" id="priceSource">{{ __('ui.select_bom_auto') }}</small></div>
                    <div><label>{{ __('ui.exchange_rate') }}</label><input id="exchangeRate" type="number" class="form-control" value="{{ $exchangeRate }}" step="0.000001" min="0.000001" oninput="recalculateLiveEstimate();"></div>
                    <div class="full"><label>Quotation Description</label><textarea id="quotationDescription" class="form-control" rows="2" maxlength="2000" placeholder="Customer-facing description"></textarea></div>
                    <div class="full"><label>Line Total</label><input id="itemTotal" class="form-control" value="{{ $saleCurrencySymbol }} 0.00" readonly></div>
                </div>
                <div id="pricingModeNotice" class="pricing-mode-notice mt-3" style="display:none"><div class="mode-icon"><i class="bi bi-check2-circle"></i></div><div class="flex-grow-1"><div class="mode-title" id="pricingModeTitle"></div><div class="mode-copy" id="pricingModeCopy"></div></div><div class="mode-price" id="pricingModePrice"></div></div>
                <div class="d-flex justify-content-between align-items-center mt-3"><button type="button" class="btn btn-outline-primary btn-sm" onclick="openBomCalculator()"><i class="bi bi-calculator me-1"></i>New BOM</button><select id="manualBomTemplate" class="form-select form-select-sm" style="max-width:310px"></select></div>
                <div id="bomDetailsPreview" class="quote-estimator mt-3" style="display:none">
                    <div class="quote-estimator-header"><div><div class="eyebrow">{{ __('ui.manual_bom_pricing') }}</div><h6><i class="bi bi-calculator-fill me-2"></i>{{ __('ui.excel_material_estimator') }}</h6></div><div><span class="badge bg-light text-dark" id="bomCodeDisplay">-</span> <span class="badge bg-warning text-dark" id="bomVersionDisplay">v1.0</span> <span class="badge bg-success" id="costCurrencyLabel">{{ $currencyCode }}</span></div></div>
                    <div class="p-3"><div class="small text-muted mb-2" id="bomExchangeRateDisplay">1 USD = {{ number_format($exchangeRate,2) }} AFN</div><div id="bomMaterialsTableBody"></div></div>
                    <div class="quote-summary-grid"><div class="quote-summary-card"><div class="label">{{ __('ui.raw_paper_unit') }}</div><div class="value" id="materialCostDisplay">؋0.0000</div></div><div class="quote-summary-card"><div class="label">{{ __('ui.combined_work_unit') }}</div><div class="value" id="laborCostDisplay">؋0.0000</div></div><div class="quote-summary-card"><div class="label">{{ __('ui.print_unit') }}</div><div class="value" id="printCostDisplay">؋0.0000</div></div><div class="quote-summary-card"><div class="label">{{ __('ui.final_cost_unit') }}</div><div class="value" id="totalCostDisplay">؋0.0000</div></div><div class="quote-summary-card"><div class="label">{{ __('ui.order_quantity') }}</div><div class="value" id="estimateQtyDisplay">1</div></div><div class="quote-summary-card highlight"><div class="label">{{ __('ui.quotation_total') }}</div><div class="value" id="sellingPriceUsdDisplay">{{ $saleCurrencySymbol }}0.00</div></div></div>
                </div>
                <div class="so2-drawer-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button><button type="button" class="btn btn-primary" id="addItemBtn" disabled><i class="bi bi-plus-lg me-1"></i>Add to Sale</button></div>
            </div>

            <div class="so2-add-pane" data-so2-add-pane="quick" id="cartonSpecSection">
                <div class="so2-form-grid">
                    <div class="full"><label>Finished Carton Product *</label><select id="csProduct" class="form-select"><option value="">Select product...</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                    <div><label>Box Style</label><select id="csBoxStyle" class="form-select"></select></div><div><label>Unit</label><select id="csUnit" class="form-select"></select></div>
                    <div><label>Length *</label><input id="csLength" type="number" class="form-control" step="0.01" min="0.01"></div><div><label>Width *</label><input id="csWidth" type="number" class="form-control" step="0.01" min="0.01"></div><div><label>Height *</label><input id="csHeight" type="number" class="form-control" step="0.01" min="0.01"></div><div><label>Quantity *</label><input id="csQuantity" type="number" class="form-control" value="1" min="1"></div>
                    <div class="full"><label>Board Profile *</label><select id="csBoardProfile" class="form-select"></select><small id="csProfileHelp" class="text-muted"></small></div>
                    <div><label>Ply</label><input id="csPly" type="number" class="form-control" readonly></div><div><label>Flute</label><select id="csFlute" class="form-select"></select></div><div><label>Printing</label><select id="csPrinting" class="form-select"></select></div><div><label>Wastage %</label><input id="csWastage" type="number" class="form-control" value="5" min="0" max="100" step="0.1"></div>
                    <div><label>Quoted Unit Price Override</label><input id="csQuotedPrice" type="number" class="form-control" min="0" step="0.0001" placeholder="Optional"></div><div class="full"><label>Quotation Description</label><input id="csDescription" class="form-control" maxlength="2000"></div>
                </div>
                <div id="csError" class="alert alert-danger mt-3 py-2" style="display:none"></div>
                <div id="csSummary" style="display:none">
                    <div class="so2-quick-summary"><div class="so2-quick-box"><small>Unit Price</small><strong id="csUnitPrice">0</strong></div><div class="so2-quick-box"><small>Material Cost</small><strong id="csMaterialCost">0</strong></div><div class="so2-quick-box"><small>Paper Kg</small><strong id="csPaperKg">0</strong></div><div class="so2-quick-box"><small>Adhesive Kg</small><strong id="csAdhesiveKg">0</strong></div><div class="so2-quick-box"><small>Work / Profit</small><strong id="csWorkProfit">0</strong></div><div class="so2-quick-box"><small>Expected Profit</small><strong id="csExpectedProfit">0</strong></div><div class="so2-quick-box"><small>Order Value</small><strong id="csOrderValue">0</strong></div><div class="so2-quick-box"><small>Stock Status</small><strong id="csStockStatus">-</strong></div></div>
                    <div id="csShortageList" class="small mt-2"></div><div id="csAdvanced" class="so2-advanced"><div id="csAdvancedRows"></div></div>
                </div>
                <div class="so2-drawer-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button><button type="button" class="btn btn-outline-primary" id="csCalculateBtn"><i class="bi bi-calculator me-1"></i>Calculate</button><button type="button" class="btn btn-primary" id="csAddBtn" disabled><i class="bi bi-plus-lg me-1"></i>Add to Sale</button></div>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var activateTab=function(name){
                document.querySelectorAll('.so2-tab').forEach(function(b){b.classList.toggle('active',b.dataset.so2Tab===name)});
                document.querySelectorAll('.so2-panel').forEach(function(p){p.classList.toggle('active',p.dataset.so2Panel===name)});
            };
            document.querySelectorAll('.so2-tab').forEach(function(btn){btn.addEventListener('click',function(){activateTab(btn.dataset.so2Tab)})});
            document.querySelectorAll('.so2-item-selector').forEach(function(box){
                box.addEventListener('change',function(){
                    document.querySelectorAll('.so2-item-selector').forEach(function(other){if(other!==box)other.checked=false});
                    document.querySelectorAll('[data-so2-item-row]').forEach(function(r){r.classList.remove('selected')});
                    if(!box.checked){document.getElementById('so2BomBreakdownBody').style.display='none';document.getElementById('so2BomEmpty').style.display='block';return}
                    var row=document.querySelector('[data-so2-item-row="'+box.value+'"]'); if(row)row.classList.add('selected');
                    var tpl=document.getElementById('so2-bom-template-'+box.value);
                    var body=document.getElementById('so2BomBreakdownBody');
                    body.innerHTML=tpl?tpl.innerHTML:'<div class="so2-empty">No BOM breakdown available.</div>';
                    body.style.display='block';document.getElementById('so2BomEmpty').style.display='none';activateTab('bom');
                });
            });
            document.querySelectorAll('[data-so2-add-mode]').forEach(function(btn){btn.addEventListener('click',function(){
                document.querySelectorAll('[data-so2-add-mode]').forEach(function(x){x.classList.toggle('active',x===btn)});
                document.querySelectorAll('[data-so2-add-pane]').forEach(function(p){p.classList.toggle('active',p.dataset.so2AddPane===btn.dataset.so2AddMode)});
            })});
            document.querySelectorAll('.so2-edit-description').forEach(function(btn){btn.addEventListener('click',function(){
                var field=document.querySelector('.quotation-description-input[data-id="'+btn.dataset.id+'"]'); if(field){field.style.display=field.style.display==='block'?'none':'block';if(field.style.display==='block')field.focus()}
            })});
            var search=document.getElementById('so2ItemSearch');
            var itemFilter='all';
            var applyItemFilters=function(){
                var q=search?search.value.toLowerCase():'';
                document.querySelectorAll('[data-so2-item-row]').forEach(function(r){
                    var textMatch=(r.dataset.search||'').includes(q);
                    var filterMatch=itemFilter==='all'||(itemFilter==='manual'&&r.dataset.manual==='1')||(itemFilter==='bom'&&r.dataset.hasBom==='1');
                    r.style.display=textMatch&&filterMatch?'':'none';
                });
            };
            if(search)search.addEventListener('input',applyItemFilters);
            document.querySelectorAll('.so2-filter-option').forEach(function(btn){btn.addEventListener('click',function(){
                itemFilter=btn.dataset.so2Filter||'all';
                var label=document.getElementById('so2FilterLabel');
                if(label)label.textContent=itemFilter==='all'?'Filter':btn.textContent.trim();
                applyItemFilters();
            })});
        });
    </script>

    {{-- ─── CONFIRMATION MODAL ─── --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 25px 60px rgba(0,0,0,0.2); max-height: 95vh;">

                {{-- ─── MODAL HEADER ─── --}}
                <div class="modal-header" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; border: none; padding: 1.25rem 2rem; flex-shrink: 0;">
                    <div>
                        <h5 class="modal-title fw-bold" style="font-size: 1.2rem;">
                            <i class="bi bi-check2-circle me-2"></i>
                            Confirm Sale Order
                        </h5>
                        <p class="mb-0 mt-0" style="font-size: 0.8rem; opacity: 0.85;">
                            <i class="bi bi-shield-check me-1"></i>
                            Review order details before confirming
                        </p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
                </div>

                <form id="confirmForm" method="POST">
                    @csrf
                    <div class="modal-body" style="padding: 1.25rem 2rem; background: #fafbfc; flex: 1; overflow: hidden;">

                        <div class="row g-3" style="height: 100%;">

                            {{-- ─── LEFT COLUMN ─── --}}
                            <div class="col-lg-7" style="overflow-y: auto; max-height: calc(95vh - 180px); padding-right: 1rem;">

                                {{-- Order Summary --}}
                                <div style="background: white; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                        <div style="width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5;">
                                            <i class="bi bi-receipt" style="font-size: 0.9rem;"></i>
                                        </div>
                                        <h6 style="font-weight: 700; color: #1e293b; margin: 0; font-size: 0.85rem;">{{ __('ui.order_summary') }}</h6>
                                        <span style="margin-left: auto; font-size: 0.65rem; color: #94a3b8;">
                                            <i class="bi bi-clock me-1"></i> Ready to confirm
                                        </span>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px;">
                                                <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                    <i class="bi bi-hash me-1"></i> {{ __('ui.sale_number') }}
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b;" id="confirmSaleNo">-</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px;">
                                                <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                    <i class="bi bi-person me-1"></i> {{ __('ui.customer') }}
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b;" id="confirmCustomer">-</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px;">
                                                <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                    <i class="bi bi-box me-1"></i> {{ __('ui.items') }}
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b;">
                                                    <span id="confirmItems">-</span> (<span id="confirmQty">-</span> qty)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px;">
                                                <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                    <i class="bi bi-currency-exchange me-1"></i> {{ __('ui.currency') }}
                                                </div>
                                                <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b;" id="confirmCurrency">-</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Amount Details --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div style="background: white; border-radius: 10px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                        <i class="bi bi-cart me-1"></i> {{ __('ui.subtotal') }}
                                                    </div>
                                                    <div style="font-weight: 700; font-size: 1.1rem; color: #1e293b;" id="confirmSubtotal">-</div>
                                                </div>
                                                <div style="width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5;">
                                                    <i class="bi bi-cart-check" style="font-size: 0.85rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div style="background: white; border-radius: 10px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <div style="font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.3px;">
                                                        <i class="bi bi-currency-dollar me-1"></i> USD Subtotal
                                                    </div>
                                                    <div style="font-weight: 700; font-size: 1.1rem; color: #1e293b;" id="confirmUsdSubtotal">-</div>
                                                </div>
                                                <div style="width: 32px; height: 32px; background: #d1fae5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #059669;">
                                                    <i class="bi bi-currency-dollar" style="font-size: 0.85rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Discount & Payment --}}
                                <div style="background: white; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem; border: 1px solid #e5e7eb;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                        <div style="width: 28px; height: 28px; background: #fef3c7; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #d97706;">
                                            <i class="bi bi-wallet2" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <h6 style="font-weight: 700; color: #1e293b; margin: 0; font-size: 0.8rem;">{{ __('ui.discount_payment') }}</h6>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label" style="font-size: 0.65rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">
                                                <i class="bi bi-tag me-1"></i> {{ __('ui.discount') }}
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.55rem; background: #e5e7eb !important; color: #475569;">
                                                    <span id="discountCurrencySymbol">$</span>
                                                </span>
                                            </label>
                                            <input type="number" name="discount_amount" id="discountAmount" class="form-control"
                                                   value="0" step="0.01" min="0" oninput="updateConfirmTotals()"
                                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 0.35rem 0.6rem; font-size: 0.85rem; height: 36px;">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label" style="font-size: 0.65rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">
                                                <i class="bi bi-cash me-1"></i> Advance Payment
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.55rem; background: #e5e7eb !important; color: #475569;">
                                                    <span id="advanceCurrencySymbol">$</span>
                                                </span>
                                            </label>
                                            <input type="number" name="advance_payment" id="advancePayment" class="form-control"
                                                   value="0" step="0.01" min="0" oninput="updateConfirmTotals()"
                                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 0.35rem 0.6rem; font-size: 0.85rem; height: 36px;">
                                        </div>
                                    </div>
                                </div>

                                {{-- Transaction Summary --}}
                                <div style="background: #f0fdf4; border-radius: 10px; padding: 0.6rem 1rem; border: 1px solid #bbf7d0; font-size: 0.8rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.15rem;">
                                        <i class="bi bi-info-circle-fill" style="color: #059669; font-size: 0.9rem;"></i>
                                        <strong style="color: #065f46; font-size: 0.8rem;">{{ __('ui.transaction_summary') }}</strong>
                                    </div>
                                    <div id="txnSummary" style="color: #475569; line-height: 1.5; font-size: 0.8rem;">
                                        <span>1. Debit Customer: <strong id="txnDebit" style="color: #1e293b;">$0.00</strong></span>
                                        <span class="mx-2">|</span>
                                        <span id="txnAdvanceText" style="color: #94a3b8;">{{ __('ui.no_advance_payment') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- ─── RIGHT COLUMN ─── --}}
                            <div class="col-lg-5" style="display: flex; flex-direction: column;">

                                {{-- Totals Preview --}}
                                <div style="display: flex; flex-direction: column; gap: 0.6rem; flex: 1;">
                                    <div style="background: linear-gradient(135deg, #eef2ff, #e0e7ff); border-radius: 12px; padding: 0.75rem 1rem; border: 1px solid #a5b4fc;">
                                        <div style="font-size: 0.6rem; text-transform: uppercase; color: #4f46e5; font-weight: 700; letter-spacing: 0.3px;">
                                            <i class="bi bi-cash-stack me-1"></i> {{ __('ui.grand_total') }}
                                        </div>
                                        <div style="font-weight: 800; font-size: 1.5rem; color: #4f46e5;" id="confirmGrandTotal">-</div>
                                    </div>

                                    <div style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border-radius: 12px; padding: 0.75rem 1rem; border: 1px solid #6ee7b7;">
                                        <div style="font-size: 0.6rem; text-transform: uppercase; color: #059669; font-weight: 700; letter-spacing: 0.3px;">
                                            <i class="bi bi-check-circle me-1"></i> Advance Paid
                                        </div>
                                        <div style="font-weight: 800; font-size: 1.5rem; color: #059669;" id="confirmAdvancePaid">-</div>
                                    </div>

                                    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 12px; padding: 0.75rem 1rem; border: 1px solid #fcd34d;">
                                        <div style="font-size: 0.6rem; text-transform: uppercase; color: #92400e; font-weight: 700; letter-spacing: 0.3px;">
                                            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('ui.due_amount') }}
                                        </div>
                                        <div style="font-weight: 800; font-size: 1.5rem; color: #92400e;" id="confirmDueAmount">-</div>
                                    </div>
                                </div>

                                {{-- Production Option --}}
                                <div style="background: #f0fdf4; border-radius: 12px; padding: 0.75rem 1rem; margin-top: 0.6rem; border: 1px solid #bbf7d0;">
                                    <div class="form-check form-switch d-flex align-items-center gap-2" style="margin: 0;">
                                        <input type="checkbox" class="form-check-input" name="start_production"
                                               id="startProductionCheckbox" value="1"
                                               style="width: 2.5rem; height: 1.4rem; cursor: pointer; margin: 0; flex-shrink: 0;">
                                        <div>
                                            <label class="form-check-label fw-semibold" for="startProductionCheckbox" style="font-size: 0.8rem; cursor: pointer; color: #065f46;">
                                                <i class="bi bi-gear me-1" style="color: #059669;"></i>
                                                {{ __('ui.start_production') }}
                                            </label>
                                            <p class="mb-0 text-muted" style="font-size: 0.65rem; margin-top: 0.05rem;">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Consumes raw materials from inventory
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div style="margin-top: 0.6rem;">
                                    <label class="form-label" style="font-size: 0.65rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem;">
                                        <i class="bi bi-file-text me-1"></i> {{ __('ui.notes') }}
                                    </label>
                                    <textarea name="notes" class="form-control" rows="2"
                                              placeholder="Add confirmation notes..."
                                              style="border-color: #e5e7eb; border-radius: 8px; padding: 0.4rem 0.6rem; font-size: 0.8rem; resize: none; height: 50px;"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ─── MODAL FOOTER ─── --}}
                    <div class="modal-footer" style="background: white; border-top: 1px solid #e5e7eb; padding: 0.75rem 2rem; border-radius: 0 0 20px 20px; flex-shrink: 0;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; padding: 0.4rem 1.25rem; font-weight: 600; font-size: 0.85rem;">
                            <i class="bi bi-x-lg me-1"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success" id="confirmSubmitBtn"
                                style="border-radius: 8px; padding: 0.4rem 1.75rem; font-weight: 700; font-size: 0.85rem; background: linear-gradient(135deg, #059669, #10b981); border: none; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);">
                            <i class="bi bi-check2-circle me-1"></i> Confirm Sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── STATUS UPDATE FORM ─── --}}
    <form id="statusForm" method="POST" action="{{ route('admin.sales.update-status', $sale->id) }}" style="display: none;">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" id="statusInput">
    </form>

    {{-- ─── DELETE MODAL ─── --}}
    <div class="modal fade delete-modal" id="deleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Delete Sale Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="deleteForm" method="POST" action="{{ route('admin.sales.destroy', $sale->id) }}">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body">
                        {{-- Sale Information --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--sale-gray-200);">
                            <div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--sale-gray-400); font-weight: 700;">
                                    {{ __('ui.sale_number') }}
                                </div>
                                <div style="font-weight: 700; font-size: 1.1rem; color: var(--sale-gray-800);">
                                    {{ $sale->sale_no }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--sale-gray-400); font-weight: 700;">
                                    {{ __('ui.status') }}
                                </div>
                                <span class="sale-status-badge {{ $statusConfig[$sale->status]['class'] ?? 'draft' }}" style="font-size: 0.7rem; padding: 0.2rem 0.75rem;">
                                    <i class="bi {{ $statusConfig[$sale->status]['icon'] ?? 'bi-question-circle' }}"></i>
                                    {{ ucfirst($sale->status ?? 'Unknown') }}
                                </span>
                            </div>
                            <div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--sale-gray-400); font-weight: 700;">
                                    {{ __('ui.total') }}
                                </div>
                                <div style="font-weight: 700; font-size: 1.1rem; color: var(--sale-gray-800);">
                                    {{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}
                                </div>
                            </div>
                        </div>

                        {{-- Warning Box --}}
                        <div class="delete-warning-box">
                            <div class="warning-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div class="warning-text">
                                <strong>{{ __('ui.warning_undo') }}</strong>
                                <ul>
                                    @if ($sale->status === 'draft')
                                        <li>{{ __('ui.this_is_a') }} <strong>DRAFT</strong> {{ __('ui.sale_permanently_removed') }}</li>
                                    @else
                                        <li>{{ __('ui.this_sale_is') }} <strong>{{ strtoupper($sale->status) }}</strong></li>
                                        <li>{{ __('ui.all_stock_quantities') }} <strong>{{ __('ui.restored') }}</strong></li>
                                        <li>{{ __('ui.all_transactions') }} <strong>{{ __('ui.reversed') }}</strong></li>
                                        <li>{{ __('ui.all_sale_records') }} <strong>{{ __('ui.removed') }}</strong></li>
                                    @endif
                                    <li>{{ __('ui.this_action_is') }} <strong>{{ __('ui.permanent') }}</strong> {{ __('ui.cannot_be_reversed') }}</li>
                                </ul>
                            </div>
                            <div style="clear: both;"></div>
                        </div>

                        {{-- Deletion Reason --}}
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; font-size: 0.875rem;">
                                <i class="bi bi-pencil"></i> Reason for Deletion
                                <span style="font-weight: 400; color: var(--sale-gray-400); font-size: 0.75rem;">{{ __('ui.optional_parentheses') }}</span>
                            </label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="e.g., Duplicate order, Customer requested cancellation, Wrong items, etc."></textarea>
                        </div>

                        {{-- Password Confirmation --}}
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; font-size: 0.875rem;">
                                <i class="bi bi-lock-fill lock-icon locked" id="lockIcon"></i>
                                {{ __('ui.confirm_password') }} <span class="text-danger">*</span>
                                <span style="font-weight: 400; color: var(--sale-gray-400); font-size: 0.7rem;">(Default: Delete@123)</span>
                            </label>
                            <div class="password-input-group">
                                <input type="password" id="deletePassword" name="password" class="form-control"
                                       placeholder="{{ __('ui.confirm_delete_password') }}" autocomplete="current-password"
                                       required>
                                <button type="button" class="toggle-password" id="togglePassword" title="{{ __('ui.toggle_password') }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="passwordError" class="text-danger" style="font-size: 0.8125rem; margin-top: 0.25rem; display: none;">
                                <i class="bi bi-exclamation-circle"></i> Incorrect password. Please try again.
                            </div>
                            <div class="attempt-counter" id="attemptCounter" style="display: none;">
                                <span>{{ __('ui.attempts_remaining') }} <span class="attempts-remaining" id="attemptsRemaining">3</span></span>
                            </div>
                        </div>

                        {{-- Confirmation Checkbox --}}
                        <div class="mb-3">
                            <label class="delete-checkbox-label">
                                <input type="checkbox" id="confirmDelete" required>
                                <span class="checkbox-text">
                                    I understand that this action <strong>{{ __('ui.cannot_be_undone') }}</strong> and will permanently
                                    delete sale order <strong>#{{ $sale->sale_no }}</strong>
                                </span>
                            </label>
                        </div>

                        {{-- Error/Success Messages --}}
                        <div class="delete-error-message" id="deleteErrorMessage">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <span id="deleteErrorText"></span>
                        </div>
                        <div class="delete-success-message" id="deleteSuccessMessage">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <span id="deleteSuccessText"></span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn-delete-sale" id="deleteSubmitBtn">
                            <span class="spinner"></span>
                            <span class="btn-text"><i class="bi bi-trash3"></i> {{ __('ui.delete_sale') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── BOM CALCULATOR MODAL ─── --}}
    <div class="modal fade bom-calculator-modal" id="bomCalculatorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 25px 60px rgba(0,0,0,0.2); max-height: 95vh;">

                {{-- ─── MODAL HEADER ─── --}}
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" style="font-size: 1.2rem;">
                            <i class="bi bi-calculator-fill me-2"></i>
                            {{ __('ui.bom_calculator') }}
                        </h5>
                        <p class="mb-0 mt-0" style="font-size: 0.8rem; opacity: 0.85;">
                            <i class="bi bi-boxes me-1"></i>
                            Calculate BOM cost using Excel formula and save as template
                        </p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
                </div>

                <form id="bomCalculatorForm">
                    @csrf
                    <div class="modal-body">

                        {{-- ─── BASIC INFORMATION ─── --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.bom_name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="modalBomName" class="form-control" placeholder="Enter BOM name..." value="Calculated BOM">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.product') }} <span class="text-danger">*</span></label>
                                <select id="modalProduct" class="form-select select2-modal-product" onchange="loadModalBOMItems()">
                                    <option value="">{{ __('ui.select_product') }}</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.formula_type') }}</label>
                                <select id="modalFormulaType" class="form-select" onchange="toggleModalFormulaFields(); loadModalBOMItems();">
                                    <option value="carton_3d">{{ __('ui.carton_3d_icon') }}</option>
                                    <option value="cut_roll">{{ __('ui.cut_roll_icon') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- ─── RAW MATERIALS SECTION (EXACTLY FROM BOM CREATE) ─── --}}
                        <div class="form-section">
                            <div class="section-title">
                                <i class="bi bi-box-seam"></i> Raw Materials
                                <span class="badge bg-primary ms-2" id="modalItemCount">0</span>
                                <span class="text-muted ms-2" style="font-weight: 400; font-size: 0.7rem;">
                                    <i class="bi bi-info-circle"></i> Excel-based formulas calculate the cost of one carton/material line
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="modal-total-cost-display" id="modalTotalMaterialCost">
                                    Material: $0.00 USD <span class="text-muted">(؋0.00 AFN)</span>
                                </span>
                                <button type="button" class="btn btn-primary btn-sm" id="modalAddItem">
                                    <i class="bi bi-plus-circle me-1"></i> Add Material
                                </button>
                            </div>

                            <div id="modalItemsContainer">
                                <!-- Items will be added here dynamically -->
                            </div>

                            <div id="modalNoItemsMessage" class="text-center text-muted py-4">
                                <i class="bi bi-box-seam" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                No materials added yet. Click "Add Material" to start building your BOM.
                            </div>
                        </div>

                        {{-- ─── EXCHANGE RATE ─── --}}
                        <div class="row g-3 mt-2" id="modalExchangeRateContainer">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.exchange_rate_usd_afn') }}</label>
                                <input type="number" id="modalExchangeRate" class="form-control"
                                       value="{{ $exchangeRate ?? 85 }}" step="0.000001" min="0.000001" oninput="calculateModalBOM()">
                                <small class="text-muted" id="modalExchangeRateInfo">1 USD = ? AFN</small>
                            </div>
                        </div>

                        {{-- ─── CALCULATION STEPS DISPLAY ─── --}}
                        <div class="mt-3" id="modalCalculatorSteps" style="display: none; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;">
                            <h6 class="mb-2"><i class="bi bi-list-steps me-2"></i>{{ __('ui.calculation_steps') }}</h6>
                            <div id="modalCalculatorStepsContent"></div>
                        </div>
                    </div>

                    {{-- ─── MODAL FOOTER ─── --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; padding: 0.4rem 1.25rem; font-weight: 600; font-size: 0.85rem;">
                            <i class="bi bi-x-lg me-1"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="modalCalculateBtn" style="border-radius: 8px; padding: 0.4rem 1.75rem; font-weight: 700; font-size: 0.85rem;" onclick="calculateModalBOM()">
                            <i class="bi bi-calculator me-1"></i> Calculate
                        </button>
                        <button type="button" class="btn btn-success" id="modalSaveBtn" style="border-radius: 8px; padding: 0.4rem 1.75rem; font-weight: 700; font-size: 0.85rem; background: linear-gradient(135deg, #059669, #10b981); border: none; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);" onclick="saveModalBOM()">
                            <i class="bi bi-save me-1"></i> Save & Select BOM
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        // ─── GLOBAL VARIABLES ───
        let saleId = {{ $sale->id }};
        let currencySymbol = '{{ $currencySymbol }}';
        let currencyCode = '{{ $currencyCode }}';
        let isUSD = {{ $isUSD ? 'true' : 'false' }};
        let exchangeRate = {{ $exchangeRate ?? 85 }};
        let currentProductId = null;
        let currentBOMData = null;
        let currentStockData = null;
        let currentQuoteSnapshot = [];
        let pricingMode = null;
        let manualTemplateBomId = null;

        // ─── ADHESIVE MIX (MIXING MATERIALS) ───
        const cartonAdhesiveConfig = @json(config('carton.adhesive'));
        const cartonSqInchToM2 = {{ config('carton.sq_inch_to_m2') }};

        function adhesiveRowFormula(parameters) {
            const length = Math.max(parseFloat(parameters.length) || 0, 0);
            const width = Math.max(parseFloat(parameters.width) || 0, 0);
            const height = Math.max(parseFloat(parameters.height) || 0, 0);
            const glueLines = Math.max(parseFloat(parameters.glueLines) || 0, 0);
            const dryGlueGsm = Math.max(parseFloat(parameters.dryGlueGsm) || 0, 0);
            const glueWastage = Math.max(parseFloat(parameters.glueWastage) || 0, 0);
            const solids = Math.max(parseFloat(parameters.solids) || 0, 0);
            const recipe = Math.max(parseFloat(parameters.recipe) || 0, 0);

            if (length <= 0 || width <= 0 || height <= 0) {
                return { boardAreaM2: 0, wetGlueKg: 0, ingredientKg: 0, reelLength: 0, reelHeight: 0 };
            }

            const reelLength = ((length + width) * 2) + 4;
            const reelHeight = width + height + 1;
            const boardAreaM2 = reelLength * reelHeight * cartonSqInchToM2;
            const dryGlueGrams = boardAreaM2 * glueLines * dryGlueGsm;
            const dryGlueWithWastage = dryGlueGrams * (1 + (glueWastage / 100));
            const wetGlueKg = solids > 0 ? (dryGlueWithWastage / 1000) / (solids / 100) : 0;

            return {
                boardAreaM2: boardAreaM2,
                wetGlueKg: wetGlueKg,
                ingredientKg: wetGlueKg * recipe,
                reelLength: reelLength,
                reelHeight: reelHeight,
            };
        }

        function adhesiveSnapshotParameters(material, f, overrides) {
            const adhesive = material.adhesive || {};
            overrides = overrides || {};

            return {
                length: overrides.length !== undefined ? overrides.length : f.length,
                width: overrides.width !== undefined ? overrides.width : f.width,
                height: overrides.height !== undefined ? overrides.height : f.height,
                glueLines: overrides.glueLines !== undefined ? overrides.glueLines : (adhesive.glue_lines || cartonAdhesiveConfig.glue_lines),
                dryGlueGsm: overrides.dryGlueGsm !== undefined ? overrides.dryGlueGsm : (adhesive.dry_glue_gsm_per_line || cartonAdhesiveConfig.dry_glue_gsm_per_line),
                glueWastage: overrides.glueWastage !== undefined ? overrides.glueWastage : (adhesive.glue_wastage_percentage || cartonAdhesiveConfig.glue_wastage_percentage),
                solids: overrides.solids !== undefined ? overrides.solids : (adhesive.adhesive_solids_percentage || cartonAdhesiveConfig.adhesive_solids_percentage),
                recipe: overrides.recipe !== undefined ? overrides.recipe : (adhesive.recipe_fraction !== undefined ? adhesive.recipe_fraction : 0),
                recipeKey: adhesive.recipe_key || null,
            };
        }

        // ─── BOM CALCULATOR MODAL VARIABLES ───
        let modalItemCounter = 0;
        const defaultModalExchangeRate = {{ $exchangeRate ?? 85 }};

        // ─── PRODUCT BOMS DATA (GLOBAL) ───
        var productBomsData = @json($productBoms);

        // ─── FORMAT FUNCTIONS ───
        function numberFormat(value) {
            return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function formatCurrency(value) {
            return currencySymbol + ' ' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function effectiveUnitPrice() {
            const manual = parseFloat($('#manualUnitPrice').val()) || 0;
            const automatic = parseFloat($('#unitPrice').val()) || 0;
            return manual > 0 ? manual : automatic;
        }

        function updateAddTotals() {
            const qty = parseFloat($('#itemQty').val()) || 0;
            const unitPrice = effectiveUnitPrice();
            const total = qty * unitPrice;
            const symbol = currencySymbol || '$';

            if (total > 0) {
                $('#itemTotal').val(symbol + ' ' + total.toFixed(2));
                $('#itemTotal').css('color', 'var(--sale-primary)');
                $('#itemTotal').css('font-weight', '700');
            } else {
                $('#itemTotal').val(symbol + ' 0.00');
                $('#itemTotal').css('color', 'var(--sale-gray-500)');
                $('#itemTotal').css('font-weight', '400');
            }
        }

        // ─── NUMERIC HELPER ───
        function numeric(obj, keys, fallback) {
            if (!obj || typeof obj !== 'object') return fallback || 0;
            for (var i = 0; i < keys.length; i++) {
                var value = parseFloat(obj[keys[i]]);
                if (!isNaN(value) && isFinite(value)) return value;
            }
            return fallback || 0;
        }

        // ─── GET BOMS FOR PRODUCT ───
        function getBomsForProduct(productId) {
            if (!productId) return [];
            return productBomsData[productId] || [];
        }

        // ─── CHECK IF PRODUCT HAS BOMS ───
        function productHasBOMs(productId) {
            var boms = getBomsForProduct(productId);
            return boms && boms.length > 0;
        }

        // ─── GET SAVED BOM PRICE (SELLING PRICE) ───
        function getSavedBomPrice(bom) {
            var afnPrice = numeric(bom, ['selling_price_afn', 'selling_price', 'net_rate_afn', 'total_cost_afn'], 0);
            var usdPrice = numeric(bom, ['selling_price_usd', 'total_cost_usd'], 0);

            if (afnPrice <= 0 && usdPrice <= 0) {
                var materialCostAfn = numeric(bom, ['total_cost_afn', 'material_cost_afn'], 0);
                var materialCostUsd = numeric(bom, ['total_cost_usd', 'material_cost_usd'], 0);
                var profitMargin = numeric(bom, ['profit_margin_percentage'], 0) / 100;
                var rate = Math.max(parseFloat($('#exchangeRate').val()) || numeric(bom, ['exchange_rate'], exchangeRate || 85), 0.000001);

                if (currencyCode === 'USD') {
                    var costToUse = materialCostUsd > 0 ? materialCostUsd : (materialCostAfn / rate);
                    afnPrice = costToUse * rate * (1 + profitMargin);
                    usdPrice = costToUse * (1 + profitMargin);
                } else {
                    var costToUse = materialCostAfn > 0 ? materialCostAfn : (materialCostUsd * rate);
                    afnPrice = costToUse * (1 + profitMargin);
                    usdPrice = (costToUse / rate) * (1 + profitMargin);
                }
            }

            var rate = Math.max(parseFloat($('#exchangeRate').val()) || numeric(bom, ['exchange_rate'], exchangeRate || 85), 0.000001);

            if (currencyCode === 'USD') {
                return usdPrice > 0 ? usdPrice : (afnPrice / rate);
            }
            return afnPrice > 0 ? afnPrice : (usdPrice * rate);
        }

        // ─── LOAD BOMS FOR PRODUCT ───
        function loadBomsForProduct(productId) {
            var $bomSelect = $('#bomSelect');
            var $bomPreview = $('#bomDetailsPreview');
            var $notice = $('#pricingModeNotice');

            $bomSelect.prop('disabled', true).html('<option value="">' + @json(__('ui.loading_pricing_methods')) + '</option>');
            $bomPreview.hide();
            $notice.hide();
            pricingMode = null;
            manualTemplateBomId = null;
            currentBOMData = null;
            $('#unitPrice').val(0);
            $('#priceSource').text(@json(__('ui.select_pricing_method')));
            $('#addItemBtn').prop('disabled', true);

            if (!productId) {
                $bomSelect.prop('disabled', true).html('<option value="">{{ __('ui.select_product_first') }}</option>');
                return;
            }

            var boms = getBomsForProduct(productId);
            var options = '';

            if (!boms || boms.length === 0) {
                options = '<option value="">{{ __('ui.no_bom_product') }}</option>';
                $bomSelect.html(options).prop('disabled', true);

                $('#pricingModeHelp').html(
                    '<span class="text-warning">' +
                    '<i class="bi bi-exclamation-triangle me-1"></i>' +
                    'No active BOM found for this product. ' +
                    '<a href="{{ route("bom.create") }}?product_id=' + productId + '" class="text-primary">' +
                    'Create BOM' +
                    '</a>' +
                    '</span>'
                );

                $('#addItemBtn').prop('disabled', true);
                return;
            }

            $('#pricingModeHelp').text(@json(__('ui.choose_pricing_help')));

            options = '<option value="">' + @json(__('ui.choose_pricing_method')) + '</option>';

            var hasMaterials = false;
            for (var i = 0; i < boms.length; i++) {
                if (boms[i].materials && boms[i].materials.length > 0) {
                    hasMaterials = true;
                    break;
                }
            }

            if (hasMaterials) {
                options += '<option value="__manual__" data-mode="manual">{{ __('ui.manual_bom_calculate') }}</option>';
            }

            options += '<optgroup label="Saved BOM Prices">';
            for (var j = 0; j < boms.length; j++) {
                var bom = boms[j];
                var savedPrice = getSavedBomPrice(bom);
                var materialCount = bom.materials ? bom.materials.length : 0;

                var hasStock = false;
                if (bom.materials) {
                    for (var k = 0; k < bom.materials.length; k++) {
                        if (bom.materials[k].inventory_cost_found === true) {
                            hasStock = true;
                            break;
                        }
                    }
                }

                var stockIcon = hasStock ? '✅' : '⚠️';

                options += '<option value="' + bom.id + '"' +
                    ' data-mode="saved"' +
                    ' data-code="' + (bom.code || 'N/A') + '"' +
                    ' data-version="' + (bom.version || '1.0') + '"' +
                    ' data-exchange="' + (bom.exchange_rate || 85) + '"' +
                    ' data-saved-price="' + savedPrice + '"' +
                    ' data-materials="' + materialCount + '"' +
                    ' data-has-stock="' + hasStock + '">' +
                    (bom.code || 'N/A') + ' — ' + (bom.name || 'Untitled') + ' (v' + (bom.version || '1.0') + ')' +
                    ' ' + stockIcon + ' ' + materialCount + ' materials' +
                    '</option>';
            }
            options += '</optgroup>';

            $bomSelect.html(options).prop('disabled', false).trigger('change.select2');
            populateManualTemplateOptions(productId);

            $('#addItemBtn').prop('disabled', true);
        }

        // ─── POPULATE MANUAL TEMPLATE OPTIONS ───
        function populateManualTemplateOptions(productId) {
            var boms = getBomsForProduct(productId);
            var options = '';

            if (!boms || boms.length === 0) {
                options = '<option value="">{{ __('ui.no_bom_template') }}</option>';
                $('#manualBomTemplate').html(options);
                manualTemplateBomId = null;
                return;
            }

            var validBoms = [];
            for (var i = 0; i < boms.length; i++) {
                if (boms[i].materials && boms[i].materials.length > 0) {
                    validBoms.push(boms[i]);
                }
            }

            if (validBoms.length === 0) {
                options = '<option value="">{{ __('ui.no_bom_material_templates') }}</option>';
                $('#manualBomTemplate').html(options);
                manualTemplateBomId = null;
                return;
            }

            for (var j = 0; j < validBoms.length; j++) {
                var bom = validBoms[j];
                var materialCount = bom.materials ? bom.materials.length : 0;

                var hasStock = false;
                if (bom.materials) {
                    for (var k = 0; k < bom.materials.length; k++) {
                        if (bom.materials[k].inventory_cost_found === true) {
                            hasStock = true;
                            break;
                        }
                    }
                }

                var stockIcon = hasStock ? '✅' : '⚠️';

                options += '<option value="' + bom.id + '">' +
                    (bom.code || 'N/A') + ' — ' + (bom.name || 'Untitled') + ' (v' + (bom.version || '1.0') + ')' +
                    ' ' + stockIcon + ' ' + materialCount + ' materials' +
                    '</option>';
            }

            $('#manualBomTemplate').html(options);
            manualTemplateBomId = validBoms.length > 0 ? String(validBoms[0].id) : null;
        }

        // ─── OPEN MANUAL BOM ESTIMATOR ───
        function openManualBomEstimator() {
            var productId = $('#productSelect').val();
            var boms = getBomsForProduct(productId);

            if (!boms || boms.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_bom_template_available')),
                    html: '<p>' + @json(__('ui.no_active_bom')) + '</p>' +
                        '<p class="text-muted">' + @json(__('ui.create_bom_or_saved')) + '</p>' +
                        '<div class="mt-3">' +
                        '<a href="{{ route("bom.create") }}?product_id=' + productId + '" class="btn btn-primary">' +
                        '<i class="bi bi-plus-circle"></i> Create BOM' +
                        '</a>' +
                        '</div>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4f46e5'
                });
                $('#bomDetailsPreview').hide();
                $('#unitPrice').val(0);
                $('#priceSource').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + @json(__('ui.create_bom_manual')) + '</span>');
                $('#addItemBtn').prop('disabled', true);
                $('#bomSelect').val('').trigger('change');
                return;
            }

            var hasMaterials = false;
            var firstBomId = null;
            for (var i = 0; i < boms.length; i++) {
                if (boms[i].materials && boms[i].materials.length > 0) {
                    hasMaterials = true;
                    firstBomId = boms[i].id;
                    break;
                }
            }

            if (!hasMaterials) {
                var editUrl = '{{ route("bom.edit", ":id") }}'.replace(':id', firstBomId || boms[0].id);

                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.bom_has_no_materials_title')),
                    html: '<p>' + @json(__('ui.selected_bom_no_materials')) + '</p>' +
                        '<p class="text-muted">' + @json(__('ui.add_bom_materials')) + '</p>' +
                        '<div class="mt-3">' +
                        '<a href="' + editUrl + '" class="btn btn-primary">' +
                        '<i class="bi bi-pencil"></i> Edit BOM' +
                        '</a>' +
                        '</div>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4f46e5'
                });
                $('#bomDetailsPreview').hide();
                $('#unitPrice').val(0);
                $('#priceSource').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + @json(__('ui.no_bom_materials')) + '</span>');
                $('#addItemBtn').prop('disabled', true);
                $('#bomSelect').val('').trigger('change');
                return;
            }

            pricingMode = 'manual';
            $('#pricingModeNotice').addClass('manual').show();
            $('#pricingModeNotice .mode-icon').html('<i class="bi bi-sliders"></i>');
            $('#pricingModeTitle').text(@json(__('ui.manual_bom_enabled')));
            $('#pricingModeCopy').text(@json(__('ui.manual_bom_adjust_help')));
            $('#pricingModePrice').text(@json(__('ui.live')));

            manualTemplateBomId = $('#manualBomTemplate').val() || String(boms[0].id);
            $('#manualBomTemplate').val(manualTemplateBomId);
            loadBomDetails(manualTemplateBomId);
        }

        // ─── APPLY SAVED BOM PRICE ───
        function applySavedBomPrice(bomId) {
            var productId = $('#productSelect').val();
            var boms = getBomsForProduct(productId);
            var bom = null;

            for (var i = 0; i < boms.length; i++) {
                if (String(boms[i].id) === String(bomId)) {
                    bom = boms[i];
                    break;
                }
            }

            if (!bom) return;

            pricingMode = 'saved';
            currentBOMData = bom;
            currentQuoteSnapshot = [];

            var sellingPrice = getSavedBomPrice(bom);
            var profitMargin = numeric(bom, ['profit_margin_percentage'], 0);

            $('#bomDetailsPreview').hide();
            $('#pricingModeNotice').removeClass('manual').show();
            $('#pricingModeNotice .mode-icon').html('<i class="bi bi-check2-circle"></i>');
            $('#pricingModeTitle').text((bom.code || 'BOM') + ' saved price selected');
            $('#pricingModeCopy').text(@json(__('ui.stored_bom_active')));
            $('#pricingModePrice').text(currencySymbol + Number(sellingPrice).toFixed(2));

            $('#unitPrice').val(Number(sellingPrice).toFixed(4));
            $('#addItemBtn').prop('disabled', false).html('<i class="bi bi-cart-plus"></i> ' + @json(__('ui.add')));

            var cost = numeric(bom, ['total_cost_afn', 'material_cost_afn'], 0);
            var markup = sellingPrice - cost;
            var marginPercent = profitMargin > 0 ? profitMargin : (cost > 0 ? (markup / cost) * 100 : 0);

            $('#priceSource').html(
                '<span class="text-success"><i class="bi bi-database-check me-1"></i>Saved BOM price: ' +
                currencySymbol + Number(sellingPrice).toFixed(4) + ' per box' +
                ' <span class="text-muted small">(Cost: ' + currencySymbol + Number(cost).toFixed(2) +
                ' | Profit: ' + currencySymbol + Number(markup).toFixed(2) +
                ' | Margin: ' + Number(marginPercent).toFixed(1) + '%)</span>' +
                '</span>'
            );
            updateAddTotals();
        }

        // ─── LOAD BOM DETAILS ───
        function loadBomDetails(bomId) {
            if (pricingMode !== 'manual') return;

            var $bomPreview = $('#bomDetailsPreview');
            var productId = $('#productSelect').val();
            if (!bomId || !productId) {
                $bomPreview.hide();
                currentBOMData = null;
                $('#addItemBtn').prop('disabled', true);
                return;
            }

            $bomPreview.show();
            $('#bomMaterialsTableBody').html(
                '<div class="manual-empty-state"><span class="spinner-border spinner-border-sm me-2"></span>' + @json(__('ui.loading_inventory_costs')) + '</div>'
            );
            $('#addItemBtn').prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.sales.bom-details") }}',
                method: 'GET',
                data: {
                    bom_id: bomId,
                    quantity: Math.max(parseFloat($('#itemQty').val()) || 1, 1),
                    currency_code: 'AFN',
                    exchange_rate: Math.max(parseFloat($('#exchangeRate').val()) || exchangeRate || 85, 0.000001)
                },
                success: function(response) {
                    if (!response.success || !response.data) {
                        showBomLoadError(response.message || 'Unable to load BOM details.');
                        $('#addItemBtn').prop('disabled', true);
                        return;
                    }

                    var data = response.data;
                    currentBOMData = {
                        ...(data.bom || {}),
                        materials: Array.isArray(data.materials) ? data.materials : []
                    };

                    $('#bomCodeDisplay').text(@json(__('ui.template_colon')) + ' ' + (currentBOMData.code || 'N/A'));
                    $('#bomVersionDisplay').text('v' + (currentBOMData.version || '1.0'));
                    $('#costCurrencyLabel').text(currencyCode);
                    $('#bomExchangeRateDisplay').text('1 USD = ' + (parseFloat($('#exchangeRate').val()) || 85).toFixed(2) + ' AFN');

                    renderManualMaterials();
                },
                error: function(xhr) {
                    showBomLoadError(xhr.responseJSON?.message || 'Unable to load latest inventory costs.');
                    $('#addItemBtn').prop('disabled', true);
                }
            });
        }

        // ─── SHOW BOM LOAD ERROR ───
        function showBomLoadError(message) {
            currentBOMData = null;
            $('#bomMaterialsTableBody').html(
                '<div class="manual-empty-state text-danger"><i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>' + message + '</div>'
            );
            $('#unitPrice').val(0);
            $('#priceSource').html('<span class="text-danger">' + @json(__('ui.inventory_pricing_error')) + '</span>');
            $('#addItemBtn').prop('disabled', true);
            updateAddTotals();
        }

        // ─── MATERIAL FORMULA VALUES ───
        function materialFormulaValues(material) {
            var costToUse = 0;

            if (material.cost_to_use && material.cost_to_use > 0) {
                costToUse = material.cost_to_use;
            } else if (currencyCode === 'USD') {
                costToUse = numeric(material, ['cost_per_unit_usd', 'latest_material_cost_usd'], 0);
            } else {
                costToUse = numeric(material, ['cost_per_unit_afn', 'latest_material_cost_afn'], 0);
            }

            if (costToUse <= 0) {
                if (currencyCode === 'USD') {
                    costToUse = numeric(material, ['cost_per_unit_afn', 'latest_material_cost_afn'], 0) / (parseFloat($('#exchangeRate').val()) || 85);
                } else {
                    costToUse = numeric(material, ['cost_per_unit_usd', 'latest_material_cost_usd'], 0) * (parseFloat($('#exchangeRate').val()) || 85);
                }
            }

            var saleExchangeRate = Math.max(parseFloat($('#exchangeRate').val()) || exchangeRate || 85, 0.000001);
            var purchaseRateUsdKg = numeric(material, ['purchase_rate_usd_kg', 'latest_purchase_rate_usd_kg'], 0);
            var purchaseRateAfnKg = purchaseRateUsdKg > 0 ? purchaseRateUsdKg * saleExchangeRate : 0;

            return {
                length: numeric(material, ['length_inch', 'length'], 17.32),
                width: numeric(material, ['width_inch', 'width'], 15.75),
                height: numeric(material, ['height_inch', 'height'], 12.20),
                reelLength: numeric(material, ['reel_length_inch', 'reel_length'], 0),
                reelHeight: numeric(material, ['reel_height_inch', 'reel_height'], 0),
                paperGsm: numeric(material, ['paper_gsm', 'gsm'], 125),
                layers: numeric(material, ['layers'], 1),
                divisionFactor: numeric(material, ['division_factor', 'division'], 1),
                // Per Gram Rate defaults to the latest valid purchase price in
                // AFN per kg. The stale template per_gram_rate is only a fallback
                // when no purchase-based price is available.
                perGramRate: purchaseRateAfnKg > 0 ? purchaseRateAfnKg : numeric(material, ['per_gram_rate', 'per_gram', 'rate'], 43),
                multiplicationLayer: numeric(material, ['multiplication_layer'], 1),
                formulaConstant: numeric(material, ['formula_constant', 'constant'], 1550000),
                workPercentage: numeric(material, ['work_percentage'], 40),
                printCost: numeric(material, ['print', 'print_cost_afn', 'print_cost'], 0),
                wastage: numeric(material, ['wastage_percentage', 'wastage'], 5),
                latestRate: costToUse,
                inventoryFound: costToUse > 0,
                purchaseRateUsdKg: purchaseRateUsdKg,
                purchaseRateAfnKg: purchaseRateAfnKg,
                purchaseRateFound: purchaseRateUsdKg > 0,
                availableQty: numeric(material, ['latest_available_qty'], 0),
                latestPurchaseDate: material.latest_purchase_date || material.purchase_date || material.latest_batch_date || '',
                latestSupplier: material.latest_supplier_name || material.supplier_name || material.supplier || '',
                latestBatch: material.latest_batch_no || material.batch_no || material.batch_number || '',
                unit: material.unit || material.inventory_unit || 'unit',
                materialIsRollBased: !!(material.material_is_roll_based),
                formulaType: material.formula_type || 'carton_3d',
                isAdhesive: (material.formula_type || '') === 'adhesive_mix',
                adhesive: material.adhesive || null
            };
        }

        // ─── ADHESIVE MIX MATERIAL CARD ───
        function buildAdhesiveMaterialCard(index, material, f, missing, referenceRate, referenceUnit) {
            var materialName = material.material_name || material.name || 'Material';
            var adhesive = material.adhesive || {};
            var rateLabel = missing
                ? '⚠️ ' + @json(__('ui.no_cost'))
                : (currencyCode === 'USD' ? '$' : '؋') + Number(referenceRate).toFixed(4) + ' / ' + referenceUnit;

            return '<div class="manual-material-card ' + (missing ? 'border border-danger' : '') + '" data-estimate-row="' + index + '" data-formula-type="adhesive_mix" data-latest-rate="' + f.latestRate + '">' +
                '<div class="manual-material-header">' +
                '<div class="manual-material-title">' +
                '<div class="manual-material-name"><i class="bi bi-droplet-half me-2 text-danger"></i>' + materialName + '</div>' +
                '<div class="manual-material-subtitle">Adhesive Mix — Dimension Based (board area drives the glue mix)</div>' +
                '</div>' +
                '<span class="latest-cost-chip">' + rateLabel + '</span>' +
                '</div>' +
                '<div class="manual-material-body">' +
                '<div class="manual-input-grid">' +
                '<div class="manual-field"><label>{{ __('ui.carton_length_in') }}</label><input class="est-length" type="number" min="0" step="0.01" value="' + f.length + '"></div>' +
                '<div class="manual-field"><label>{{ __('ui.carton_width_in') }}</label><input class="est-width" type="number" min="0" step="0.01" value="' + f.width + '"></div>' +
                '<div class="manual-field"><label>{{ __('ui.carton_height_in') }}</label><input class="est-height" type="number" min="0" step="0.01" value="' + f.height + '"></div>' +
                '<div class="manual-field"><label>Recipe Share (kg/kg wet glue)</label><input class="est-adhesive-recipe" type="number" min="0" step="0.000001" value="' + (adhesive.recipe_fraction !== undefined ? adhesive.recipe_fraction : '') + '"></div>' +
                '<div class="manual-field"><label>Glue Lines</label><input class="est-adhesive-glue-lines" type="number" min="0" step="1" value="' + (adhesive.glue_lines !== undefined ? adhesive.glue_lines : cartonAdhesiveConfig.glue_lines) + '"></div>' +
                '<div class="manual-field"><label>Dry Glue GSM / Line</label><input class="est-adhesive-dry-gsm" type="number" min="0" step="0.01" value="' + (adhesive.dry_glue_gsm_per_line !== undefined ? adhesive.dry_glue_gsm_per_line : cartonAdhesiveConfig.dry_glue_gsm_per_line) + '"></div>' +
                '<div class="manual-field"><label>Glue Wastage %</label><input class="est-adhesive-wastage" type="number" min="0" step="0.01" value="' + (adhesive.glue_wastage_percentage !== undefined ? adhesive.glue_wastage_percentage : cartonAdhesiveConfig.glue_wastage_percentage) + '"></div>' +
                '<div class="manual-field"><label>Adhesive Solids %</label><input class="est-adhesive-solids" type="number" min="0" max="100" step="0.01" value="' + (adhesive.adhesive_solids_percentage !== undefined ? adhesive.adhesive_solids_percentage : cartonAdhesiveConfig.adhesive_solids_percentage) + '"></div>' +
                '<div class="manual-field"><label>Board Area (m²)</label><input class="form-control est-adhesive-board-area" type="text" value="0.000000" readonly style="background:#f1f5f9;cursor:not-allowed;font-size:0.75rem;height:34px;"></div>' +
                '<div class="manual-field"><label>Wet Glue (kg / carton)</label><input class="form-control est-adhesive-wet-kg" type="text" value="0.00000000" readonly style="background:#f1f5f9;cursor:not-allowed;font-size:0.75rem;height:34px;"></div>' +
                '<div class="manual-field"><label>Ingredient (kg / carton)</label><input class="form-control est-adhesive-kg" type="text" value="0.00000000" readonly style="background:#f1f5f9;cursor:not-allowed;font-size:0.75rem;height:34px;"></div>' +
                '</div>' +
                '<div class="manual-result-panel">' +
                '<div class="manual-result-row"><span>Ingredient kg × Carton</span><strong class="est-adhesive-order-kg">0.000000</strong></div>' +
                '<div class="manual-result-row total"><span>Physical material cost</span><strong class="est-adhesive-cost">؋0.0000</strong></div>' +
                '</div>' +
                '</div>' +
                '</div>';
        }

        // ─── RENDER MANUAL MATERIALS ───
        function renderManualMaterials() {
            var html = '';
            var hasMissingCost = false;
            var materials = Array.isArray(currentBOMData?.materials) ? currentBOMData.materials : [];

            if (!materials || materials.length === 0) {
                $('#bomMaterialsTableBody').html(
                    '<div class="manual-empty-state"><i class="bi bi-inbox fs-3 d-block mb-2"></i>{{ __('ui.no_materials_bom') }}</div>'
                );
                $('#unitPrice').val(0);
                $('#priceSource').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + @json(__('ui.no_bom_materials')) + '</span>');
                $('#addItemBtn').prop('disabled', true);
                updateAddTotals();
                return;
            }

            for (var i = 0; i < materials.length; i++) {
                var material = materials[i];
                var f = materialFormulaValues(material);
                var materialName = material.material_name || material.name || 'Material';
                var unit = f.unit || 'unit';
                var kgBased = f.materialIsRollBased || unit === 'roll' || unit === 'kg';
                var referenceRate = (kgBased && f.purchaseRateAfnKg > 0) ? f.purchaseRateAfnKg : f.latestRate;
                var referenceUnit = (kgBased && f.purchaseRateFound) ? 'kg' : unit;
                // Roll-based (per kg) materials MUST have a purchase-based per-kg
                // price; a stale template rate is never acceptable for a quotation.
                var missing = !f.inventoryFound || referenceRate <= 0 || (kgBased && !f.purchaseRateFound);
                hasMissingCost = hasMissingCost || missing;

                if (f.isAdhesive) {
                    html += buildAdhesiveMaterialCard(i, material, f, missing, referenceRate, referenceUnit);
                    continue;
                }

                var reelLengthCalc = ((f.length + f.width) * 2) + 4;
                var reelHeightCalc = f.width + f.height + 1;

                var formulaType = material.formula_type || 'carton_3d';
                html += '<div class="manual-material-card ' + (missing ? 'border border-danger' : '') + '" data-estimate-row="' + i + '" data-formula-type="' + formulaType + '" data-latest-rate="' + f.latestRate + '">' +
                    '<div class="manual-material-header">' +
                    '<div class="manual-material-title">' +
                    '<div class="manual-material-name"><i class="bi bi-layers me-2 text-primary"></i>' + materialName + '</div>' +
                    '<div class="manual-material-subtitle">Excel Formula: Paper Rate = (Reel L × Reel H × GSM × Per Gram Rate) ÷ Constant</div>' +
                    '</div>' +
                    '<span class="latest-cost-chip">' + (missing ? '⚠️ ' + @json(__('ui.no_cost')) : (currencyCode === 'USD' ? '$' : '؋') + Number(referenceRate).toFixed(4) + ' / ' + referenceUnit) + '</span>' +
                    '</div>' +
                    '<div class="manual-material-body">' +
                    '<div class="manual-input-grid">' +
                    '<div class="manual-field"><label>{{ __('ui.carton_length_in') }}</label><input class="est-length" type="number" min="0" step="0.01" value="' + f.length + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.carton_width_in') }}</label><input class="est-width" type="number" min="0" step="0.01" value="' + f.width + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.carton_height_in') }}</label><input class="est-height" type="number" min="0" step="0.01" value="' + f.height + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.paper_gsm') }}</label><input class="est-paper-gsm" type="number" min="0" step="1" value="' + f.paperGsm + '"></div>' +
                    '<div class="manual-field"><label>PO Rate (AFN/kg)</label><input class="est-per-gram-rate" type="number" min="0" step="0.0001" value="' + f.perGramRate + '" readonly style="background:#f1f5f9;cursor:not-allowed;"><small class="text-muted d-block mt-1">Auto-populated from latest arrived purchase order.</small></div>' +
                    '<div class="manual-field"><label>{{ __('ui.multiplication_layer') }}</label><input class="est-multiplication-layer" type="number" min="1" step="1" value="' + f.multiplicationLayer + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.print_cost') }}</label><input class="est-print-cost" type="number" min="0" step="0.01" value="' + f.printCost + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.formula_constant') }}</label><input class="est-formula-constant" type="number" min="0.0001" step="1" value="' + f.formulaConstant + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.work_percentage_percent') }}</label><input class="est-work-percentage" type="number" min="0" step="0.1" value="' + f.workPercentage + '"></div>' +
                    '<div class="manual-field"><label>{{ __('ui.wastage_label') }}</label><input class="est-wastage" type="number" min="0" step="0.01" value="' + f.wastage + '"></div>' +
                    '<div class="manual-field latest-cost-field">' +
                    '<label>{{ __('ui.material_cost_ref') }}</label>' +
                    '<div class="latest-cost-display ' + (missing ? 'border border-danger bg-danger-subtle' : '') + '" style="min-height:40px;padding:0.4rem 0.6rem;">' +
                    (missing ?
                            '<div class="latest-cost-main text-danger" style="font-size:0.75rem;">{{ __('ui.not_available') }}</div>' :
                            '<div class="latest-cost-main" style="font-size:0.85rem;font-weight:600;color:#166534;">' + (currencyCode === 'USD' ? '$' : '؋') + Number(referenceRate).toFixed(4) + ' <span style="font-size:0.65rem;font-weight:400;color:#64748b;">/ ' + referenceUnit + '</span></div>' +
                            '<div style="font-size:0.55rem;color:#64748b;margin-top:0.1rem;"><i class="bi bi-info-circle"></i> {{ __('ui.reference_only') }}</div>'
                    ) +
                    '</div>' +
                    '</div>' +
                    '<div class="manual-field">' +
                    '<label>{{ __('ui.reel_length_auto') }}</label>' +
                    '<input class="form-control" type="text" value="' + reelLengthCalc.toFixed(2) + ' in" readonly style="background:#f1f5f9;cursor:not-allowed;font-size:0.75rem;height:34px;">' +
                    '</div>' +
                    '<div class="manual-field">' +
                    '<label>{{ __('ui.reel_height_auto') }}</label>' +
                    '<input class="form-control" type="text" value="' + reelHeightCalc.toFixed(2) + ' in" readonly style="background:#f1f5f9;cursor:not-allowed;font-size:0.75rem;height:34px;">' +
                    '</div>' +
                    '</div>' +
                    '<div class="manual-result-panel">' +
                    '<div class="manual-result-row"><span>Paper Rate × Layers</span><strong class="est-paper-rate">؋0.0000</strong></div>' +
                    '<div class="manual-result-row"><span>{{ __('ui.work_amount_open') }}<span class="est-work-label">' + f.workPercentage + '</span>%)</span><strong class="est-work-amount">؋0.0000</strong></div>' +
                    '<div class="manual-result-row total"><span>{{ __('ui.unit_rate') }}</span><strong class="est-unit-result">؋0.0000</strong></div>' +
                    '<div class="manual-result-row total"><span>{{ __('ui.order_total') }}</span><strong class="est-order-result">؋0.00</strong></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            $('#bomMaterialsTableBody').html(html);

            $('#bomMaterialsTableBody input, #bomMaterialsTableBody select').on('input change', function() {
                var $input = $(this);
                var $sourceRow = $input.closest('.manual-material-card');

                if (($sourceRow.data('formula-type') === 'carton_3d' || $sourceRow.data('formula-type') === 'adhesive_mix')
                    && ($input.hasClass('est-length') || $input.hasClass('est-width') || $input.hasClass('est-height'))) {
                    var fieldClass = $input.hasClass('est-length')
                        ? '.est-length'
                        : ($input.hasClass('est-width') ? '.est-width' : '.est-height');

                    $('#bomMaterialsTableBody .manual-material-card[data-formula-type="carton_3d"], #bomMaterialsTableBody .manual-material-card[data-formula-type="adhesive_mix"]')
                        .not($sourceRow)
                        .find(fieldClass)
                        .val($input.val());
                }

                recalculateLiveEstimate();
            });

            // A carton has one set of finished dimensions shared by every paper
            // layer AND the dimension-driven adhesive rows.
            var $first3d = $('#bomMaterialsTableBody .manual-material-card[data-formula-type="carton_3d"], #bomMaterialsTableBody .manual-material-card[data-formula-type="adhesive_mix"]').first();
            if ($first3d.length) {
                ['.est-length', '.est-width', '.est-height'].forEach(function(selector) {
                    var value = $first3d.find(selector).val();
                    $('#bomMaterialsTableBody .manual-material-card[data-formula-type="carton_3d"], #bomMaterialsTableBody .manual-material-card[data-formula-type="adhesive_mix"]')
                        .find(selector)
                        .val(value);
                });
            }

            if (hasMissingCost) {
                $('#unitPrice').val(0);
                $('#priceSource').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + @json(__('ui.material_cost_missing')) + '</span>');
                $('#addItemBtn').prop('disabled', true);
                updateAddTotals();
                return;
            }

            $('#addItemBtn').prop('disabled', false).html('<i class="bi bi-cart-plus"></i> ' + @json(__('ui.add')));

            setTimeout(function() {
                recalculateLiveEstimate();
            }, 100);
        }

        // ─── RECALCULATE LIVE ESTIMATE ───
        function recalculateLiveEstimate() {
            if (!currentBOMData || pricingMode !== 'manual') return;

            var qty = Math.max(parseFloat($('#itemQty').val()) || 1, 1);
            var saleExchangeRate = Math.max(parseFloat($('#exchangeRate').val()) || exchangeRate || 85, 0.000001);

            // Summary aggregates drawn from the SAME per-row components used by the
            // estimator (Paper Rate x Layers, Work/Profit, Print), never a redeclared
            // formula or a raw row-net total relabelled as paper.
            var paperSubtotal = 0;
            var workSubtotal = 0;
            var printSubtotal = 0;
            var snapshot = [];

            $('#bomMaterialsTableBody .manual-material-card[data-estimate-row]').each(function() {
                var row = $(this);
                var index = parseInt(row.data('estimate-row'));
                var material = currentBOMData.materials[index];

                // Dimension-driven adhesive rows: physical material cost only,
                // never part of the paper commercial quotation subtotals.
                if (row.data('formula-type') === 'adhesive_mix') {
                    var adhesive = material.adhesive || {};
                    var adhesiveParams = {
                        length: parseFloat(row.find('.est-length').val()) || 0,
                        width: parseFloat(row.find('.est-width').val()) || 0,
                        height: parseFloat(row.find('.est-height').val()) || 0,
                        glueLines: parseFloat(row.find('.est-adhesive-glue-lines').val()),
                        dryGlueGsm: parseFloat(row.find('.est-adhesive-dry-gsm').val()),
                        glueWastage: parseFloat(row.find('.est-adhesive-wastage').val()),
                        solids: parseFloat(row.find('.est-adhesive-solids').val()),
                        recipe: parseFloat(row.find('.est-adhesive-recipe').val())
                    };
                    var adhesiveResult = adhesiveRowFormula(adhesiveParams);
                    var adhesiveF = materialFormulaValues(material);
                    var adhesiveRateAfn = adhesiveF.purchaseRateAfnKg > 0 ? adhesiveF.purchaseRateAfnKg : adhesiveF.latestRate;
                    var adhesiveRateUsd = adhesiveF.purchaseRateUsdKg > 0
                        ? adhesiveF.purchaseRateUsdKg
                        : (saleExchangeRate > 0 ? adhesiveRateAfn / saleExchangeRate : 0);
                    var adhesiveCostAfn = adhesiveResult.ingredientKg * adhesiveRateAfn;

                    row.find('.est-adhesive-board-area').val(adhesiveResult.boardAreaM2.toFixed(6));
                    row.find('.est-adhesive-wet-kg').val(adhesiveResult.wetGlueKg.toFixed(8));
                    row.find('.est-adhesive-kg').val(adhesiveResult.ingredientKg.toFixed(8));
                    row.find('.est-adhesive-order-kg').text((adhesiveResult.ingredientKg * qty).toFixed(6));
                    row.find('.est-adhesive-cost').text('؋' + adhesiveCostAfn.toFixed(4));

                    snapshot.push({
                        material_id: material.material_id || material.id,
                        material_name: material.material_name || material.name || 'Material',
                        formula_type: 'adhesive_mix',
                        length: adhesiveParams.length,
                        width: adhesiveParams.width,
                        height: adhesiveParams.height,
                        glue_lines: adhesiveParams.glueLines,
                        dry_glue_gsm_per_line: adhesiveParams.dryGlueGsm,
                        glue_wastage_percentage: adhesiveParams.glueWastage,
                        adhesive_solids_percentage: adhesiveParams.solids,
                        recipe_key: adhesive.recipe_key || null,
                        recipe_percentage: adhesiveParams.recipe,
                        sq_inch_to_m2: cartonSqInchToM2,
                        landed_cost_usd_per_kg: adhesiveRateUsd,
                        per_gram_rate: adhesiveRateAfn,
                        wastage: 0,
                        work_percentage: 0,
                        print_cost: 0,
                        kg_per_finished_unit: adhesiveResult.ingredientKg,
                        kg_with_wastage: adhesiveResult.ingredientKg,
                        physical_cost_usd: adhesiveResult.ingredientKg * adhesiveRateUsd,
                        row_net_rate: 0,
                        final_rate_afn: 0
                    });

                    return;
                }

                var length = parseFloat(row.find('.est-length').val()) || 0;
                var width = parseFloat(row.find('.est-width').val()) || 0;
                var height = parseFloat(row.find('.est-height').val()) || 0;
                var paperGsm = parseFloat(row.find('.est-paper-gsm').val()) || 0;
                var perGramRate = parseFloat(row.find('.est-per-gram-rate').val()) || 0;
                var multiplicationLayer = parseFloat(row.find('.est-multiplication-layer').val()) || 1;
                var printCost = parseFloat(row.find('.est-print-cost').val()) || 0;
                var formulaConstant = parseFloat(row.find('.est-formula-constant').val()) || 1550000;
                var workPercentage = parseFloat(row.find('.est-work-percentage').val()) || 40;
                var wastage = parseFloat(row.find('.est-wastage').val()) || 0;

                var reelLengthCalc = ((length + width) * 2) + 4;
                var reelHeightCalc = width + height + 1;
                var divisionValue = reelLengthCalc * reelHeightCalc * paperGsm * perGramRate;
                var paperRate = divisionValue / formulaConstant;
                var paperRateByLayers = multiplicationLayer * paperRate;
                var workAmount = paperRateByLayers * (workPercentage / 100);
                var rowNetRate = printCost + paperRateByLayers + workAmount;

                // Match the authoritative first Excel section exactly:
                // Net Rate = Print Cost + (Paper Rate × Layers) + Work Amount.
                // Wastage is retained in the snapshot for production planning and is
                // intentionally NOT added to the quotation/BOM selling-rate formula.
                var finalRateAfn = rowNetRate;

                var orderTotal = finalRateAfn * qty;

                // Show the same intermediate values used to build the final unit rate.
                row.find('.est-paper-rate').text('؋' + paperRateByLayers.toFixed(4));
                row.find('.est-work-amount').text('؋' + workAmount.toFixed(4));
                row.find('.est-work-label').text(Number(workPercentage).toFixed(2).replace(/\.00$/, ''));
                row.find('.est-unit-result').text('؋' + finalRateAfn.toFixed(4));
                row.find('.est-order-result').text('؋' + orderTotal.toFixed(2));

                paperSubtotal += paperRateByLayers;
                workSubtotal += workAmount;
                printSubtotal += printCost;

                snapshot.push({
                    material_id: material.material_id || material.id,
                    formula_type: 'carton_3d',
                    length: length,
                    width: width,
                    height: height,
                    paper_gsm: paperGsm,
                    per_gram_rate: perGramRate,
                    multiplication_layer: multiplicationLayer,
                    print_cost: printCost,
                    formula_constant: formulaConstant,
                    work_percentage: workPercentage,
                    wastage: wastage,
                    reel_length: reelLengthCalc,
                    reel_height: reelHeightCalc,
                    division_value: divisionValue,
                    paper_rate: paperRate,
                    paper_rate_by_layers: paperRateByLayers,
                    work_amount: workAmount,
                    row_net_rate: rowNetRate,
                    final_rate: finalRateAfn
                });
            });

            var profitMarginPercent = numeric(currentBOMData, ['profit_margin_percentage'], 0);
            // The client's Excel Net Rate already includes the standard 40% work/profit
            // (Net Rate = Print + Paper Rate by Layers + Standard Work/Profit). Per the
            // authoritative Excel workflow the Net Rate is the FINAL default selling rate;
            // profit_margin_percentage must NOT add another markup on top of it.
            // Final Unit Rate = Paper Subtotal + Work Subtotal + Print Subtotal.
            // Each subtotal is the aggregated (not modified) row component from the
            // estimator, so the breakdown always sums back to the final unit rate.
            var costPerUnitAfn = paperSubtotal + workSubtotal + printSubtotal;
            var sellingPricePerUnitAfn = costPerUnitAfn;
            var sellingPricePerUnitUsd = sellingPricePerUnitAfn / saleExchangeRate;
            var unitPriceSaleCurrency = currencyCode === 'USD' ? sellingPricePerUnitUsd : sellingPricePerUnitAfn;
            var orderTotalSaleCurrency = unitPriceSaleCurrency * qty;

            $('#materialCostDisplay').text('؋' + paperSubtotal.toFixed(4));
            $('#laborCostDisplay').text('؋' + workSubtotal.toFixed(4));
            $('#printCostDisplay').text('؋' + printSubtotal.toFixed(4));
            $('#totalCostDisplay').text('؋' + costPerUnitAfn.toFixed(4));
            $('#estimateQtyDisplay').text(qty.toLocaleString());
            $('#sellingPriceUsdDisplay').text(currencySymbol + orderTotalSaleCurrency.toFixed(2));

            $('#unitPrice').val(unitPriceSaleCurrency.toFixed(4));
            $('#addItemBtn').prop('disabled', false).html('<i class="bi bi-cart-plus"></i> ' + @json(__('ui.add')));

            // The Real components are already known from the estimator rows; reuse them
            // instead of re-deriving paper/work from a blended work factor.
            var paperBasisAfn = paperSubtotal;
            var standardWorkProfit = workSubtotal;
            var markup = 0;
            var marginPercent = costPerUnitAfn > 0 ? (standardWorkProfit / costPerUnitAfn) * 100 : 0;

            $('#priceSource').html(
                '<span class="text-primary"><i class="bi bi-calculator-fill me-1"></i>Manual Excel formula: ' +
                currencySymbol + unitPriceSaleCurrency.toFixed(4) + ' per box' +
                ' <span class="text-muted small">(Net Rate: ' + currencySymbol + Number(costPerUnitAfn).toFixed(4) +
                ' | Std Work/Profit 40%: ' + currencySymbol + Number(standardWorkProfit).toFixed(2) +
                ' | Margin on Rev: ' + Number(marginPercent).toFixed(1) + '%)</span>' +
                '</span>'
            );

            $('#pricingModePrice').text(currencySymbol + unitPriceSaleCurrency.toFixed(2));

            currentQuoteSnapshot = snapshot;
            updateAddTotals();
        }

        // ─── ADD ITEM TO SALE ───
        function addItemToSale() {
            var productId = $('#productSelect').val();
            var selectedPricingValue = $('#bomSelect').val();
            var bomId = pricingMode === 'manual' ? manualTemplateBomId : selectedPricingValue;
            var qty = parseFloat($('#itemQty').val()) || 0;
            var autoUnitPrice = parseFloat($('#unitPrice').val()) || 0;
            var manualUnitPrice = parseFloat($('#manualUnitPrice').val()) || 0;
            var unitPrice = manualUnitPrice > 0 ? manualUnitPrice : autoUnitPrice;
            var exchangeRateVal = parseFloat($('#exchangeRate').val()) || 1;

            if (!productId) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_product_selected')),
                    text: @json(__('ui.select_product_add'))
                });
                return;
            }

            if (!selectedPricingValue || !bomId) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_pricing_method')),
                    text: @json(__('ui.select_saved_manual_bom'))
                });
                return;
            }

            if (qty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.invalid_quantity')),
                    text: @json(__('ui.valid_quantity'))
                });
                return;
            }

            if (unitPrice <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.invalid_price')),
                    text: @json(__('ui.zero_unit_price'))
                });
                return;
            }

            $('#addItemBtn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span> Adding...');

            $.ajax({
                url: '{{ route("admin.sales.add-item-with-bom") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    sale_id: saleId,
                    product_id: productId,
                    bom_id: bomId,
                    qty: qty,
                    exchange_rate: exchangeRateVal,
                    currency_code: currencyCode,
                    quoted_unit_price: autoUnitPrice,
                    manual_unit_price: manualUnitPrice > 0 ? manualUnitPrice : null,
                    quotation_description: $('#quotationDescription').val(),
                    pricing_mode: pricingMode || 'saved',
                    formula_snapshot: pricingMode === 'manual' ? JSON.stringify(currentQuoteSnapshot) : null,
                    remarks: pricingMode === 'manual'
                        ? 'Manual BOM quotation using template: ' + $('#manualBomTemplate option:selected').text()
                        : 'Saved BOM price: ' + ($('#bomSelect option:selected').data('code') || '') + ' - ' + $('#bomSelect option:selected').text()
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: @json(__('ui.added')),
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.error')),
                            text: response.message || 'Failed to add item'
                        });
                        $('#addItemBtn').prop('disabled', false).html(
                            '<i class="bi bi-cart-plus"></i> Add');
                    }
                },
                error: function(xhr) {
                    var msg = 'Error adding item';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.error')),
                        text: msg
                    });
                    $('#addItemBtn').prop('disabled', false).html(
                        '<i class="bi bi-cart-plus"></i> Add');
                }
            });
        }

        // ─── REMOVE ITEM ───
        function removeItem(itemId) {
            if (!confirm(@json(__('ui.remove_sale_item')))) return;

            $.ajax({
                url: '{{ url('admin/sales/remove-item') }}/' + itemId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Error removing item');
                    }
                },
                error: function(xhr) {
                    var msg = 'Error removing item';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        }

        // ─── CONFIRMATION MODAL ───
        let confirmData = {};

        function openConfirmModal() {
            Swal.fire({
                title: @json(__('ui.loading')),
                text: @json(__('ui.preparing_confirmation')),
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("admin.sales.confirmation-data", ":id") }}'.replace(':id', saleId),
                method: 'GET',
                success: function(response) {
                    Swal.close();

                    if (response.success) {
                        confirmData = response;

                        let exchangeRate = parseFloat(response.exchange_rate) || 1;
                        let symbol = response.currency_symbol || '$';
                        let subtotal = parseFloat(response.subtotal) || 0;
                        let usdSubtotal = parseFloat(response.usd_subtotal) || 0;

                        $('#confirmSaleNo').text(response.sale?.sale_no || '-');
                        $('#confirmCustomer').text(response.sale?.customer?.name || '-');
                        $('#confirmItems').text((response.item_count || 0) + ' items');
                        $('#confirmQty').text((parseFloat(response.total_qty) || 0).toFixed(2) + ' units');
                        $('#confirmCurrency').text(response.currency_code + ' (' + symbol + ')');

                        $('#discountCurrencySymbol').text(symbol);
                        $('#advanceCurrencySymbol').text(symbol);

                        $('#confirmSubtotal').text(symbol + ' ' + numberFormat(subtotal));
                        $('#confirmUsdSubtotal').text('$' + numberFormat(usdSubtotal));

                        $('#discountAmount').val(0);
                        $('#advancePayment').val(0);

                        updateConfirmTotals();

                        var modal = new bootstrap.Modal(document.getElementById('confirmModal'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.error')),
                            text: response.message || 'Error loading confirmation data.'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let msg = 'Error loading confirmation data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.error')),
                        text: msg
                    });
                }
            });
        }

        // ─── UPDATE CONFIRM TOTALS ───
        function updateConfirmTotals() {
            let subtotal = parseFloat(confirmData.subtotal) || 0;
            let symbol = confirmData.currency_symbol || '$';
            let isUSD = confirmData.currency_code === 'USD';
            let exchangeRate = parseFloat(confirmData.exchange_rate) || 1;

            let discount = parseFloat($('#discountAmount').val()) || 0;
            let advance = parseFloat($('#advancePayment').val()) || 0;

            let grandTotal = subtotal - discount;
            let dueAmount = grandTotal - advance;

            $('#confirmGrandTotal').text(symbol + ' ' + numberFormat(grandTotal));
            $('#confirmAdvancePaid').text(symbol + ' ' + numberFormat(advance));
            $('#confirmDueAmount').text(symbol + ' ' + numberFormat(dueAmount));

            $('#txnDebit').text(symbol + ' ' + numberFormat(grandTotal));

            if (advance > 0) {
                $('#txnAdvanceText').html(@json(__('ui.credit_customer_cash')) + ' <strong>' + symbol + ' ' + numberFormat(advance) + '</strong>');
            } else {
                $('#txnAdvanceText').html(@json(__('ui.no_advance_payment')));
            }

            let dueElement = $('#confirmDueAmount');
            let dueCard = dueElement.closest('div');
            if (dueAmount > 0) {
                dueElement.css('color', '#92400e');
                dueCard.css('background', 'linear-gradient(135deg, #fef3c7, #fde68a)');
                dueCard.css('border-color', '#fcd34d');
            } else if (dueAmount === 0) {
                dueElement.css('color', '#065f46');
                dueCard.css('background', 'linear-gradient(135deg, #ecfdf5, #d1fae5)');
                dueCard.css('border-color', '#6ee7b7');
            } else {
                dueElement.css('color', '#dc2626');
                dueCard.css('background', 'linear-gradient(135deg, #fecaca, #fca5a5)');
                dueCard.css('border-color', '#f87171');
            }
        }

        // ─── CONFIRM FORM SUBMISSION ───
        $(document).on('submit', '#confirmForm', function(e) {
            e.preventDefault();

            let discount = parseFloat($('#discountAmount').val()) || 0;
            let advance = parseFloat($('#advancePayment').val()) || 0;
            let startProduction = $('#startProductionCheckbox').is(':checked');
            let subtotal = parseFloat(confirmData.subtotal) || 0;
            let symbol = confirmData.currency_symbol || '$';

            if (discount > subtotal) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.invalid_discount')),
                    text: @json(__('ui.discount_exceeds_subtotal')) + ' (' + symbol + ' ' + numberFormat(subtotal) + ')'
                });
                return;
            }

            let grandTotal = subtotal - discount;
            if (advance > grandTotal) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.invalid_advance_payment')),
                    text: @json(__('ui.advance_exceeds_total')) + ' (' + symbol + ' ' + numberFormat(grandTotal) + ')'
                });
                return;
            }

            if (parseInt(confirmData.item_count) === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_items')),
                    text: @json(__('ui.cannot_confirm_no_items'))
                });
                return;
            }

            $('#confirmSubmitBtn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span> Processing...');

            let formData = $(this).serialize();
            formData += '&start_production=' + (startProduction ? '1' : '0');

            $.ajax({
                url: '{{ route("admin.sales.confirm", ":id") }}'.replace(':id', saleId),
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
                        if (modal) modal.hide();

                        let message = response.message;
                        if (response.production_started) {
                            message += '<br><br><i class="bi bi-gear me-2"></i> <strong>{{ __('ui.production_started') }}</strong> Raw materials have been consumed.';
                        }

                        Swal.fire({
                            icon: 'success',
                            title: @json(__('ui.success')),
                            html: message,
                            timer: 3000,
                            showConfirmButton: true
                        }).then(function() {
                            window.location.href = response.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.error')),
                            text: response.message
                        });
                        $('#confirmSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2-circle"></i> Confirm Sale');
                    }
                },
                error: function(xhr) {
                    let msg = 'Error confirming sale';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.error')),
                        text: msg
                    });
                    $('#confirmSubmitBtn').prop('disabled', false).html(
                        '<i class="bi bi-check2-circle"></i> Confirm Sale');
                }
            });
        });

        // ─── UPDATE STATUS ───
        window.updateStatus = function (status) {
            if (status === 'confirmed') {
                openConfirmModal();
                return;
            }

            if (confirm(@json(__('ui.change_status_confirm')).replace(':status', status.toUpperCase()))) {
                $('#statusInput').val(status);
                $('#statusForm').submit();
            }
        };

        // ─── BOM CALCULATOR MODAL FUNCTIONS ───

        function openBomCalculator() {
            // Reset the modal
            $('#bomCalculatorModal').modal('show');
            modalItemCounter = 0;
            $('#modalItemsContainer').html('');
            $('#modalNoItemsMessage').show();
            $('#modalItemCount').text('0');
            $('#modalTotalMaterialCost').html(@json(__('ui.material_label')) + ' $0.00 USD <span class="text-muted">(؋0.00 AFN)</span>');
            $('#modalExchangeRateContainer').show();
            $('#modalBomName').val('Calculated BOM');

            // Reset product select
            $('#modalProduct').val('').trigger('change');

            // Set default formula type
            $('#modalFormulaType').val('carton_3d').trigger('change');

            // Show exchange rate
            $('#modalExchangeRate').val(defaultModalExchangeRate);

            // Hide calculation steps
            $('#modalCalculatorSteps').hide();

            calculateModalBOM();
        }

        function toggleModalFormulaFields() {
            // This function is now handled by the item-level formula fields
            // No need to toggle global fields anymore
        }

        function loadModalBOMItems() {
            var productId = $('#modalProduct').val();
            if (!productId) {
                $('#modalItemsContainer').html('');
                $('#modalNoItemsMessage').show();
                $('#modalItemCount').text('0');
                return;
            }

            var boms = getBomsForProduct(productId);
            if (!boms || boms.length === 0) {
                $('#modalItemsContainer').html('');
                $('#modalNoItemsMessage').show();
                $('#modalItemCount').text('0');
                return;
            }

            var bom = boms[0];
            if (!bom.materials || bom.materials.length === 0) {
                $('#modalItemsContainer').html('');
                $('#modalNoItemsMessage').show();
                $('#modalItemCount').text('0');
                return;
            }

            // Populate materials from the BOM
            $('#modalItemsContainer').html('');
            $('#modalNoItemsMessage').hide();

            for (var i = 0; i < bom.materials.length; i++) {
                var material = bom.materials[i];
                var cost = material.cost_per_unit || material.total_cost || 0;
                var currency = material.purchase_currency || material.currency || 'AFN';
                var materialName = material.material_name || material.name || 'Material';
                var materialId = material.material_id || material.id || '';
                var requiredQty = material.required_qty || material.quantity || 1;
                var unit = material.unit || 'unit';
                var gsm = material.paper_gsm || 125;
                var perGramRate = material.per_gram_rate || 43;
                var multiplicationLayer = material.multiplication_layer || 1;
                var printCost = material.print || 0;
                var formulaConstant = material.formula_constant || 1550000;
                var workPercentage = material.work_percentage || 40;
                var wastage = material.wastage_percentage || 5;

                // Check if it's a 3D Carton or Cut/Roll material
                var formulaType = material.formula_type || 'carton_3d';

                var itemId = ++modalItemCounter;
                var html = generateModalItemHtml(itemId, {
                    material_id: materialId,
                    material_name: materialName,
                    cost: cost,
                    currency: currency,
                    quantity: requiredQty,
                    unit: unit,
                    length: material.length_inch || 17.32,
                    width: material.width_inch || 15.75,
                    height: material.height_inch || 12.20,
                    paper_gsm: gsm,
                    per_gram_rate: perGramRate,
                    multiplication_layer: multiplicationLayer,
                    print: printCost,
                    formula_constant: formulaConstant,
                    work_percentage: workPercentage,
                    wastage: wastage,
                    formula_type: formulaType,
                    cut_length: material.cut_length_inch || 0,
                    cut_width: material.cut_width_inch || 0,
                    grh: material.grh || 0,
                    ply: material.ply || 1,
                    multiplication_method: material.multiplication_method || 'multiply',
                });
                $('#modalItemsContainer').append(html);
            }

            updateModalItemCount();
            calculateModalBOM();
        }

        function generateModalItemHtml(id, data) {
            var formulaType = data.formula_type || 'carton_3d';
            var isFormula = formulaType !== 'fixed';

            return `
            <div class="bom-item-row-modal" id="modal-item-${id}">
                <button type="button" class="remove-item-modal" onclick="removeModalItem(${id})">
                    <i class="bi bi-x-circle"></i>
                </button>

                <div class="modal-item-counter">
                    Material #${id}
                    <span class="material-cost-preview" id="modal-cost-preview-${id}">Cost: $0.00 USD</span>
                    <span class="modal-formula-badge ${formulaType} ms-2" id="modal-formula-badge-${id}">${formulaType === 'carton_3d' ? '3D Carton' : (formulaType === 'cut_roll' ? 'Cut/Roll' : 'Fixed')}</span>
                    <span class="modal-currency-badge ${data.currency === 'USD' ? 'usd' : 'afn'} ms-2" id="modal-currency-badge-${id}">${data.currency || 'AFN'}</span>
                </div>

                <!-- ─── REEL DIMENSIONS DISPLAY ─── -->
                <div class="modal-reel-dimensions-bar" id="modal-reel-dimensions-${id}" style="display: ${formulaType === 'carton_3d' ? 'block' : 'none'};">
                    <div class="row g-0 align-items-center">
                        <div class="col-6">
                            <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                            <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="modal-reel-length-display-${id}">0.00</span>
                            <span style="font-size: 0.7rem; color: #64748b;">in</span>
                            <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">((L + W) × 2) + 4</span>
                        </div>
                        <div class="col-6">
                            <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                            <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="modal-reel-height-display-${id}">0.00</span>
                            <span style="font-size: 0.7rem; color: #64748b;">in</span>
                            <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">(W + H + 1)</span>
                        </div>
                    </div>
                </div>

                <!-- ─── MATERIAL SELECTION ─── -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.raw_material') }} <span class="text-danger">*</span></label>
                        <select class="form-select select2-modal-material"
                                name="modal_items[${id}][material_id]"
                                id="modal-material-${id}">
                            <option value="${data.material_id || ''}">${data.material_name || 'Select Raw Material...'}</option>
                            @foreach($materials as $material)
            <option value="{{ $material->id }}"
                                        data-unit="{{ $material->unit }}"
                                        data-name="{{ $material->name }}"
                                        data-currency="{{ $material->purchase_currency ?? 'AFN' }}"
                                        data-currency-id="{{ $material->purchase_currency_id ?? '' }}">
                                    {{ $material->name }}
            <span class="text-muted small">
                ({{ $material->purchase_currency ?? 'AFN' }})
                                    </span>
                                </option>
                            @endforeach
            </select>
            <input type="hidden" name="modal_items[${id}][purchase_currency]" id="modal-purchase-currency-${id}" value="${data.currency || 'AFN'}">
                        <input type="hidden" name="modal_items[${id}][purchase_currency_id]" id="modal-purchase-currency-id-${id}" value="">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.formula_type') }}</label>
                        <select class="form-select"
                                name="modal_items[${id}][formula_type]"
                                id="modal-formula-type-${id}"
                                onchange="toggleModalFormulaFields(${id})">
                            <option value="fixed" ${formulaType === 'fixed' ? 'selected' : ''}>{{ __('ui.fixed_quantity') }}</option>
                            <option value="carton_3d" ${formulaType === 'carton_3d' ? 'selected' : ''}>{{ __('ui.three_d_carton') }}</option>
                            <option value="cut_roll" ${formulaType === 'cut_roll' ? 'selected' : ''}>{{ __('ui.cut_roll') }}</option>
                            <option value="fixed_percentage" ${formulaType === 'fixed_percentage' ? 'selected' : ''}>{{ __('ui.material_percent') }}</option>
                            <option value="fixed_rate" ${formulaType === 'fixed_rate' ? 'selected' : ''}>{{ __('ui.fixed_rate') }}</option>
                        </select>
                        <input type="hidden" name="modal_items[${id}][is_formula_based]"
                               id="modal-is-formula-${id}" value="${isFormula ? '1' : '0'}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.formula_quantity') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input auto-calculated"
                               name="modal_items[${id}][quantity]"
                               id="modal-quantity-${id}"
                               value="${data.quantity || 1}"
                               step="0.0001" min="0.0001"
                               placeholder="{{ __('ui.auto_calculated') }}"
                               ${isFormula ? 'readonly' : ''}>
                        <small class="text-muted" id="modal-rolls-hint-${id}">
                            ${isFormula ? '<i class="bi bi-magic"></i> Auto-calculated from formula' : 'Enter quantity manually'}
                        </small>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.roll_weight') }}</label>
                        <input type="text" class="form-control"
                               name="modal_items[${id}][roll_weight]"
                               id="modal-roll-weight-${id}"
                               value="0" readonly>
                        <small class="text-muted">
                            <i class="bi bi-weight-scale"></i> Calculated from formula
                        </small>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">{{ __('ui.unit') }}</label>
                        <input type="text" class="form-control unit-input"
                               name="modal_items[${id}][unit]"
                               id="modal-unit-${id}"
                               value="${data.unit || 'Unit'}" readonly>
                    </div>
                </div>

                <!-- ─── FORMULA CONFIGURATION ─── -->
                <div id="modal-formula-fields-${id}" style="display: ${isFormula ? 'block' : 'none'};" class="modal-formula-config-section">
                    <div class="modal-formula-config-card">
                        <div class="config-header">
                            <div class="config-title">
                                <i class="bi bi-calculator-fill"></i>
                                Formula Configuration
                            </div>
                            <span class="config-badge" id="modal-config-badge-${id}">${formulaType === 'carton_3d' ? '3D Carton' : (formulaType === 'cut_roll' ? 'Cut/Roll' : 'Fixed')}</span>
                        </div>

                        <div class="formula-hint-box" id="modal-formula-hint-box-${id}">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="modal-formula-description-${id}">${formulaType === 'carton_3d' ? '3D Carton formula calculates quantity based on carton dimensions and paper properties.' : (formulaType === 'cut_roll' ? 'Cut/Roll formula calculates quantity based on cut dimensions and material properties.' : 'Configure how this material quantity is calculated')}</span>
                        </div>

                        <!-- 3D Carton Formula -->
                        <div id="modal-carton-3d-fields-${id}" style="display: ${formulaType === 'carton_3d' ? 'block' : 'none'};">
                            <div class="formula-hint-box" style="border-left-color: #3b82f6; background: #eff6ff;">
                                <i class="bi bi-info-circle me-1" style="color: #3b82f6;"></i>
                                <strong style="color: #1e40af;">{{ __('ui.excel_formula_steps') }}</strong>
                                <div style="font-size: 0.75rem; margin-top: 0.25rem; line-height: 1.6;">
                                    <div><strong>{{ __('ui.step_1') }}</strong> Reel Length = ((L + W) × 2) + 4 &nbsp;|&nbsp; Reel Height = W + H + 1</div>
                                    <div><strong>{{ __('ui.step_2') }}</strong> Division Value = Reel Length × Reel Height × GSM × Per Gram Rate</div>
                                    <div><strong>{{ __('ui.step_3') }}</strong> Paper Rate = Division Value ÷ Formula Constant</div>
                                    <div><strong>{{ __('ui.step_4') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate</div>
                                    <div><strong>{{ __('ui.step_5') }}</strong> Work % = Paper Rate × Layers × (Work Percentage / 100)</div>
                                    <div><strong>{{ __('ui.step_6') }}</strong> Net Rate = Print Cost + Paper Rate × Layers + Work %</div>
                                </div>
                            </div>

                            <div class="form-group-row">
                                <div>
                                    <div class="form-label-sm">{{ __('ui.carton_length') }} <span class="text-danger">*</span></div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][length_inch]"
                                           id="modal-length-${id}"
                                           value="${data.length || 17.32}"
                                           placeholder="17.32" step="0.01" min="0.01" required
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.carton_width') }} <span class="text-danger">*</span></div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][width_inch]"
                                           id="modal-width-${id}"
                                           value="${data.width || 15.75}"
                                           placeholder="15.75" step="0.01" min="0.01" required
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.carton_height') }} <span class="text-danger">*</span></div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][height_inch]"
                                           id="modal-height-${id}"
                                           value="${data.height || 12.20}"
                                           placeholder="12.20" step="0.01" min="0.01" required
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.paper_gsm') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][paper_gsm]"
                                           id="modal-paper-gsm-${id}"
                                           value="${data.paper_gsm || 125}"
                                           placeholder="125" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][per_gram_rate]"
                                           id="modal-per-gram-rate-${id}"
                                           value="${data.per_gram_rate || 43}"
                                           placeholder="40" step="0.01" min="0.01"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.layers') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][layers]"
                                           id="modal-layers-${id}"
                                           value="${data.layers || 1}"
                                           placeholder="1" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][multiplication_layer]"
                                           id="modal-multiplication-layer-${id}"
                                           value="${data.multiplication_layer || 1}"
                                           placeholder="5" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][print]"
                                           id="modal-print-${id}"
                                           value="${data.print || 0}"
                                           placeholder="0" step="0.01" min="0"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][formula_constant]"
                                           id="modal-formula-constant-${id}"
                                           value="${data.formula_constant || 1550000}"
                                           placeholder="1550000"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][work_percentage]"
                                           id="modal-carton-work-percentage-${id}"
                                           value="${data.work_percentage || 40}"
                                           placeholder="40" step="0.1" min="0"
                                           oninput="calculateModalItem(${id})">
                                </div>
                            </div>
                        </div>

                        <!-- Cut/Roll Formula -->
                        <div id="modal-cut-roll-fields-${id}" style="display: ${formulaType === 'cut_roll' ? 'block' : 'none'};">
                            <div class="formula-hint-box" style="border-left-color: #10b981; background: #f0fdf4;">
                                <i class="bi bi-info-circle me-1" style="color: #10b981;"></i>
                                <strong style="color: #065f46;">{{ __('ui.formula_steps') }}</strong>
                                <div style="font-size: 0.75rem; margin-top: 0.25rem; line-height: 1.6;">
                                    <div><strong>{{ __('ui.step_1') }}</strong> Multiplication = (Cut Length × Cut Width × Constant) ÷ 1000 (or × Constant)</div>
                                    <div><strong>{{ __('ui.step_2') }}</strong> Paper Rate = (Multiplication × Per Gram Rate × GRH × Ply) ÷ Constant</div>
                                    <div><strong>{{ __('ui.step_3') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate</div>
                                    <div><strong>{{ __('ui.step_4') }}</strong> Work % = Paper Rate × Layers × (Work Percentage / 100)</div>
                                    <div><strong>{{ __('ui.step_5') }}</strong> Net Rate = Print Cost + Paper Rate × Layers + Work %</div>
                                </div>
                            </div>

                            <div class="form-group-row">
                                <div>
                                    <div class="form-label-sm">{{ __('ui.cut_length_inches') }} <span class="text-danger">*</span></div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][cut_length_inch]"
                                           id="modal-cut-length-${id}"
                                           value="${data.cut_length || 0}"
                                           placeholder="26" step="0.01" min="0.01"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.cut_width_inches') }} <span class="text-danger">*</span></div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][cut_width_inch]"
                                           id="modal-cut-width-${id}"
                                           value="${data.cut_width || 0}"
                                           placeholder="30" step="0.01" min="0.01"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">GRH</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][grh]"
                                           id="modal-grh-${id}"
                                           value="${data.grh || 0}"
                                           placeholder="125" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][per_gram_rate]"
                                           id="modal-per-gram-rate-cut-${id}"
                                           value="${data.per_gram_rate || 43}"
                                           placeholder="43" step="0.01" min="0.01"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.ply') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][ply]"
                                           id="modal-ply-${id}"
                                           value="${data.ply || 1}"
                                           placeholder="3" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][print]"
                                           id="modal-print-cut-${id}"
                                           value="${data.print || 0}"
                                           placeholder="0" min="0"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][multiplication_layer]"
                                           id="modal-multiplication-layer-cut-${id}"
                                           value="${data.multiplication_layer || 1}"
                                           placeholder="3" min="1"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][formula_constant]"
                                           id="modal-formula-constant-cut-${id}"
                                           value="${data.formula_constant || 1550000}"
                                           placeholder="1550000"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                    <input type="number" class="form-control-sm-custom"
                                           name="modal_items[${id}][work_percentage]"
                                           id="modal-cut-work-percentage-${id}"
                                           value="${data.work_percentage || 40}"
                                           placeholder="40" step="0.1" min="0"
                                           oninput="calculateModalItem(${id})">
                                </div>
                                <div>
                                    <div class="form-label-sm">{{ __('ui.multiplication_method') }}</div>
                                    <select class="form-control-sm-custom"
                                            name="modal_items[${id}][multiplication_method]"
                                            id="modal-multiplication-method-${id}"
                                            onchange="calculateModalItem(${id})">
                                        <option value="multiply" ${data.multiplication_method === 'multiply' ? 'selected' : ''}>{{ __('ui.multiply') }}</option>
                                        <option value="divide" ${data.multiplication_method === 'divide' ? 'selected' : ''}>{{ __('ui.divide_by_1000') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Fixed Percentage -->
                        <div id="modal-fixed-percentage-fields-${id}" style="display: ${formulaType === 'fixed_percentage' ? 'block' : 'none'};">
                            <div class="formula-hint-box" style="border-left-color: #f59e0b; background: #fef3c7;">
                                <i class="bi bi-info-circle me-1" style="color: #f59e0b;"></i>
                                <strong style="color: #92400e;">{{ __('ui.formula') }}</strong>
                                <span style="font-size: 0.75rem;">Base Material Quantity × (Percentage ÷ 100) = Required Quantity</span>
                            </div>

                            <div class="form-group-row">
                                <div>
                                    <div class="form-label-sm">{{ __('ui.base_material') }}</div>
                                    <select class="form-control-sm-custom select2-modal-base-material"
                                            name="modal_items[${id}][base_material_id]"
                                            id="modal-base-material-${id}"
                                            style="width: 100%;"
                                            onchange="calculateModalItem(${id})">
                                        <option value="">{{ __('ui.select_base_material') }}</option>
                                        @foreach($materials as $material)
            <option value="{{ $material->id }}">{{ $material->name }}</option>
                                        @endforeach
            </select>
        </div>
        <div>
            <div class="form-label-sm">{{ __('ui.percentage_percent') }}</div>
            <input type="number" class="form-control-sm-custom"
                   name="modal_items[${id}][percentage_of_base]"
                                       id="modal-percentage-of-base-${id}"
                                       value="${data.percentage_of_base || 0}"
                                       placeholder="5" step="0.1" min="0.1"
                                       oninput="calculateModalItem(${id})">
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Rate -->
                    <div id="modal-fixed-rate-fields-${id}" style="display: ${formulaType === 'fixed_rate' ? 'block' : 'none'};">
                        <div class="formula-hint-box" style="border-left-color: #dc2626; background: #fef2f2;">
                            <i class="bi bi-info-circle me-1" style="color: #dc2626;"></i>
                            <strong style="color: #991b1b;">{{ __('ui.formula') }}</strong>
                            <span style="font-size: 0.75rem;">(Production Quantity ÷ Per Units) × Rate = Required Quantity</span>
                        </div>

                        <div class="form-group-row">
                            <div>
                                <div class="form-label-sm">{{ __('ui.rate_quantity') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="modal_items[${id}][rate_per_unit]"
                                       id="modal-rate-per-unit-${id}"
                                       value="${data.rate_per_unit || 0}"
                                       placeholder="0.5" step="0.0001" min="0.0001"
                                       oninput="calculateModalItem(${id})">
                            </div>
                            <div>
                                <div class="form-label-sm">{{ __('ui.per_x_units') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="modal_items[${id}][rate_base_units]"
                                       id="modal-rate-base-units-${id}"
                                       value="${data.rate_base_units || 100}"
                                       placeholder="100" min="1"
                                       oninput="calculateModalItem(${id})">
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- ─── CALCULATION DETAILS ─── -->
                <div class="modal-calculation-details" id="modal-carton-calculation-details-${id}" style="display: ${formulaType === 'carton_3d' ? 'block' : 'none'};">
                    <div class="row g-1">
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-reel-length-detail-${id}">0.00</span>
                            <span class="text-muted">in</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-reel-height-detail-${id}">0.00</span>
                            <span class="text-muted">in</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.division_value_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-division-value-${id}">0.00</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-paper-rate-${id}">0.00000000</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_layers_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-paper-rate-by-layers-${id}">0.00000000</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.work_percent_icon') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="modal-work-amount-${id}">0.00000000</span>
                        </div>
                        <div class="col-12 detail-row" style="border-top: 1px dashed #e5e7eb; padding-top: 0.4rem; margin-top: 0.2rem;">
                            <span style="color: #475569; font-weight: 700;">{{ __('ui.net_rate_icon') }}</span>
                            <span style="color: #4f46e5; font-weight: 700; font-size: 0.9rem;" id="modal-row-net-rate-${id}">0.00000000</span>
                        </div>
                    </div>
                </div>

                <!-- ─── COST & NOTES ─── -->
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.cost_per_unit') }} <span class="currency-label usd">USD</span></label>
                        <input type="number" class="form-control cost-input"
                               name="modal_items[${id}][cost_per_unit_usd]"
                               id="modal-cost-usd-${id}"
                               value="${data.cost || 0}" step="0.00001" min="0"
                               oninput="calculateModalItemCost(${id})">
                        <input type="hidden" name="modal_items[${id}][cost_per_unit_afn]" id="modal-cost-afn-${id}" value="0">
                        <small class="text-muted cost-hint" id="modal-cost-hint-${id}">
                            Auto-filled from the latest purchase cost
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.cost_in_afn') }}</label>
                        <input type="text" class="form-control" id="modal-cost-afn-display-${id}" value="0.00" readonly>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Auto-converted using exchange rate
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.purchase_currency') }}</label>
                        <input type="text" class="form-control" id="modal-currency-display-${id}" value="${data.currency || 'AFN'}" readonly>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Currency of the selected material
                        </small>
                    </div>
                </div>

                <div id="modal-batch-info-${id}" class="batch-info" style="display: none;"></div>
            </div>
            `;
        }

        function toggleModalFormulaFields(id) {
            var formulaType = $(`#modal-formula-type-${id}`).val();
            var isFormula = formulaType !== 'fixed';

            if (isFormula) {
                $(`#modal-formula-fields-${id}`).show();
                $(`#modal-is-formula-${id}`).val(1);
                $(`#modal-quantity-${id}`).prop('readonly', true);
                $(`#modal-rolls-hint-${id}`).html('<i class="bi bi-magic"></i> ' + @json(__('ui.auto_excel_formula')));
            } else {
                $(`#modal-formula-fields-${id}`).hide();
                $(`#modal-is-formula-${id}`).val(0);
                $(`#modal-quantity-${id}`).prop('readonly', false);
                $(`#modal-rolls-hint-${id}`).html(@json(__('ui.enter_quantity_manually')));
            }

            // Hide all formula groups
            $(`#modal-carton-3d-fields-${id}`).hide();
            $(`#modal-cut-roll-fields-${id}`).hide();
            $(`#modal-fixed-percentage-fields-${id}`).hide();
            $(`#modal-fixed-rate-fields-${id}`).hide();

            // Show selected formula group
            var badgeText = 'Fixed';
            var descriptionText = 'Fixed quantity - enter the quantity manually.';

            if (formulaType === 'carton_3d') {
                $(`#modal-carton-3d-fields-${id}`).show();
                $(`#modal-reel-dimensions-${id}`).show();
                $(`#modal-carton-calculation-details-${id}`).show();
                badgeText = '3D Carton';
                descriptionText = '3D Carton formula calculates quantity based on carton dimensions and paper properties.';
            } else if (formulaType === 'cut_roll') {
                $(`#modal-cut-roll-fields-${id}`).show();
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
                badgeText = 'Cut/Roll';
                descriptionText = 'Cut/Roll formula calculates quantity based on cut dimensions and material properties.';
            } else if (formulaType === 'fixed_percentage') {
                $(`#modal-fixed-percentage-fields-${id}`).show();
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
                badgeText = '% of Material';
                descriptionText = 'Fixed Percentage calculates quantity as a percentage of a base material.';
            } else if (formulaType === 'fixed_rate') {
                $(`#modal-fixed-rate-fields-${id}`).show();
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
                badgeText = 'Fixed Rate';
                descriptionText = 'Fixed Rate calculates quantity based on a rate per unit.';
            } else {
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
                descriptionText = 'Fixed quantity - enter the quantity manually.';
            }

            $(`#modal-config-badge-${id}`).text(badgeText);
            $(`#modal-formula-description-${id}`).text(descriptionText);

            // Update formula badge
            var badge = $(`#modal-formula-badge-${id}`);
            var labels = {
                fixed: { class: 'fixed', label: 'Fixed' },
                carton_3d: { class: 'carton_3d', label: '3D Carton' },
                cut_roll: { class: 'cut_roll', label: 'Cut/Roll' },
                fixed_percentage: { class: 'fixed_percentage', label: '% of Material' },
                fixed_rate: { class: 'fixed_rate', label: 'Fixed Rate' }
            };
            var info = labels[formulaType] || labels.fixed;
            badge.removeClass('fixed carton_3d cut_roll fixed_percentage fixed_rate')
                .addClass(info.class)
                .text(info.label);

            if (isFormula) {
                calculateModalItem(id);
            }
        }

        function calculateModalItem(id) {
            var formulaType = $(`#modal-formula-type-${id}`).val();
            var exchangeRate = parseFloat($('#modalExchangeRate').val()) || defaultModalExchangeRate;
            var wastage = parseFloat($(`#modal-wastage-${id}`).val()) || 0;
            var costPerUnitUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;

            var quantity = 1;
            var paperRate = 0;
            var paperRateByLayers = 0;
            var workCost = 0;
            var netRate = 0;
            var reelLength = 0;
            var reelHeight = 0;

            if (formulaType === 'carton_3d') {
                var length = parseFloat($(`#modal-length-${id}`).val()) || 0;
                var width = parseFloat($(`#modal-width-${id}`).val()) || 0;
                var height = parseFloat($(`#modal-height-${id}`).val()) || 0;
                var gsm = parseFloat($(`#modal-paper-gsm-${id}`).val()) || 0;
                var perGramRate = parseFloat($(`#modal-per-gram-rate-${id}`).val()) || 0;
                var multiplicationLayer = parseFloat($(`#modal-multiplication-layer-${id}`).val()) || 1;
                var printCost = parseFloat($(`#modal-print-${id}`).val()) || 0;
                var constant = parseFloat($(`#modal-formula-constant-${id}`).val()) || 1550000;
                var workPercentage = parseFloat($(`#modal-carton-work-percentage-${id}`).val()) || 40;

                if (length > 0 && width > 0 && height > 0 && gsm > 0 && perGramRate > 0 && constant > 0) {
                    reelLength = ((length + width) * 2) + 4;
                    reelHeight = width + height + 1;
                    var divisionValue = reelLength * reelHeight * gsm * perGramRate;
                    paperRate = divisionValue / constant;
                    paperRateByLayers = multiplicationLayer * paperRate;
                    workCost = paperRateByLayers * (workPercentage / 100);
                    netRate = printCost + paperRateByLayers + workCost;
                    quantity = 1;

                    $(`#modal-reel-dimensions-${id}`).show();
                    $(`#modal-reel-length-display-${id}`).text(reelLength.toFixed(2));
                    $(`#modal-reel-height-display-${id}`).text(reelHeight.toFixed(2));
                    $(`#modal-reel-length-detail-${id}`).text(reelLength.toFixed(2));
                    $(`#modal-reel-height-detail-${id}`).text(reelHeight.toFixed(2));
                    $(`#modal-carton-calculation-details-${id}`).show();
                    $(`#modal-division-value-${id}`).text(divisionValue.toFixed(2));
                    $(`#modal-paper-rate-${id}`).text(paperRate.toFixed(8));
                    $(`#modal-paper-rate-by-layers-${id}`).text(paperRateByLayers.toFixed(8));
                    $(`#modal-work-amount-${id}`).text(workCost.toFixed(8));
                    $(`#modal-row-net-rate-${id}`).text(netRate.toFixed(8));
                } else {
                    $(`#modal-reel-dimensions-${id}`).hide();
                    $(`#modal-carton-calculation-details-${id}`).hide();
                }
            } else if (formulaType === 'cut_roll') {
                var cutLength = parseFloat($(`#modal-cut-length-${id}`).val()) || 0;
                var cutWidth = parseFloat($(`#modal-cut-width-${id}`).val()) || 0;
                var grh = parseFloat($(`#modal-grh-${id}`).val()) || 0;
                var perGramRate = parseFloat($(`#modal-per-gram-rate-cut-${id}`).val()) || 0;
                var ply = parseFloat($(`#modal-ply-${id}`).val()) || 1;
                var multiplicationLayer = parseFloat($(`#modal-multiplication-layer-cut-${id}`).val()) || 1;
                var printCost = parseFloat($(`#modal-print-cut-${id}`).val()) || 0;
                var constant = parseFloat($(`#modal-formula-constant-cut-${id}`).val()) || 1550000;
                var workPercentage = parseFloat($(`#modal-cut-work-percentage-${id}`).val()) || 40;
                var multiplicationMethod = $(`#modal-multiplication-method-${id}`).val() || 'multiply';

                if (cutLength > 0 && cutWidth > 0 && grh > 0 && perGramRate > 0 && constant > 0) {
                    var multiplicationValue = cutLength * cutWidth * constant;
                    if (multiplicationMethod === 'divide') {
                        multiplicationValue = multiplicationValue / 1000;
                    }
                    paperRate = (multiplicationValue * perGramRate * grh * ply) / constant;
                    paperRateByLayers = multiplicationLayer * paperRate;
                    workCost = paperRateByLayers * (workPercentage / 100);
                    netRate = printCost + paperRateByLayers + workCost;
                    quantity = 1;

                    $(`#modal-reel-dimensions-${id}`).hide();
                    $(`#modal-carton-calculation-details-${id}`).hide();
                }
            } else if (formulaType === 'fixed_percentage') {
                var baseMaterialId = $(`#modal-base-material-${id}`).val();
                var percentage = parseFloat($(`#modal-percentage-of-base-${id}`).val()) || 0;
                var baseQuantity = 0;

                $('.bom-item-row-modal').each(function() {
                    var rowId = $(this).attr('id').replace('modal-item-', '');
                    if ($(`#modal-material-${rowId}`).val() == baseMaterialId && rowId != id) {
                        baseQuantity = parseFloat($(`#modal-quantity-${rowId}`).val()) || 0;
                    }
                });

                quantity = baseQuantity * (percentage / 100);
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
            } else if (formulaType === 'fixed_rate') {
                var rate = parseFloat($(`#modal-rate-per-unit-${id}`).val()) || 0;
                var perUnits = parseFloat($(`#modal-rate-base-units-${id}`).val()) || 1;
                quantity = rate / perUnits;
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
            } else {
                quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
                $(`#modal-reel-dimensions-${id}`).hide();
                $(`#modal-carton-calculation-details-${id}`).hide();
            }

            // ─── HANDLE CURRENCY CONVERSION FOR COST ───
            var purchaseCurrency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';
            var costInUsd = 0;
            var costInAfn = 0;

            if (formulaType === 'carton_3d' || formulaType === 'cut_roll') {
                var netWithWastageAfn = netRate * (1 + wastage / 100);
                costInAfn = netWithWastageAfn;
                costInUsd = exchangeRate > 0 ? netWithWastageAfn / exchangeRate : 0;
            } else {
                costInUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;
                costInAfn = costInUsd * exchangeRate;
            }

            // Store both costs
            $(`#modal-cost-usd-${id}`).val(costInUsd.toFixed(5));
            $(`#modal-cost-afn-${id}`).val(costInAfn.toFixed(2));
            $(`#modal-cost-afn-display-${id}`).val(costInAfn.toFixed(2));

            // Set quantity
            $(`#modal-quantity-${id}`).val(quantity > 0 ? quantity.toFixed(4) : '');

            // Calculate roll weight
            calculateModalRollWeight(id);

            // Update cost preview
            calculateModalItemCost(id);
        }

        function calculateModalRollWeight(id) {
            var quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
            var formulaType = $(`#modal-formula-type-${id}`).val();
            var rollWeight = 0;

            if (formulaType === 'carton_3d') {
                var length = parseFloat($(`#modal-length-${id}`).val()) || 0;
                var width = parseFloat($(`#modal-width-${id}`).val()) || 0;
                var height = parseFloat($(`#modal-height-${id}`).val()) || 0;
                var gsm = parseFloat($(`#modal-paper-gsm-${id}`).val()) || 0;

                if (length > 0 && width > 0 && height > 0 && gsm > 0) {
                    var reelLength = ((length + width) * 2) + 4;
                    var reelHeight = width + height + 1;
                    rollWeight = quantity * (reelLength * reelHeight * gsm) / 1000;
                }
            } else if (formulaType === 'cut_roll') {
                var cutLength = parseFloat($(`#modal-cut-length-${id}`).val()) || 0;
                var cutWidth = parseFloat($(`#modal-cut-width-${id}`).val()) || 0;
                var grh = parseFloat($(`#modal-grh-${id}`).val()) || 0;

                if (cutLength > 0 && cutWidth > 0 && grh > 0) {
                    rollWeight = quantity * (cutLength * cutWidth * grh) / 1000;
                }
            } else {
                rollWeight = quantity;
            }

            $(`#modal-roll-weight-${id}`).val(rollWeight > 0 ? rollWeight.toFixed(4) : 0);
        }

        function calculateModalItemCost(id) {
            var quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
            var costUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;
            var costAfn = parseFloat($(`#modal-cost-afn-${id}`).val()) || 0;
            var exchangeRate = parseFloat($('#modalExchangeRate').val()) || defaultModalExchangeRate;
            var wastage = parseFloat($(`#modal-wastage-${id}`).val()) || 0;
            var formulaType = $(`#modal-formula-type-${id}`).val();
            var purchaseCurrency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';

            var multiplier = (formulaType === 'carton_3d' || formulaType === 'cut_roll')
                ? 1
                : (1 + wastage / 100);

            var totalCostUsd = quantity * costUsd * multiplier;
            var totalCostAfn = quantity * costAfn * multiplier;

            if (purchaseCurrency === 'AFN') {
                totalCostAfn = quantity * costAfn * multiplier;
                totalCostUsd = totalCostAfn / exchangeRate;
            }

            $(`#modal-cost-preview-${id}`).html(`
                Cost: $${totalCostUsd.toFixed(4)} USD
                <span class="text-muted">(؋${totalCostAfn.toFixed(2)} AFN)</span>
            `);

            updateModalTotalCost();
        }

        function updateModalTotalCost() {
            var totalCostUsd = 0;
            var totalCostAfn = 0;
            var exchangeRate = parseFloat($('#modalExchangeRate').val()) || defaultModalExchangeRate;

            $('.bom-item-row-modal').each(function() {
                var id = $(this).attr('id').replace('modal-item-', '');
                var quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
                var costUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;
                var costAfn = parseFloat($(`#modal-cost-afn-${id}`).val()) || 0;
                var formulaType = $(`#modal-formula-type-${id}`).val();
                var wastage = parseFloat($(`#modal-wastage-${id}`).val()) || 0;
                var purchaseCurrency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';

                var multiplier = (formulaType === 'carton_3d' || formulaType === 'cut_roll')
                    ? 1
                    : (1 + wastage / 100);

                var itemCostUsd = quantity * costUsd * multiplier;
                var itemCostAfn = quantity * costAfn * multiplier;

                if (purchaseCurrency === 'AFN') {
                    itemCostAfn = quantity * costAfn * multiplier;
                    itemCostUsd = itemCostAfn / exchangeRate;
                }

                totalCostUsd += itemCostUsd;
                totalCostAfn += itemCostAfn;
            });

            $('#modalTotalMaterialCost').html(`
                Material: $${totalCostUsd.toFixed(2)} USD
                <span class="text-muted">(؋${totalCostAfn.toFixed(2)} AFN)</span>
            `);
        }

        function removeModalItem(id) {
            if (confirm(@json(__('ui.remove_bom_material')))) {
                $(`#modal-item-${id}`).remove();
                updateModalItemCount();
                updateModalTotalCost();
                if ($('#modalItemsContainer').children().length === 0) {
                    $('#modalNoItemsMessage').show();
                    $('#modalTotalMaterialCost').html(@json(__('ui.material_label')) + ' $0.00 USD <span class="text-muted">(؋0.00 AFN)</span>');
                }
            }
        }

        function updateModalItemCount() {
            var count = $('#modalItemsContainer .bom-item-row-modal').length;
            $('#modalItemCount').text(count);
        }

        function calculateModalBOM() {
            // Calculate all items
            $('.bom-item-row-modal').each(function() {
                var id = $(this).attr('id').replace('modal-item-', '');
                if ($(`#modal-formula-type-${id}`).val() !== 'fixed') {
                    calculateModalItem(id);
                }
            });
            updateModalTotalCost();

            // Show calculation steps
            var steps = [];
            $('.bom-item-row-modal').each(function() {
                var id = $(this).attr('id').replace('modal-item-', '');
                var formulaType = $(`#modal-formula-type-${id}`).val();
                var materialName = $(`#modal-material-${id}`).find('option:selected').text() || 'Material';
                var netRate = parseFloat($(`#modal-row-net-rate-${id}`).text()) || 0;
                var quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
                var cost = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;
                var currency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';
                var currencySymbol = currency === 'USD' ? '$' : '؋';

                if (netRate > 0) {
                    steps.push({
                        material: materialName,
                        formula_type: formulaType,
                        net_rate: netRate,
                        quantity: quantity,
                        cost: cost,
                        currency: currency,
                        currency_symbol: currencySymbol
                    });
                }
            });

            if (steps.length > 0) {
                var stepsHtml = '<div class="mb-2"><strong>{{ __('ui.material_calculations') }}</strong></div>';
                for (var i = 0; i < steps.length; i++) {
                    var step = steps[i];
                    var isFinal = i === steps.length - 1;
                    stepsHtml += `
                        <div class="calculation-step-card" style="${isFinal ? 'background: #d1fae5; border: 1px solid #6ee7b7;' : ''}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-number">${step.material}</span>
                                    <div style="font-size: 0.75rem; color: #64748b;">${step.formula_type === 'carton_3d' ? '3D Carton' : (step.formula_type === 'cut_roll' ? 'Cut/Roll' : 'Fixed')}</div>
                                </div>
                                <span class="step-result" style="color: ${isFinal ? '#4f46e5' : '#0f766e'};">${step.currency_symbol}${step.net_rate.toFixed(4)}</span>
                            </div>
                        </div>
                    `;
                }
                $('#modalCalculatorStepsContent').html(stepsHtml);
                $('#modalCalculatorSteps').show();
            } else {
                $('#modalCalculatorSteps').hide();
            }
        }

        function saveModalBOM() {
            var productId = $('#modalProduct').val();
            var bomName = $('#modalBomName').val().trim();
            var exchangeRate = parseFloat($('#modalExchangeRate').val()) || defaultModalExchangeRate;

            if (!productId) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_product_selected')),
                    text: @json(__('ui.select_product_bom'))
                });
                return;
            }

            if (!bomName) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.bom_name_required')),
                    text: @json(__('ui.enter_bom_name'))
                });
                return;
            }

            // ─── COLLECT ITEM DATA ───
            var items = [];
            var hasItems = false;

            $('.bom-item-row-modal').each(function() {
                var id = $(this).attr('id').replace('modal-item-', '');
                var materialId = $(`#modal-material-${id}`).val();
                var materialName = $(`#modal-material-${id}`).find('option:selected').text() || 'Material';
                var costUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;
                var costAfn = parseFloat($(`#modal-cost-afn-${id}`).val()) || 0;
                var currency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';
                var quantity = parseFloat($(`#modal-quantity-${id}`).val()) || 0;
                var unit = $(`#modal-unit-${id}`).val() || 'unit';
                var formulaType = $(`#modal-formula-type-${id}`).val() || 'fixed';
                var isFormula = formulaType !== 'fixed';

                var itemData = {
                    material_id: materialId,
                    material_name: materialName,
                    cost_per_unit_usd: costUsd,
                    cost_per_unit_afn: costAfn,
                    purchase_currency: currency,
                    quantity: quantity,
                    unit: unit,
                    formula_type: formulaType,
                    is_formula_based: isFormula,
                    wastage_percentage: parseFloat($(`#modal-wastage-${id}`).val()) || 0,
                };

                // Add formula fields based on type
                if (formulaType === 'carton_3d') {
                    itemData.length_inch = parseFloat($(`#modal-length-${id}`).val()) || 0;
                    itemData.width_inch = parseFloat($(`#modal-width-${id}`).val()) || 0;
                    itemData.height_inch = parseFloat($(`#modal-height-${id}`).val()) || 0;
                    itemData.paper_gsm = parseFloat($(`#modal-paper-gsm-${id}`).val()) || 0;
                    itemData.per_gram_rate = parseFloat($(`#modal-per-gram-rate-${id}`).val()) || 0;
                    itemData.layers = parseFloat($(`#modal-layers-${id}`).val()) || 1;
                    itemData.multiplication_layer = parseFloat($(`#modal-multiplication-layer-${id}`).val()) || 1;
                    itemData.print = parseFloat($(`#modal-print-${id}`).val()) || 0;
                    itemData.formula_constant = parseFloat($(`#modal-formula-constant-${id}`).val()) || 1550000;
                    itemData.work_percentage = parseFloat($(`#modal-carton-work-percentage-${id}`).val()) || 40;
                } else if (formulaType === 'cut_roll') {
                    itemData.cut_length_inch = parseFloat($(`#modal-cut-length-${id}`).val()) || 0;
                    itemData.cut_width_inch = parseFloat($(`#modal-cut-width-${id}`).val()) || 0;
                    itemData.grh = parseFloat($(`#modal-grh-${id}`).val()) || 0;
                    itemData.per_gram_rate = parseFloat($(`#modal-per-gram-rate-cut-${id}`).val()) || 0;
                    itemData.ply = parseFloat($(`#modal-ply-${id}`).val()) || 1;
                    itemData.multiplication_layer = parseFloat($(`#modal-multiplication-layer-cut-${id}`).val()) || 1;
                    itemData.print = parseFloat($(`#modal-print-cut-${id}`).val()) || 0;
                    itemData.formula_constant = parseFloat($(`#modal-formula-constant-cut-${id}`).val()) || 1550000;
                    itemData.work_percentage = parseFloat($(`#modal-cut-work-percentage-${id}`).val()) || 40;
                    itemData.multiplication_method = $(`#modal-multiplication-method-${id}`).val() || 'multiply';
                } else if (formulaType === 'fixed_percentage') {
                    itemData.base_material_id = $(`#modal-base-material-${id}`).val() || null;
                    itemData.percentage_of_base = parseFloat($(`#modal-percentage-of-base-${id}`).val()) || 0;
                } else if (formulaType === 'fixed_rate') {
                    itemData.rate_per_unit = parseFloat($(`#modal-rate-per-unit-${id}`).val()) || 0;
                    itemData.rate_base_units = parseFloat($(`#modal-rate-base-units-${id}`).val()) || 100;
                }

                items.push(itemData);
                hasItems = true;
            });

            if (!hasItems) {
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.no_materials')),
                    text: @json(__('ui.add_one_material'))
                });
                return;
            }

            // ─── CALCULATE TOTAL COST ───
            var totalCostUsd = 0;
            for (var i = 0; i < items.length; i++) {
                totalCostUsd += items[i].cost_per_unit_usd * items[i].quantity;
            }
            var totalCostAfn = totalCostUsd * exchangeRate;

            // ─── GET SELLING PRICE ───
            var costPerUnitAfn = parseFloat($('#modalCostPerUnit').text().replace('؋', '')) || totalCostAfn;
            var profitMargin = parseFloat($('#modalProfitMargin').val()) || 0;
            var sellingPrice = costPerUnitAfn * (1 + profitMargin / 100);

            $('#modalSaveBtn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span> Saving...'
            );

            $.ajax({
                url: '{{ route("admin.sales.create-bom-from-calculator") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId,
                    name: bomName,
                    items: JSON.stringify(items),
                    exchange_rate: exchangeRate,
                    currency_code: currencyCode,
                    total_cost_usd: totalCostUsd,
                    total_cost_afn: totalCostAfn,
                    selling_price: sellingPrice,
                    sale_id: saleId,
                    work_percentage: parseFloat($('#modalWorkPercentage').val()) || 40,
                    profit_margin_percentage: profitMargin,
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: @json(__('ui.bom_created')),
                            text: @json(__('ui.bom_created_selected')),
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            $('#bomSelect').val(response.bom_id).trigger('change');
                            $('#bomCalculatorModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.error')),
                            text: response.message || 'Failed to create BOM.'
                        });
                        $('#modalSaveBtn').prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save & Select BOM'
                        );
                    }
                },
                error: function(xhr) {
                    var msg = 'Error creating BOM';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.error')),
                        text: msg
                    });
                    $('#modalSaveBtn').prop('disabled', false).html(
                        '<i class="bi bi-save me-1"></i> Save & Select BOM'
                    );
                }
            });
        }

        // ─── MODAL EVENT LISTENERS ───
        $(document).ready(function() {
            // Initialize Select2
            $('#productSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: @json(__('ui.search_for_product')),
                dropdownCssClass: 'custom-select2-dropdown',
                escapeMarkup: function(m) { return m; }
            });

            $('#bomSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: @json(__('ui.choose_saved_manual')),
                allowClear: true
            });

            // Modal select2
            $('#modalProduct').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: @json(__('ui.search_for_product')),
                dropdownParent: $('#bomCalculatorModal'),
                dropdownCssClass: 'custom-select2-dropdown'
            });

            // ─── MODAL ADD ITEM ───
            $('#modalAddItem').on('click', function() {
                var itemId = ++modalItemCounter;
                var html = generateModalItemHtml(itemId, {
                    material_id: '',
                    material_name: 'New Material',
                    cost: 0,
                    currency: 'AFN',
                    quantity: 1,
                    unit: 'Unit',
                    length: 17.32,
                    width: 15.75,
                    height: 12.20,
                    paper_gsm: 125,
                    per_gram_rate: 43,
                    multiplication_layer: 1,
                    print: 0,
                    formula_constant: 1550000,
                    work_percentage: 40,
                    wastage: 5,
                    formula_type: 'carton_3d',
                    cut_length: 0,
                    cut_width: 0,
                    grh: 0,
                    ply: 1,
                    multiplication_method: 'multiply'
                });
                $('#modalItemsContainer').append(html);
                $('#modalNoItemsMessage').hide();
                updateModalItemCount();

                // Initialize select2 for material
                $(`#modal-material-${itemId}`).select2({
                    placeholder: @json(__('ui.select_raw_material')),
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#bomCalculatorModal')
                });

                // Initialize select2 for base material
                $(`#modal-base-material-${itemId}`).select2({
                    placeholder: @json(__('ui.select_base_material')),
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#bomCalculatorModal')
                });

                // ─── Material selection handler ───
                $(`#modal-material-${itemId}`).on('change', function() {
                    var materialId = $(this).val();
                    var id = $(this).attr('id').replace('modal-material-', '');

                    if (materialId) {
                        var selectedOption = $(this).find('option:selected');
                        var currency = selectedOption.data('currency') || 'AFN';
                        var currencyId = selectedOption.data('currency-id') || '';
                        var unit = selectedOption.data('unit') || 'Unit';

                        $(`#modal-currency-display-${id}`).val(currency);
                        $(`#modal-purchase-currency-${id}`).val(currency);
                        $(`#modal-purchase-currency-id-${id}`).val(currencyId);
                        $(`#modal-unit-${id}`).val(unit);

                        var badge = $(`#modal-currency-badge-${id}`);
                        if (currency === 'USD') {
                            badge.removeClass('afn').addClass('usd').text('USD');
                        } else {
                            badge.removeClass('usd').addClass('afn').text('AFN');
                        }

                        fetchModalMaterialCost(materialId, id, currency);
                    } else {
                        $(`#modal-unit-${id}`).val('');
                        $(`#modal-cost-usd-${id}`).val(0);
                        $(`#modal-cost-afn-${id}`).val(0);
                        $(`#modal-cost-afn-display-${id}`).val('0.00');
                        $(`#modal-currency-display-${id}`).val('AFN');
                        calculateModalItemCost(id);
                    }
                });

                // ─── Formula type toggle ───
                toggleModalFormulaFields(itemId);

                // ─── Quantity and cost change handlers ───
                $(`#modal-quantity-${itemId}`).on('keyup change', function() {
                    calculateModalItemCost(itemId);
                    calculateModalRollWeight(itemId);
                });
                $(`#modal-cost-usd-${itemId}`).on('keyup change', function() {
                    calculateModalItemCost(itemId);
                });
                $(`#modal-wastage-${itemId}`).on('keyup change', function() {
                    calculateModalItemCost(itemId);
                });

                calculateModalItem(itemId);
            });

            // ─── MODAL EXCHANGE RATE CHANGE ───
            $('#modalExchangeRate').on('input', function() {
                var exchangeRate = parseFloat($(this).val()) || defaultModalExchangeRate;
                $('.bom-item-row-modal').each(function() {
                    var id = $(this).attr('id').replace('modal-item-', '');
                    var currency = $(`#modal-purchase-currency-${id}`).val() || 'AFN';
                    var costUsd = parseFloat($(`#modal-cost-usd-${id}`).val()) || 0;

                    if (currency === 'USD') {
                        var costAfn = costUsd * exchangeRate;
                        $(`#modal-cost-afn-${id}`).val(costAfn.toFixed(2));
                        $(`#modal-cost-afn-display-${id}`).val(costAfn.toFixed(2));
                    } else {
                        var costAfn = parseFloat($(`#modal-cost-afn-${id}`).val()) || 0;
                        if (costAfn > 0) {
                            var newCostUsd = costAfn / exchangeRate;
                            $(`#modal-cost-usd-${id}`).val(newCostUsd.toFixed(4));
                        }
                    }
                    calculateModalItemCost(id);
                });
            });

            // ─── MODAL CALCULATE BUTTON ───
            $('#modalCalculateBtn').on('click', function() {
                calculateModalBOM();
                Swal.fire({
                    icon: 'info',
                    title: @json(__('ui.calculated')),
                    text: @json(__('ui.bom_calculated_review')),
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            // ─── MODAL RESET ───
            $('#bomCalculatorModal').on('hidden.bs.modal', function() {
                $('#modalSaveBtn').prop('disabled', false).html(
                    '<i class="bi bi-save me-1"></i> Save & Select BOM'
                );
                $('#modalItemsContainer').html('');
                $('#modalNoItemsMessage').show();
                $('#modalItemCount').text('0');
                $('#modalTotalMaterialCost').html(@json(__('ui.material_label')) + ' $0.00 USD <span class="text-muted">(؋0.00 AFN)</span>');
                $('#modalCalculatorSteps').hide();
                modalItemCounter = 0;
            });

            // ─── PRODUCT CHANGE ───
            $('#productSelect').on('change', function() {
                var productId = $(this).val();
                if (productId) {
                    currentProductId = productId;
                    loadBomsForProduct(productId);
                    $('#unitPrice').val(0);
                    $('#manualUnitPrice').val('');
                    $('#priceSource').text(@json(__('ui.select_pricing_method_plain')));
                    $('#addItemBtn').prop('disabled', true);
                    updateAddTotals();
                } else {
                    $('#bomSelect').prop('disabled', true).html('<option value="">{{ __('ui.select_product_first') }}</option>');
                    $('#bomDetailsPreview, #pricingModeNotice').hide();
                    pricingMode = null;
                    manualTemplateBomId = null;
                    $('#unitPrice').val(0);
                    $('#manualUnitPrice').val('');
                    $('#priceSource').text(@json(__('ui.select_bom_auto')));
                    $('#addItemBtn').prop('disabled', true);
                    updateAddTotals();
                }
            });

            // ─── BOM SELECT CHANGE ───
            $('#bomSelect').on('change', function() {
                var value = $(this).val();
                var selectedOption = $(this).find('option:selected');

                if (!value) {
                    pricingMode = null;
                    currentBOMData = null;
                    $('#bomDetailsPreview, #pricingModeNotice').hide();
                    $('#unitPrice').val(0);
                    $('#manualUnitPrice').val('');
                    $('#priceSource').text(@json(__('ui.select_pricing_method_no_dots')));
                    $('#addItemBtn').prop('disabled', true);
                    updateAddTotals();
                    return;
                }

                if (selectedOption.prop('disabled')) {
                    $('#bomDetailsPreview, #pricingModeNotice').hide();
                    $('#unitPrice').val(0);
                    $('#manualUnitPrice').val('');
                    $('#priceSource').text(@json(__('ui.no_bom_available')));
                    $('#addItemBtn').prop('disabled', true);
                    updateAddTotals();
                    return;
                }

                if (value === '__manual__') {
                    openManualBomEstimator();
                    return;
                }

                var selectedExchange = parseFloat(selectedOption.data('exchange')) || 85;
                if ($('#exchangeRate').val() == 1 || $('#exchangeRate').val() == '') {
                    $('#exchangeRate').val(selectedExchange);
                }
                applySavedBomPrice(value);
            });

            // ─── MANUAL BOM TEMPLATE CHANGE ───
            $('#manualBomTemplate').on('change', function() {
                if (pricingMode !== 'manual') return;
                manualTemplateBomId = $(this).val();
                loadBomDetails(manualTemplateBomId);
            });

            // ─── ITEM QTY CHANGE ───
            $('#itemQty').on('input', function() {
                if (pricingMode === 'manual') {
                    recalculateLiveEstimate();
                } else {
                    updateAddTotals();
                }
            });

            $('#manualUnitPrice').on('input', function() {
                updateAddTotals();
            });

            $(document).on('change', '.manual-line-price', function() {
                var $field = $(this);
                var itemId = $field.data('id');
                var rawValue = String($field.val() ?? '').trim();
                var isReset = rawValue === '';
                var unitPrice = isReset ? null : parseFloat(rawValue);

                if (!isReset && (!unitPrice || unitPrice <= 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: @json(__('ui.invalid_price')),
                        text: 'Manual unit price must be greater than zero, or leave it empty to use the system/BOM price.'
                    });
                    return;
                }

                $field.prop('disabled', true);

                $.ajax({
                    url: isReset
                        ? '{{ url('admin/sales/item') }}/' + itemId + '/reset-price'
                        : '{{ url('admin/sales/item') }}/' + itemId + '/manual-price',
                    method: isReset ? 'POST' : 'PATCH',
                    data: isReset
                        ? { _token: '{{ csrf_token() }}' }
                        : { _token: '{{ csrf_token() }}', unit_price: unitPrice },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        $field.prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.error')),
                            text: xhr.responseJSON?.message || (isReset
                                ? 'Could not restore the system/BOM price.'
                                : 'Could not update manual unit price.')
                        });
                    }
                });
            });

            $(document).on('change blur', '.quotation-description-input', function() {
                var $field = $(this);
                var itemId = $field.data('id');

                $.ajax({
                    url: '{{ url('admin/sales/item') }}/' + itemId + '/quotation-description',
                    method: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}',
                        quotation_description: $field.val()
                    }
                }).fail(function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.error')),
                        text: xhr.responseJSON?.message || 'Could not save quotation description.'
                    });
                });
            });

            // ─── EXCHANGE RATE CHANGE ───
            $('#exchangeRate').on('input', function() {
                if (pricingMode === 'manual') {
                    recalculateLiveEstimate();
                } else if (pricingMode === 'saved') {
                    var bomId = $('#bomSelect').val();
                    if (bomId && bomId !== '__manual__') {
                        applySavedBomPrice(bomId);
                    }
                }
                updateAddTotals();
            });

            // ─── ADD ITEM BUTTON ───
            $('#addItemBtn').on('click', function(e) {
                e.preventDefault();
                if (pricingMode === 'manual') {
                    recalculateLiveEstimate();
                }
                addItemToSale();
            });

            // ─── ENTER KEY FOR ADD ITEM ───
            $('#itemQty, #unitPrice').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    if (pricingMode === 'manual') {
                        recalculateLiveEstimate();
                    }
                    addItemToSale();
                }
            });

            // ─── REMOVE ITEM ───
            $(document).on('click', '.remove-item', function() {
                var itemId = $(this).data('id');
                removeItem(itemId);
            });

            // ─── DISCOUNT & PAYMENT ───
            $('#discountAmount, #advancePayment').on('input', function() {
                updateConfirmTotals();
            });

            // ─── INITIAL LOAD ───
            var initialProduct = $('#productSelect').val();
            if (initialProduct) {
                loadBomsForProduct(initialProduct);
            }

            var defaultRate = {{ $exchangeRate ?? 1 }};
            $('#exchangeRate').val(defaultRate);
            updateAddTotals();

            // ─── AUTO-REFRESH ───
            @if($sale->status === 'draft' || $sale->status === 'confirmed')
            var refreshInterval = setInterval(function() {
                if (!document.hidden) {
                    $.ajax({
                        url: '{{ route("admin.sales.check-status", $sale->id) }}',
                        method: 'GET',
                        success: function(response) {
                            if (response.status_changed) {
                                location.reload();
                            }
                        }
                    });
                }
            }, 30000);

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    clearInterval(refreshInterval);
                } else {
                    refreshInterval = setInterval(function() {
                        if (!document.hidden) {
                            $.ajax({
                                url: '{{ route("admin.sales.check-status", $sale->id) }}',
                                method: 'GET',
                                success: function(response) {
                                    if (response.status_changed) {
                                        location.reload();
                                    }
                                }
                            });
                        }
                    }, 30000);
                }
            });
            @endif
        });

        // ─── FETCH MODAL MATERIAL COST ───
        function fetchModalMaterialCost(materialId, id, currency) {
            const $costInput = $(`#modal-cost-usd-${id}`);
            const $costAfnInput = $(`#modal-cost-afn-${id}`);
            const $costAfnDisplay = $(`#modal-cost-afn-display-${id}`);
            const $batchInfo = $(`#modal-batch-info-${id}`);
            const $hint = $(`#modal-cost-hint-${id}`);

            $costInput.prop('disabled', true);
            $costInput.attr('placeholder', 'Loading...');
            $hint.html('<i class="bi bi-hourglass-split me-1"></i> ' + @json(__('ui.fetching_latest_cost')));

            const url = '{{ route("admin.sales.get-material-stock-cost") }}?material_id=' + materialId + '&exchange_rate=' + (parseFloat($('#modalExchangeRate').val()) || 85);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const exchangeRate = parseFloat($('#modalExchangeRate').val()) || 85;

                        let costInUsd = parseFloat(data.cost) || 0;
                        let costInAfn = costInUsd * exchangeRate;

                        // If cost is 0, try to get from weighted average
                        if (costInUsd === 0 && data.weighted_avg_cost) {
                            costInUsd = parseFloat(data.weighted_avg_cost) || 0;
                            if (currency === 'AFN') {
                                costInAfn = costInUsd;
                                costInUsd = costInUsd / exchangeRate;
                            } else {
                                costInAfn = costInUsd * exchangeRate;
                            }
                        }

                        // Update fields
                        $(`#modal-unit-${id}`).val(data.unit || 'Unit');
                        $costInput.val(costInUsd.toFixed(4));
                        $costAfnInput.val(costInAfn.toFixed(2));
                        $costAfnDisplay.val(costInAfn.toFixed(2));

                        // Build batch info
                        let batchHtml = '';
                        if (data.batch_no) {
                            batchHtml = `
                        <div class="fw-semibold mb-2">
                            <i class="bi bi-clock-history me-1"></i>
                            Latest Inventory Batch
                        </div>
                        <div class="batch-item">
                            <span>
                                ${data.batch_no || 'N/A'}
                                ${data.purchase_date ? `(${data.purchase_date})` : ''}
                            </span>
                            <span>
                                ${data.available_qty || 0} ${data.unit || 'unit'} available
                                @ ${currency === 'USD' ? '$' : '؋'}${(currency === 'USD' ? costInUsd : costInAfn).toFixed(4)}
                            </span>
                        </div>
                        ${data.supplier ? `<div class="batch-item"><span>{{ __('ui.supplier') }}</span><span>${data.supplier}</span></div>` : ''}
                    `;
                        } else {
                            batchHtml = '<div class="text-muted">{{ __('ui.no_stock_manual_cost') }}</div>';
                        }

                        $batchInfo.html(batchHtml).show();
                        $hint.html(`<i class="bi bi-check-circle text-success me-1"></i> Latest cost loaded: ${currency === 'USD' ? '$' : '؋'}${(currency === 'USD' ? costInUsd : costInAfn).toFixed(4)} ${data.unit || 'unit'}`);
                    } else {
                        $batchInfo.html(`<div class="text-danger">${response.message || 'Could not fetch cost.'}</div>`).show();
                        $hint.html('<i class="bi bi-exclamation-triangle text-warning me-1"></i> ' + (response.message || @json(__('ui.cost_not_found_manual'))));
                    }
                    $costInput.prop('disabled', false);
                    $costInput.attr('placeholder', 'Enter cost');
                    calculateModalItemCost(id);
                },
                error: function() {
                    $batchInfo.html('<div class="text-warning">{{ __('ui.server_cost_error') }}</div>').show();
                    $costInput.prop('disabled', false);
                    $costInput.attr('placeholder', 'Enter cost');
                    $hint.html('<i class="bi bi-exclamation-triangle text-warning me-1"></i> ' + @json(__('ui.cost_fetch_failed_manual')));
                    calculateModalItemCost(id);
                }
            });
        }
    </script>

    {{-- ─── DELETE MODAL FUNCTIONALITY ─── --}}
    <script>
        (function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDeleteModal);
            } else {
                initDeleteModal();
            }

            function initDeleteModal() {
                const deletePassword = document.getElementById('deletePassword');
                const confirmCheckbox = document.getElementById('confirmDelete');
                const submitBtn = document.getElementById('deleteSubmitBtn');
                const togglePassword = document.getElementById('togglePassword');
                const lockIcon = document.getElementById('lockIcon');
                const passwordError = document.getElementById('passwordError');
                const deleteForm = document.getElementById('deleteForm');
                const deleteModal = document.getElementById('deleteModal');
                const errorMessage = document.getElementById('deleteErrorMessage');
                const errorText = document.getElementById('deleteErrorText');
                const successMessage = document.getElementById('deleteSuccessMessage');
                const successText = document.getElementById('deleteSuccessText');
                const attemptCounter = document.getElementById('attemptCounter');
                const attemptsRemaining = document.getElementById('attemptsRemaining');

                if (!deletePassword || !confirmCheckbox || !submitBtn) return;

                let attempts = 0;
                const MAX_ATTEMPTS = 3;
                const REQUIRED_PASSWORD = 'Delete@123';

                togglePassword.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (deletePassword.type === 'password') {
                        deletePassword.type = 'text';
                        icon.className = 'bi bi-eye-slash';
                    } else {
                        deletePassword.type = 'password';
                        icon.className = 'bi bi-eye';
                    }
                });

                function validateForm() {
                    const password = deletePassword.value;
                    const isChecked = confirmCheckbox.checked;
                    const isValidPassword = password === REQUIRED_PASSWORD;

                    if (isChecked && isValidPassword) {
                        submitBtn.disabled = false;
                    } else {
                        submitBtn.disabled = true;
                    }

                    if (password.length > 0 && !isValidPassword) {
                        passwordError.style.display = 'block';
                    } else {
                        passwordError.style.display = 'none';
                    }

                    if (password.length > 0) {
                        lockIcon.className = 'bi bi-lock-fill lock-icon unlocked';
                    } else {
                        lockIcon.className = 'bi bi-lock-fill lock-icon locked';
                    }

                    if (password === REQUIRED_PASSWORD) {
                        deletePassword.classList.remove('is-invalid');
                        deletePassword.classList.add('is-valid');
                    } else if (password.length > 0) {
                        deletePassword.classList.remove('is-valid');
                        deletePassword.classList.add('is-invalid');
                    } else {
                        deletePassword.classList.remove('is-valid', 'is-invalid');
                    }
                }

                deletePassword.addEventListener('input', function() {
                    errorMessage.classList.remove('show');
                    passwordError.style.display = 'none';
                    validateForm();
                });

                confirmCheckbox.addEventListener('change', function() {
                    validateForm();
                });

                deleteForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const password = deletePassword.value;

                    if (password !== REQUIRED_PASSWORD) {
                        attempts++;
                        const remaining = MAX_ATTEMPTS - attempts;

                        if (attempts >= MAX_ATTEMPTS) {
                            deletePassword.disabled = true;
                            submitBtn.disabled = true;
                            errorText.textContent = @json(__('ui.too_many_attempts'));
                            errorMessage.classList.add('show');
                            attemptCounter.style.display = 'block';
                            attemptCounter.classList.add('danger');
                            attemptsRemaining.textContent = '0';
                            return;
                        }

                        errorText.textContent = @json(__('ui.incorrect_password_attempts')).replace(':count', remaining);
                        errorMessage.classList.add('show');
                        deletePassword.classList.add('is-invalid');
                        attemptCounter.style.display = 'block';
                        attemptsRemaining.textContent = remaining;

                        deletePassword.style.animation = 'shake 0.5s ease';
                        setTimeout(() => { deletePassword.style.animation = ''; }, 500);

                        return;
                    }

                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                successText.textContent = data.message;
                                successMessage.classList.add('show');
                                submitBtn.classList.remove('loading');
                                submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Deleted!';

                                setTimeout(() => {
                                    const modal = bootstrap.Modal.getInstance(deleteModal);
                                    if (modal) modal.hide();
                                    window.location.href = data.redirect || '{{ route("admin.sales.index") }}';
                                }, 1500);
                            } else {
                                errorText.textContent = data.message || @json(__('ui.error_deleting_sale'));
                                errorMessage.classList.add('show');
                                submitBtn.classList.remove('loading');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<span class="spinner"></span><span class="btn-text"><i class="bi bi-trash3"></i> {{ __('ui.delete_sale') }}</span>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            errorText.textContent = @json(__('ui.error_deleting_sale'));
                            errorMessage.classList.add('show');
                            submitBtn.classList.remove('loading');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<span class="spinner"></span><span class="btn-text"><i class="bi bi-trash3"></i> {{ __('ui.delete_sale') }}</span>';
                        });
                });

                deleteModal.addEventListener('hidden.bs.modal', function() {
                    deletePassword.value = '';
                    deletePassword.classList.remove('is-valid', 'is-invalid');
                    deletePassword.disabled = false;
                    confirmCheckbox.checked = false;
                    submitBtn.disabled = true;
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = '<span class="spinner"></span><span class="btn-text"><i class="bi bi-trash3"></i> {{ __('ui.delete_sale') }}</span>';
                    passwordError.style.display = 'none';
                    errorMessage.classList.remove('show');
                    successMessage.classList.remove('show');
                    attemptCounter.style.display = 'none';
                    attemptCounter.classList.remove('danger');
                    attempts = 0;
                    attemptsRemaining.textContent = MAX_ATTEMPTS;
                    lockIcon.className = 'bi bi-lock-fill lock-icon locked';

                    if (deletePassword.type === 'text') {
                        deletePassword.type = 'password';
                        const icon = togglePassword.querySelector('i');
                        icon.className = 'bi bi-eye';
                    }
                });

                deletePassword.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !submitBtn.disabled) {
                        deleteForm.dispatchEvent(new Event('submit'));
                    }
                });

                validateForm();
            }
        })();
    </script>

    {{-- ─── QUICK CARTON QUOTATION SCRIPT ─── --}}
    <script>
        (function () {
            var csSaleId = {{ (int) $sale->id }};
            var csCurrency = @json($currencyCode);
            var csOptionsUrl = @json(route('admin.sales.carton-spec.options'));
            var csCalculateUrl = @json(route('admin.sales.carton-spec.calculate', $sale->id));
            var csAddUrl = @json(route('admin.sales.carton-spec.add', $sale->id));
            var csToken = @json(csrf_token());
            var csOptions = null;
            var csPreview = null;

            if (!document.getElementById('cartonSpecSection')) {
                return;
            }

            function csNumber(value, decimals) {
                var number = parseFloat(value || 0);
                if (isNaN(number)) { number = 0; }
                return number.toLocaleString(undefined, {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            function csFillSelect(element, items, placeholder) {
                element.innerHTML = '';
                if (placeholder) {
                    var empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = placeholder;
                    element.appendChild(empty);
                }
                (items || []).forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = item.value;
                    option.textContent = item.label;
                    element.appendChild(option);
                });
            }

            function csCurrentProfile() {
                if (!csOptions) { return null; }
                var id = document.getElementById('csBoardProfile').value;
                return (csOptions.profiles || []).find(function (profile) {
                    return String(profile.id) === String(id);
                }) || null;
            }

            function csApplyProfile() {
                var profile = csCurrentProfile();
                if (!profile) {
                    document.getElementById('csProfileHelp').textContent = '';
                    return;
                }
                document.getElementById('csPly').value = profile.ply;
                if (profile.flute_type) {
                    var flute = document.getElementById('csFlute');
                    if (flute.querySelector('option[value="' + profile.flute_type + '"]')) {
                        flute.value = profile.flute_type;
                    }
                }
                if (!document.getElementById('csWastage').dataset.touched) {
                    document.getElementById('csWastage').value = profile.wastage_percentage;
                }
                var layers = (profile.layers || []).map(function (layer) {
                    return (layer.gsm || 0) + ' GSM x ' + layer.multiplication_layer;
                }).join(' + ');
                document.getElementById('csProfileHelp').textContent = 'v' + profile.version + ' - ' + layers;
            }

            function csShowError(message) {
                var box = document.getElementById('csError');
                box.textContent = message || '';
                box.style.display = message ? 'block' : 'none';
            }

            function csRenderPreview(preview) {
                csPreview = preview;
                var unitPrice = csCurrency === 'USD'
                    ? preview.commercial.selling_price_usd
                    : preview.commercial.selling_price_afn;
                var orderValue = csCurrency === 'USD'
                    ? preview.commercial.order_total_usd
                    : preview.commercial.order_total_afn;
                var quantity = preview.quantity || 1;

                document.getElementById('csUnitPrice').textContent = csNumber(unitPrice, 4);
                document.getElementById('csOrderValue').textContent = csNumber(orderValue, 2);
                document.getElementById('csPaperKg').textContent = csNumber(preview.paper.physical_kg_total, 3) + ' kg';
                document.getElementById('csAdhesiveKg').textContent = csNumber(preview.adhesive.kg_total, 3) + ' kg';
                document.getElementById('csMaterialCost').textContent = csNumber(preview.physical.material_cost_afn_total, 2);
                document.getElementById('csWorkProfit').textContent = csNumber(preview.commercial.work_profit_afn * quantity, 2);
                document.getElementById('csExpectedProfit').textContent = csNumber(preview.expected_profit_afn, 2);

                var stock = document.getElementById('csStockStatus');
                var shortageBox = document.getElementById('csShortageList');
                if (preview.shortages && preview.shortages.has_shortage) {
                    stock.textContent = 'Shortage';
                    stock.style.color = '#dc2626';
                    var lines = (preview.shortages.materials || [])
                        .filter(function (row) { return !row.is_available; })
                        .map(function (row) {
                            return row.material_name + ': need ' + csNumber(row.required_quantity, 3)
                                + ' kg, available ' + csNumber(row.available_quantity, 3) + ' kg';
                        });
                    shortageBox.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + lines.join('<br>');
                    shortageBox.style.display = 'block';
                } else {
                    stock.textContent = 'Available';
                    stock.style.color = '#059669';
                    shortageBox.style.display = 'none';
                }

                var body = document.getElementById('csAdvancedRows');
                body.innerHTML = '';
                (preview.rows || []).forEach(function (row) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (row.material_name || '-') + '</td>'
                        + '<td>' + row.component_type + '</td>'
                        + '<td class="text-end">' + (row.paper_gsm || '-') + '</td>'
                        + '<td class="text-end">' + (row.multiplication_layer || '-') + '</td>'
                        + '<td class="text-end">' + csNumber(row.kg_per_unit, 6) + '</td>'
                        + '<td class="text-end">' + csNumber(row.kg_with_wastage, 6) + '</td>'
                        + '<td class="text-end">' + csNumber(row.landed_cost_afn_per_kg, 4) + '</td>'
                        + '<td class="text-end">' + (row.apply_work_percentage ? csNumber(row.work_percentage, 2) : '0.00') + '</td>'
                        + '<td class="text-end">' + csNumber(row.row_net_rate_afn, 4) + '</td>';
                    body.appendChild(tr);
                });

                document.getElementById('csSummary').style.display = 'block';
                document.getElementById('csAddBtn').disabled = false;
            }

            function csCalculate() {
                csShowError('');
                document.getElementById('csAddBtn').disabled = true;
                var payload = {
                    _token: csToken,
                    product_id: document.getElementById('csProduct').value,
                    box_style: document.getElementById('csBoxStyle').value,
                    length: document.getElementById('csLength').value,
                    width: document.getElementById('csWidth').value,
                    height: document.getElementById('csHeight').value,
                    dimension_unit: document.getElementById('csUnit').value,
                    board_profile_id: document.getElementById('csBoardProfile').value,
                    ply: document.getElementById('csPly').value || null,
                    flute_type: document.getElementById('csFlute').value || null,
                    printing_option: document.getElementById('csPrinting').value,
                    quantity: document.getElementById('csQuantity').value,
                    wastage_percentage: document.getElementById('csWastage').value,
                    quoted_unit_price: document.getElementById('csQuotedPrice').value || null,
                    quotation_description: document.getElementById('csDescription').value || null
                };

                $.ajax({
                    url: csCalculateUrl,
                    type: 'POST',
                    data: payload,
                    success: function (response) {
                        if (response.success) {
                            csRenderPreview(response.data);
                        } else {
                            csShowError(response.message || 'Calculation failed.');
                        }
                    },
                    error: function (xhr) {
                        var message = 'Calculation failed.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                        csShowError(message);
                    }
                });
            }

            function csAdd() {
                if (!csPreview) {
                    csShowError('Calculate the specification before adding it to the sale.');
                    return;
                }
                csShowError('');
                document.getElementById('csAddBtn').disabled = true;
                $.ajax({
                    url: csAddUrl,
                    type: 'POST',
                    data: {
                        _token: csToken,
                        product_id: document.getElementById('csProduct').value,
                        box_style: document.getElementById('csBoxStyle').value,
                        length: document.getElementById('csLength').value,
                        width: document.getElementById('csWidth').value,
                        height: document.getElementById('csHeight').value,
                        dimension_unit: document.getElementById('csUnit').value,
                        board_profile_id: document.getElementById('csBoardProfile').value,
                        ply: document.getElementById('csPly').value || null,
                        flute_type: document.getElementById('csFlute').value || null,
                        printing_option: document.getElementById('csPrinting').value,
                        quantity: document.getElementById('csQuantity').value,
                        wastage_percentage: document.getElementById('csWastage').value,
                        quoted_unit_price: document.getElementById('csQuotedPrice').value || null,
                        quotation_description: document.getElementById('csDescription').value || null
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: @json(__('ui.added')),
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            document.getElementById('csAddBtn').disabled = false;
                            csShowError(response.message || 'Could not add the specification.');
                        }
                    },
                    error: function (xhr) {
                        document.getElementById('csAddBtn').disabled = false;
                        var message = 'Could not add the specification.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                        csShowError(message);
                    }
                });
            }

            document.getElementById('csBoardProfile').addEventListener('change', csApplyProfile);
            document.getElementById('csWastage').addEventListener('input', function () {
                this.dataset.touched = '1';
            });
            document.getElementById('csCalculateBtn').addEventListener('click', csCalculate);
            document.getElementById('csAddBtn').addEventListener('click', csAdd);

            $.get(csOptionsUrl, function (response) {
                if (!response.success) { return; }
                csOptions = response.data;

                csFillSelect(document.getElementById('csBoxStyle'), csOptions.box_styles, null);
                csFillSelect(document.getElementById('csFlute'),
                    [{ value: '', label: 'None' }].concat(csOptions.flutes), null);
                csFillSelect(document.getElementById('csPrinting'), csOptions.printing, null);
                csFillSelect(document.getElementById('csUnit'),
                    (csOptions.units || []).map(function (unit) {
                        return { value: unit, label: unit.toUpperCase() };
                    }), null);

                var profileItems = (csOptions.profiles || []).map(function (profile) {
                    return {
                        value: String(profile.id),
                        label: profile.name + ' (v' + profile.version + ')'
                    };
                });
                csFillSelect(document.getElementById('csBoardProfile'), profileItems, 'Select board profile...');

                document.getElementById('csBoxStyle').value = csOptions.default_box_style || 'RSC';
                var unitSelect = document.getElementById('csUnit');
                if (unitSelect.querySelector('option[value="cm"]')) {
                    unitSelect.value = 'cm';
                }
                if (csOptions.profiles && csOptions.profiles.length === 1) {
                    document.getElementById('csBoardProfile').value = String(csOptions.profiles[0].id);
                }
                csApplyProfile();

                if (!profileItems.length) {
                    csShowError('No active board profiles are configured. Run the board profile seeder, then reload this page.');
                }
            }).fail(function (xhr) {
                var message = 'Could not load carton quotation options.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    message = 'You do not have permission to create sale items.';
                } else if (xhr.status === 404) {
                    message = 'The carton quotation endpoint was not found. Clear route/config caches and reload.';
                } else if (xhr.status >= 500) {
                    message = 'The carton quotation options failed on the server. Confirm the board profile migrations have been applied.';
                }
                csShowError(message);
            });
        })();
    </script>
@endsection
