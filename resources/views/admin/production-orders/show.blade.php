{{-- resources/views/admin/production-orders/show.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Production Order Details')

@section('css')
    <style>
        /* ─── Variables ─── */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #7c3aed);
            --success-gradient: linear-gradient(135deg, #059669, #10b981);
            --danger-gradient: linear-gradient(135deg, #dc2626, #ef4444);
            --warning-gradient: linear-gradient(135deg, #d97706, #f59e0b);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
        }

        /* ─── Base Styles ─── */
        .detail-label {
            font-size: 0.65rem;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .detail-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        /* ─── Status Badges ─── */
        .status-badge {
            padding: 5px 16px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
            transition: var(--transition-smooth);
            position: relative;
        }
        .status-badge .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .status-badge.pending .pulse-dot {
            background: #d97706;
        }
        .status-badge.in_progress {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .status-badge.in_progress .pulse-dot {
            background: #2563eb;
        }
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status-badge.completed .pulse-dot {
            background: #059669;
        }
        .status-badge.cancelled {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .status-badge.cancelled .pulse-dot {
            background: #6b7280;
        }

        .status-badge.approved {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #c4b5fd;
        }
        .status-badge.approved .pulse-dot { background: #7c3aed; }
        .status-badge.closed {
            background: #e2e8f0;
            color: #334155;
            border: 1px solid #94a3b8;
        }
        .status-badge.closed .pulse-dot {
            background: #475569;
            animation: none;
        }
        .status-badge.reversed {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status-badge.reversed .pulse-dot { background: #dc2626; }

        /* ─── Currency Badge ─── */
        .currency-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }
        .currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }

        /* ─── Action Buttons ─── */
        .action-btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 0.6rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 700;
            font-size: 0.8rem;
            border: none;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }
        .action-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0);
            transition: var(--transition-smooth);
        }
        .action-btn:hover::after {
            background: rgba(255, 255, 255, 0.15);
        }
        .action-btn:active {
            transform: scale(0.96);
        }
        .action-btn-success {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }
        .action-btn-success:hover {
            box-shadow: 0 6px 24px rgba(5, 150, 105, 0.45);
            transform: translateY(-2px);
        }
        .action-btn-danger {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }
        .action-btn-danger:hover {
            box-shadow: 0 6px 24px rgba(220, 38, 38, 0.45);
            transform: translateY(-2px);
        }
        .action-btn-secondary {
            background: #f1f5f9;
            color: #475569;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .action-btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        .action-btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }
        .action-btn-primary:hover {
            box-shadow: 0 6px 24px rgba(79, 70, 229, 0.45);
            transform: translateY(-2px);
        }

        /* ─── Cards ─── */
        .card-modern {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            transition: var(--transition-smooth);
            height: 100%;
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
        }
        .card-modern:hover {
            box-shadow: var(--card-shadow-hover);
            border-color: #e2e8f0;
        }
        .card-modern:nth-child(1) { animation-delay: 0.05s; }
        .card-modern:nth-child(2) { animation-delay: 0.1s; }
        .card-modern:nth-child(3) { animation-delay: 0.15s; }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-modern {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .card-header-modern h6 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-header-modern h6 i {
            color: #4f46e5;
            font-size: 1rem;
        }
        .card-body-modern {
            padding: 1.5rem;
        }

        /* ─── Stats Grid ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-modern {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius-md);
            border: 1px solid #f1f5f9;
            text-align: center;
            transition: var(--transition-smooth);
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 0 0 3px 3px;
        }
        .stat-card-modern:nth-child(1) { animation-delay: 0.05s; }
        .stat-card-modern:nth-child(1)::before { background: #4f46e5; }
        .stat-card-modern:nth-child(2) { animation-delay: 0.1s; }
        .stat-card-modern:nth-child(2)::before { background: #059669; }
        .stat-card-modern:nth-child(3) { animation-delay: 0.15s; }
        .stat-card-modern:nth-child(3)::before { background: #d97706; }
        .stat-card-modern:nth-child(4) { animation-delay: 0.2s; }
        .stat-card-modern:nth-child(4)::before { background: #7c3aed; }

        .stat-card-modern:hover {
            border-color: #e2e8f0;
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }
        .stat-card-modern .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.2rem;
            transition: var(--transition-smooth);
        }
        .stat-card-modern:hover .stat-icon {
            transform: scale(1.05) rotate(-3deg);
        }
        .stat-card-modern .stat-icon.blue {
            background: #eef2ff;
            color: #4f46e5;
        }
        .stat-card-modern .stat-icon.green {
            background: #ecfdf5;
            color: #059669;
        }
        .stat-card-modern .stat-icon.orange {
            background: #fffbeb;
            color: #d97706;
        }
        .stat-card-modern .stat-icon.purple {
            background: #f5f3ff;
            color: #7c3aed;
        }
        .stat-card-modern .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-card-modern .stat-value.green {
            color: #059669;
        }
        .stat-card-modern .stat-value.blue {
            color: #4f46e5;
        }
        .stat-card-modern .stat-value.orange {
            color: #d97706;
        }
        .stat-card-modern .stat-value.purple {
            color: #7c3aed;
        }
        .stat-card-modern .stat-label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 0.25rem;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ─── Progress Ring ─── */
        .progress-ring-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            padding: 0.5rem 0;
        }
        .progress-ring-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            flex-shrink: 0;
        }
        .progress-ring-wrapper svg {
            transform: rotate(-90deg);
        }
        .progress-ring-wrapper .ring-bg {
            fill: none;
            stroke: #f1f5f9;
            stroke-width: 8;
        }
        .progress-ring-wrapper .ring-fg {
            fill: none;
            stroke: #4f46e5;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .progress-ring-wrapper .ring-fg.green {
            stroke: #059669;
        }
        .progress-ring-wrapper .ring-fg.orange {
            stroke: #d97706;
        }
        .progress-ring-wrapper .ring-fg.red {
            stroke: #dc2626;
        }
        .progress-ring-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .progress-ring-center .percentage {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }
        .progress-ring-center .label {
            font-size: 0.6rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-top: 0.15rem;
        }
        .progress-stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .progress-stat-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .progress-stat-item:last-child {
            border-bottom: none;
        }
        .progress-stat-item .label {
            color: #64748b;
        }
        .progress-stat-item .value {
            font-weight: 600;
            color: #0f172a;
        }

        /* ─── Linked Sale Card ─── */
        .linked-sale-card {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border: 1px solid #a5b4fc;
            border-radius: var(--border-radius-md);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            animation: fade-up 0.5s ease 0.1s forwards;
            opacity: 0;
            transition: var(--transition-smooth);
        }
        .linked-sale-card:hover {
            box-shadow: var(--card-shadow-hover);
            border-color: #818cf8;
        }
        .linked-sale-card .sale-link {
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            transition: var(--transition-smooth);
        }
        .linked-sale-card .sale-link:hover {
            text-decoration: underline;
            color: #4338ca;
        }

        /* ─── Cost Summary ─── */
        .cost-summary {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: var(--border-radius-sm);
            border: 1px solid #f1f5f9;
        }
        .cost-summary .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }
        .cost-summary .cost-item:last-child {
            border-bottom: none;
        }
        .cost-summary .cost-total {
            font-weight: 700;
            font-size: 1rem;
            color: #4f46e5;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            margin: 0 -1.25rem;
            padding: 0.6rem 1.25rem;
            border-radius: 0;
        }
        .cost-summary .cost-total span:last-child {
            color: #4f46e5;
        }

        .bg-per-unit {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bg-total {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ─── Material Table ─── */
        .table-modern {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .table-modern thead th {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid #f1f5f9;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .table-modern tbody td {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
            transition: var(--transition-smooth);
        }
        .table-modern tbody tr {
            transition: var(--transition-smooth);
        }
        .table-modern tbody tr:hover {
            background: #f8fafc;
        }
        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }
        .table-modern tfoot {
            background: #f8fafc;
            font-weight: 700;
            border-top: 2px solid #f1f5f9;
        }
        .table-modern tfoot td {
            padding: 0.75rem 1rem;
        }

        .badge-available {
            background: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-shortage {
            background: #fecaca;
            color: #991b1b;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ─── Production Completion Workspace ─── */
        #completeProductionModal .modal-dialog {
            max-width: min(1480px, 96vw);
        }
        #completeProductionModal .completion-modal-content {
            background: #f8fafc;
            max-height: calc(100vh - 2rem);
            overflow: hidden;
        }
        #completeProductionModal .completion-modal-content > form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-height: inherit;
            overflow: hidden;
        }
        #completeProductionModal .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        #completeProductionModal .completion-modal-header,
        #completeProductionModal .completion-modal-footer {
            flex: 0 0 auto;
        }
        .completion-modal-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 1.15rem 1.35rem;
        }
        .completion-context-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .completion-context-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .85rem 1rem;
            min-width: 0;
        }
        .completion-context-card .label {
            color: #64748b;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .completion-context-card .value {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 800;
            margin-top: .15rem;
        }
        .completion-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .completion-section-title {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: .85rem;
        }
        .completion-step {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 800;
            flex: 0 0 auto;
        }
        .completion-output-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-radius: 10px;
            padding: .7rem .85rem;
            margin-top: .75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: .8rem;
        }
        .completion-output-check.is-valid {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        .completion-output-check.is-invalid {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .completion-material-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 290px;
            gap: .85rem;
            align-items: start;
        }
        .completion-material-table {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .completion-material-row {
            transition: background .2s ease, box-shadow .2s ease;
        }
        .completion-material-row.is-over-plan {
            background: #fff7ed;
        }
        .completion-material-row.is-below-plan {
            background: #f0fdf4;
        }
        .completion-material-row.is-on-plan {
            background: #fff;
        }
        .completion-actual-input {
            min-width: 145px;
            font-weight: 700;
        }
        .completion-quick-action {
            font-size: .68rem;
            padding: .22rem .5rem;
            white-space: nowrap;
        }
        .completion-summary-panel {
            position: sticky;
            top: 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .completion-summary-panel .summary-head {
            padding: .85rem 1rem;
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            border-bottom: 1px solid #e0e7ff;
        }
        .completion-summary-panel .summary-body {
            padding: .9rem 1rem;
        }
        .completion-summary-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            padding: .48rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: .78rem;
        }
        .completion-summary-stat:last-child {
            border-bottom: 0;
        }
        .completion-inventory-note {
            margin-top: .85rem;
            padding: .75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #475569;
            font-size: .73rem;
            line-height: 1.45;
        }
        .completion-modal-footer {
            background: #fff;
            border-top: 1px solid #e2e8f0 !important;
            padding: .9rem 1.25rem;
        }
        @media (max-width: 1199.98px) {
            .completion-context-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .completion-material-grid {
                grid-template-columns: 1fr;
            }
            .completion-summary-panel {
                position: static;
            }
        }
        @media (max-width: 767.98px) {
            .completion-context-grid {
                grid-template-columns: 1fr;
            }
            #completeProductionModal .modal-dialog {
                max-width: none;
                margin: .5rem;
            }
            .completion-section {
                padding: .8rem;
            }
        }

        /* ─── Alerts ─── */
        .alert-modern {
            border-radius: var(--border-radius-md);
            border: none;
            padding: 1rem 1.5rem;
            animation: slide-down 0.4s ease forwards;
            opacity: 0;
        }
        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert-modern .btn-close {
            filter: brightness(0.8);
        }

        /* ─── Page Header ─── */
        .page-header-modern {
            padding: 0.5rem 0 1.5rem 0;
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
        }
        .page-header-modern h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .page-header-modern h1 .accent {
            color: #4f46e5;
        }
        .page-header-modern .subtitle {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ─── Responsive ─── */
        @media (max-width: 992px) {
            .linked-sale-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .progress-ring-container {
                flex-direction: column;
                gap: 1rem;
            }
        }
        @media (max-width: 768px) {
            .page-header-modern h1 {
                font-size: 1.4rem;
            }
            .card-body-modern {
                padding: 1rem;
            }
            .cost-summary {
                padding: 1rem;
            }
        }
        @media (max-width: 576px) {
            .action-btn-group .action-btn {
                flex: 1;
                justify-content: center;
                padding: 0.5rem 1rem;
                font-size: 0.7rem;
            }
            .card-header-modern {
                padding: 0.75rem 1rem;
            }
            .card-header-modern h6 {
                font-size: 0.7rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header-modern">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-gear-wide me-2" style="color: #4f46e5;"></i>
                        {{ __('ui.production_order') }} <span class="accent">#{{ $productionOrder->order_number }}</span>
                    </h1>
                    <p class="subtitle">
                        <span><i class="bi bi-box me-1"></i> {{ $productionOrder->product->name ?? 'N/A' }}</span>
                        <span class="text-muted">·</span>
                        <span><i class="bi bi-file-text me-1"></i> {{ $productionOrder->bom->name ?? 'N/A' }}</span>
                        <span class="status-badge {{ $productionOrder->control_status }} ms-2">
                            <span class="pulse-dot"></span>
                            {{ ucfirst(str_replace('_', ' ', $productionOrder->control_status)) }}
                        </span>
                        @if(isset($currencyCode))
                            <span class="currency-badge {{ $currencyCode === 'USD' ? 'usd' : 'afn' }}">
                                <i class="bi bi-currency-exchange me-1"></i>
                                {{ $currencyCode }}
                            </span>
                        @endif
                    </p>
                </div>
                <div class="action-btn-group">
                    @if($productionOrder->status === 'pending' && !$productionOrder->approved_at)
                        @can('approve production orders')
                            <form action="{{ route('production-orders.approve', $productionOrder) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="action-btn action-btn-primary">
                                    <i class="bi bi-shield-check"></i> Approve
                                </button>
                            </form>
                        @endcan
                    @endif

                    @if($productionOrder->status === 'pending' && (!($setting->production_approval_required ?? false) || $productionOrder->approved_at))
                        <button type="button"
                                class="action-btn action-btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#startProductionModal">
                            <i class="bi bi-play-fill"></i> {{ __('ui.start_production') }}
                        </button>
                    @endif

                    @if($productionOrder->status === 'in_progress')
                        <button type="button"
                                class="action-btn action-btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#completeProductionModal">
                            <i class="bi bi-check2"></i> {{ __('ui.complete_production') }}
                        </button>
                    @endif

                    @if($productionOrder->status === 'completed' && !$productionOrder->closed_at)
                        @can('close production orders')
                            <button type="button" class="action-btn action-btn-primary" data-bs-toggle="modal" data-bs-target="#closeProductionModal">
                                <i class="bi bi-lock"></i> Close
                            </button>
                        @endcan
                        @can('reverse production orders')
                            <button type="button" class="action-btn action-btn-danger" data-bs-toggle="modal" data-bs-target="#reverseProductionModal">
                                <i class="bi bi-arrow-counterclockwise"></i> Reverse Completion
                            </button>
                        @endcan
                    @endif

                    @if($productionOrder->closed_at)
                        @can('reopen production orders')
                            <button type="button" class="action-btn action-btn-primary" data-bs-toggle="modal" data-bs-target="#reopenProductionModal">
                                <i class="bi bi-unlock"></i> Reopen
                            </button>
                        @endcan
                    @endif

                    @if(in_array($productionOrder->status, ['pending', 'in_progress']) && !$productionOrder->closed_at)
                        <form action="{{ route('production-orders.cancel', $productionOrder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="action-btn action-btn-danger" onclick="return confirm('Cancel production? This will restore materials to inventory.')">
                                <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('production-orders.index') }}" class="action-btn action-btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        @if($productionOrder->status === 'pending' && ($setting->production_approval_required ?? false) && !$productionOrder->approved_at)
            <div class="alert alert-warning border-0 shadow-sm">
                <i class="bi bi-shield-lock me-1"></i>
                <strong>Approval required:</strong> this work order cannot consume inventory until a supervisor approves it.
            </div>
        @endif

        @if($productionOrder->status === 'pending' && (!($setting->production_approval_required ?? false) || $productionOrder->approved_at))
            <div class="modal fade" id="startProductionModal" tabindex="-1" aria-labelledby="startProductionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('production-orders.start', $productionOrder) }}" method="POST" id="startProductionForm">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <h5 class="modal-title fw-bold" id="startProductionModalLabel">Start Production</h5>
                                    <div class="text-muted small">Enter the quantity you plan to produce in this run.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                            </div>
                            <div class="modal-body pt-3">
                                <div class="rounded-3 p-3 mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Customer Ordered</span>
                                        <strong>{{ number_format((float) $productionOrder->quantity_ordered, 2) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Current Raw Material Supports</span>
                                        <strong>{{ $maxProducibleQuantity !== null ? number_format((float) $maxProducibleQuantity, 2) : 'N/A' }}</strong>
                                    </div>
                                </div>

                                <label for="quantity_planned" class="form-label fw-semibold">
                                    Planned Production Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control form-control-lg {{ isset($errors) && $errors->has('quantity_planned') ? 'is-invalid' : '' }}"
                                       id="quantity_planned"
                                       name="quantity_planned"
                                       value="{{ old('quantity_planned', $productionOrder->quantity_ordered) }}"
                                       min="0.01"
                                       max="999999999.99"
                                       step="0.01"
                                       required>
                                @if(isset($errors) && $errors->has('quantity_planned'))
                                    <div class="invalid-feedback">{{ $errors->first('quantity_planned') }}</div>
                                @endif

                                <div class="alert alert-info mt-3 mb-0 small">
                                    <i class="bi bi-calculator me-1"></i>
                                    Raw-material quantity and production cost will be calculated from this value.
                                    It may be lower or higher than the customer order, but it cannot exceed what current raw material can support.
                                    The real finished quantity will still be entered when production ends.
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-play-circle me-1"></i> Calculate & Start Production
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($productionOrder->status === 'completed' && !$productionOrder->closed_at)
            @can('close production orders')
                <div class="modal fade" id="closeProductionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <form action="{{ route('production-orders.close', $productionOrder) }}" method="POST">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title fw-bold">Close Production Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <p class="small text-muted">Closing locks normal correction. Reopening requires a separate authorized action and audit reason.</p>
                                    <label class="form-label fw-semibold">Closure reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" minlength="10" maxlength="1000" required></textarea>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Close Order</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('reverse production orders')
                <div class="modal fade" id="reverseProductionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <form action="{{ route('production-orders.reverse-completion', $productionOrder) }}" method="POST">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title fw-bold text-danger">Reverse Production Completion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="alert alert-danger small">
                                        This restores the exact FIFO material consumption and the captured pre-completion sale/invoice snapshot. Delivered sales cannot be reversed here.
                                    </div>
                                    <label class="form-label fw-semibold">Reversal reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="4" minlength="10" maxlength="1000" required></textarea>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reverse Completion</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        @if($productionOrder->closed_at)
            @can('reopen production orders')
                <div class="modal fade" id="reopenProductionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <form action="{{ route('production-orders.reopen', $productionOrder) }}" method="POST">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title fw-bold">Reopen Production Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <p class="small text-muted">The reason becomes part of the immutable production control history.</p>
                                    <label class="form-label fw-semibold">Reopen reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" minlength="10" maxlength="1000" required></textarea>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Reopen Order</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        @if($productionOrder->status === 'in_progress')
            <div class="modal fade" id="completeProductionModal" tabindex="-1" aria-labelledby="completeProductionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg completion-modal-content">
                        <form action="{{ route('production-orders.complete', $productionOrder) }}" method="POST" id="productionCompletionForm">

                            @csrf
                            <div class="modal-header completion-modal-header">
                                <div>
                                    <h5 class="modal-title fw-bold" id="completeProductionModalLabel">Complete Production — Actual Results</h5>
                                    <div class="text-muted small">Enter shop-floor actuals. Saving reconciles inventory to the quantities below using FIFO landed costs.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                            </div>

                            <div class="modal-body">
                                <div class="completion-context-grid">
                                    <div class="completion-context-card">
                                        <div class="label">Customer Ordered</div>
                                        <div class="value">{{ number_format((float) $productionOrder->quantity_ordered, 2) }}</div>
                                    </div>
                                    <div class="completion-context-card">
                                        <div class="label">Planned This Run</div>
                                        <div class="value">{{ number_format((float) ($productionOrder->quantity_planned ?: $productionOrder->quantity_ordered), 2) }}</div>
                                    </div>
                                    <div class="completion-context-card">
                                        <div class="label">Materials to Reconcile</div>
                                        <div class="value">{{ count($completionMaterials) }}</div>
                                    </div>
                                    <div class="completion-context-card">
                                        <div class="label">Inventory Method</div>
                                        <div class="value">FIFO Actuals</div>
                                    </div>
                                </div>

                                <div class="completion-section">
                                    <div class="completion-section-title">
                                        <span class="completion-step">1</span>
                                        <span>Finished Output</span>
                                    </div>
                                    <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="quantity_manufactured" class="form-label fw-semibold">Manufactured Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_manufactured') is-invalid @enderror"
                                               id="quantity_manufactured" name="quantity_manufactured"
                                               value="{{ old('quantity_manufactured', $productionOrder->quantity_planned ?: $productionOrder->quantity_ordered) }}"
                                               min="0.01" max="999999999.99" step="0.01" required autofocus>
                                        @error('quantity_manufactured')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">All cartons physically manufactured, including rejected units.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity_produced" class="form-label fw-semibold">Good / Actual Finished Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_produced') is-invalid @enderror"
                                               id="quantity_produced" name="quantity_produced"
                                               value="{{ old('quantity_produced', $productionOrder->quantity_planned ?: $productionOrder->quantity_ordered) }}"
                                               min="0.01" max="999999999.99" step="0.01" required>
                                        @error('quantity_produced')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">Usable cartons available for delivery/invoicing.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity_rejected" class="form-label fw-semibold">Rejected / Scrap Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_rejected') is-invalid @enderror"
                                               id="quantity_rejected" name="quantity_rejected"
                                               value="{{ old('quantity_rejected', 0) }}"
                                               min="0" max="999999999.99" step="0.01" required>
                                        @error('quantity_rejected')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">Defective or unusable cartons from this run.</div>
                                    </div>
                                    </div>
                                    <div class="completion-output-check" id="completionOutputCheck">
                                        <span><i class="bi bi-calculator me-1"></i> Manufactured must equal Good + Rejected.</span>
                                        <strong id="completionOutputCheckValue">Checking…</strong>
                                    </div>
                                </div>

                                <div class="completion-section">
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-2">
                                        <div class="completion-section-title mb-0">
                                            <span class="completion-step">2</span>
                                            <span>Actual Raw Material Consumption</span>
                                        </div>
                                        <span class="badge bg-light text-dark border">BOM remains the planned baseline</span>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        Paper reels are calculated automatically from the actual manufactured quantity.
                                        Enter shop-floor actuals only for materials that can realistically be measured.
                                    </div>
                                <div class="alert alert-info py-2 px-3 mb-3 small">
                                    <strong>Roll paper: no weighing required.</strong>
                                    The ERP scales the frozen BOM requirement to Manufactured Qty (good + rejected) and reconciles FIFO/reel inventory automatically.
                                    Measurable mixing/auxiliary materials can still be corrected manually.
                                </div>

                                @error('materials')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                                <div class="completion-material-grid">
                                    <div class="table-responsive completion-material-table">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Material</th>
                                                <th class="text-end">BOM Planned</th>
                                                <th style="min-width:175px;">Actual Consumed</th>
                                                <th style="min-width:165px;">Waste within Actual</th>
                                                <th style="min-width:165px;">Difference</th>
                                                <th style="min-width:120px;">Unit / Reel</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($completionMaterials as $index => $material)
                                                @php
                                                    $hasReels = !empty($material['reel_options']);
                                                    $isRollBased = (bool) ($material['is_roll_based'] ?? false);
                                                    $useReelSelection = $hasReels
                                                        ? (bool) old('materials.'.$index.'.use_reel_selection', false)
                                                        : false;
                                                    $actualInputValue = $isRollBased
                                                        ? old('materials.'.$index.'.actual_quantity', $material['planned_quantity'])
                                                        : old('materials.'.$index.'.actual_quantity', $material['current_actual_quantity']);
                                                    $wastageInputValue = $isRollBased
                                                        ? old('materials.'.$index.'.wastage_quantity', $material['planned_wastage_quantity'] ?? 0)
                                                        : old('materials.'.$index.'.wastage_quantity', $material['current_wastage_quantity']);
                                                    $initialVariance = is_numeric($actualInputValue)
                                                        ? ((float) $actualInputValue - (float) $material['planned_quantity'])
                                                        : null;
                                                @endphp
                                                <tr class="completion-material-row" data-material-row>
                                                    <td>
                                                        <div class="fw-semibold">{{ $material['material_name'] }}</div>
                                                        @if($isRollBased)
                                                            <div class="small text-success fw-semibold">
                                                                <i class="bi bi-magic me-1"></i> System-calculated roll paper
                                                            </div>
                                                        @else
                                                            <div class="small text-muted">Measurable production material</div>
                                                        @endif
                                                        <input type="hidden" name="materials[{{ $index }}][material_id]" value="{{ $material['material_id'] }}">
                                                        <input type="hidden" name="materials[{{ $index }}][unit]" value="{{ $material['unit'] }}">
                                                    </td>
                                                    <td class="text-end">
                                                        <strong>{{ number_format((float) $material['planned_quantity'], 4) }}</strong>
                                                        <div class="small text-muted">{{ $material['unit'] }}</div>
                                                    </td>
                                                    <td>
                                                        @if($isRollBased)
                                                            <input type="hidden"
                                                                   class="actual-consumption-input auto-roll-consumption"
                                                                   name="materials[{{ $index }}][actual_quantity]"
                                                                   value="{{ $actualInputValue }}"
                                                                   data-auto-roll="1"
                                                                   data-planned="{{ (float) $material['planned_quantity'] }}"
                                                                   data-planned-run="{{ (float) ($material['planned_run_quantity'] ?? 0) }}"
                                                                   data-unit="{{ $material['unit'] }}"
                                                                   data-variance-target="materialVariance{{ $index }}"
                                                                   data-variance-status-target="materialVarianceStatus{{ $index }}">
                                                            <div class="border rounded-3 px-3 py-2 bg-light">
                                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                                    <span class="small text-muted">Calculated</span>
                                                                    <strong class="text-primary auto-roll-consumption-display">
                                                                        {{ number_format((float) $actualInputValue, 4) }} {{ $material['unit'] }}
                                                                    </strong>
                                                                </div>
                                                            </div>
                                                            <div class="form-text">Updates automatically when Manufactured Qty changes.</div>
                                                        @else
                                                            <div class="d-flex align-items-center gap-2">
                                                                <input type="number"
                                                                       class="form-control actual-consumption-input completion-actual-input @error('materials.'.$index.'.actual_quantity') is-invalid @enderror"
                                                                       name="materials[{{ $index }}][actual_quantity]"
                                                                       value="{{ $actualInputValue }}"
                                                                       min="0"
                                                                       step="0.000001"
                                                                       data-planned="{{ (float) $material['planned_quantity'] }}"
                                                                       data-unit="{{ $material['unit'] }}"
                                                                       data-variance-target="materialVariance{{ $index }}"
                                                                       data-variance-status-target="materialVarianceStatus{{ $index }}"
                                                                       required>
                                                                <button type="button"
                                                                        class="btn btn-outline-secondary completion-quick-action use-bom-plan"
                                                                        title="Copy BOM planned quantity into Actual Consumed">
                                                                    Use Plan
                                                                </button>
                                                            </div>
                                                            @error('materials.'.$index.'.actual_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                            <div class="form-text">Enter the quantity physically used on the shop floor.</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($isRollBased)
                                                            <input type="hidden"
                                                                   class="auto-roll-wastage"
                                                                   name="materials[{{ $index }}][wastage_quantity]"
                                                                   value="{{ $wastageInputValue }}"
                                                                   data-planned-wastage="{{ (float) ($material['planned_wastage_quantity'] ?? 0) }}"
                                                                   data-planned-run="{{ (float) ($material['planned_run_quantity'] ?? 0) }}">
                                                            <div class="small text-muted">
                                                                Included in system formula
                                                                <div class="fw-semibold text-dark auto-roll-wastage-display">
                                                                    {{ number_format((float) $wastageInputValue, 4) }} {{ $material['unit'] }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <input type="number"
                                                                   class="form-control @error('materials.'.$index.'.wastage_quantity') is-invalid @enderror"
                                                                   name="materials[{{ $index }}][wastage_quantity]"
                                                                   value="{{ $wastageInputValue }}"
                                                                   min="0"
                                                                   step="0.000001"
                                                                   required>
                                                            @error('materials.'.$index.'.wastage_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                            <div class="form-text">Must be part of Actual Consumed.</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold {{ $initialVariance !== null && $initialVariance > 0.000001 ? 'text-danger' : ($initialVariance !== null && $initialVariance < -0.000001 ? 'text-success' : 'text-muted') }}"
                                                             id="materialVariance{{ $index }}">
                                                            @if($initialVariance === null)
                                                                —
                                                            @else
                                                                {{ $initialVariance > 0.000001 ? '+' : '' }}{{ number_format($initialVariance, 4) }} {{ $material['unit'] }}
                                                            @endif
                                                        </div>
                                                        <div class="small text-muted" id="materialVarianceStatus{{ $index }}">
                                                            @if($initialVariance !== null && $initialVariance > 0.000001)
                                                                Over BOM plan
                                                            @elseif($initialVariance !== null && $initialVariance < -0.000001)
                                                                Below BOM plan
                                                            @elseif($initialVariance !== null)
                                                                On BOM plan
                                                            @else
                                                                Actual − planned
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold">{{ $material['unit'] }}</div>
                                                        @if($hasReels)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary mt-1"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#reelDetails{{ $index }}"
                                                                    aria-expanded="{{ $useReelSelection ? 'true' : 'false' }}"
                                                                    aria-controls="reelDetails{{ $index }}">
                                                                <i class="bi bi-box-seam me-1"></i> Reel tracking
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>

                                                @if($hasReels)
                                                    <tr class="bg-light">
                                                        <td colspan="6" class="p-0 border-0">
                                                            <div class="collapse {{ $useReelSelection ? 'show' : '' }}" id="reelDetails{{ $index }}">
                                                                <div class="p-3 border-top border-bottom">
                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input reel-selection-toggle"
                                                                               type="checkbox"
                                                                               role="switch"
                                                                               id="useReelSelection{{ $index }}"
                                                                               name="materials[{{ $index }}][use_reel_selection]"
                                                                               value="1"
                                                                               data-target="reelSelectionPanel{{ $index }}"
                                                                               @checked($useReelSelection)>
                                                                        <label class="form-check-label fw-semibold" for="useReelSelection{{ $index }}">
                                                                            Use physical reel declaration
                                                                        </label>
                                                                        <div class="form-text">
                                                                            Advanced/optional. Normal production requires no reel weighing and uses automatic FIFO allocation. Turn this on only when the operator genuinely knows the exact reel allocation or has a measured remainder.
                                                                        </div>
                                                                    </div>

                                                                    <div id="reelSelectionPanel{{ $index }}"
                                                                         class="reel-selection-panel"
                                                                         style="{{ $useReelSelection ? '' : 'display:none;' }}">
                                                                        @error('materials.'.$index.'.reels')
                                                                            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                                                                        @enderror

                                                                        <div class="alert alert-light border py-2 px-3 small mb-3">
                                                                            This does not replace the ERP's calculated total paper consumption. It only lets you identify the physical reel allocation or record an observed remainder for reconciliation.
                                                                        </div>

                                                                        <div class="table-responsive border rounded-3">
                                                                            <table class="table table-sm align-middle mb-0">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>Reel / Source</th>
                                                                                        <th>Status</th>
                                                                                        <th class="text-end">Available for this run</th>
                                                                                        <th style="min-width:160px;">Consumed kg</th>
                                                                                        <th style="min-width:180px;">Final weighed remainder</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach($material['reel_options'] as $reelIndex => $reel)
                                                                                        <tr class="reel-option-row"
                                                                                            data-selectable="{{ $reel['selectable'] ? '1' : '0' }}">
                                                                                            <td>
                                                                                                <div class="fw-semibold">{{ $reel['reel_code'] }}</div>
                                                                                                <small class="text-muted">
                                                                                                    Batch {{ $reel['batch_no'] ?: '—' }}
                                                                                                    @if($reel['purchase_no'])
                                                                                                        · {{ $reel['purchase_no'] }}
                                                                                                    @endif
                                                                                                </small>
                                                                                                <input type="hidden"
                                                                                                       name="materials[{{ $index }}][reels][{{ $reelIndex }}][reel_id]"
                                                                                                       value="{{ $reel['id'] }}">
                                                                                            </td>
                                                                                            <td>
                                                                                                @if($reel['selectable'])
                                                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                                                        {{ $reel['status'] === 'consumed' ? 'Used in this run' : ucfirst($reel['status']) }}
                                                                                                    </span>
                                                                                                @else
                                                                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                                                                        {{ ucfirst($reel['status']) }} · unavailable
                                                                                                    </span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td class="text-end">
                                                                                                <strong>{{ number_format((float) $reel['available_for_run_kg'], 4) }} kg</strong>
                                                                                                @if((float) $reel['current_run_consumed_kg'] > 0)
                                                                                                    <div class="small text-muted">
                                                                                                        {{ number_format((float) $reel['current_run_consumed_kg'], 4) }} kg provisionally allocated
                                                                                                    </div>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td>
                                                                                                <input type="number"
                                                                                                       class="form-control form-control-sm reel-consumed-input"
                                                                                                       name="materials[{{ $index }}][reels][{{ $reelIndex }}][consumed_kg]"
                                                                                                       value="{{ old('materials.'.$index.'.reels.'.$reelIndex.'.consumed_kg') }}"
                                                                                                       min="0"
                                                                                                       step="0.000001"
                                                                                                       placeholder="Actual kg"
                                                                                                       @disabled(!$reel['selectable'])>
                                                                                            </td>
                                                                                            <td>
                                                                                                <input type="number"
                                                                                                       class="form-control form-control-sm"
                                                                                                       name="materials[{{ $index }}][reels][{{ $reelIndex }}][final_remaining_kg]"
                                                                                                       value="{{ old('materials.'.$index.'.reels.'.$reelIndex.'.final_remaining_kg') }}"
                                                                                                       min="0"
                                                                                                       step="0.000001"
                                                                                                       placeholder="Optional scale weight"
                                                                                                       @disabled(!$reel['selectable'])>
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>

                                                                        <div class="row g-2 mt-2 align-items-start">
                                                                            <div class="col-lg-7">
                                                                                <div class="form-text">
                                                                                    Enter consumed kg, or leave it blank and enter the final weighed remainder so consumption can be inferred.
                                                                                    If both are entered, consumed kg drives inventory and the weighed remainder is recorded separately as remnant variance.
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-lg-5">
                                                                                <input type="text"
                                                                                       class="form-control form-control-sm"
                                                                                       name="materials[{{ $index }}][selection_note]"
                                                                                       value="{{ old('materials.'.$index.'.selection_note') }}"
                                                                                       maxlength="1000"
                                                                                       placeholder="Optional reel / scale note">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr><td colspan="6" class="text-center text-danger py-4">No production materials are available for completion.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    </div>

                                    <aside class="completion-summary-panel" id="completionSummaryPanel">
                                        <div class="summary-head">
                                            <div class="fw-bold"><i class="bi bi-clipboard-check me-1"></i> Completion Summary</div>
                                            <div class="small text-muted mt-1">Live review before inventory reconciliation</div>
                                        </div>
                                        <div class="summary-body">
                                            <div class="completion-summary-stat">
                                                <span>Materials</span>
                                                <strong id="completionMaterialCount">{{ count($completionMaterials) }}</strong>
                                            </div>
                                            <div class="completion-summary-stat">
                                                <span class="text-danger">Over BOM plan</span>
                                                <strong class="text-danger" id="completionOverCount">0</strong>
                                            </div>
                                            <div class="completion-summary-stat">
                                                <span class="text-success">Below BOM plan</span>
                                                <strong class="text-success" id="completionBelowCount">0</strong>
                                            </div>
                                            <div class="completion-summary-stat">
                                                <span>On BOM plan</span>
                                                <strong id="completionOnPlanCount">0</strong>
                                            </div>
                                            <div class="completion-inventory-note">
                                                <div class="fw-bold text-dark mb-1"><i class="bi bi-box-arrow-down me-1"></i> Inventory impact</div>
                                                Actual Consumed is authoritative. Higher actual usage deducts additional FIFO stock; lower actual usage restores the unused provisional allocation. Actual FIFO landed cost becomes the production material cost, and the linked sale's realized profit is recalculated from that actual cost.
                                            </div>
                                        </div>
                                    </aside>
                                </div>

                                <div class="alert alert-warning mt-3 mb-0 small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Completion is atomic. Materials without a physical reel declaration keep FIFO. An explicit reel declaration replaces only that material's provisional allocation and preserves the landed cost of each selected source batch. Scale remainders are observational and never overwrite stock directly.
                                </div>
                                </div>
                            </div>

                            <div class="modal-footer completion-modal-footer">
                                <div class="me-auto small text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    BOM remains unchanged; only actual production consumption and inventory are reconciled.
                                </div>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                <button type="submit" class="btn btn-success px-4" id="completeProductionSubmit" @disabled(empty($completionMaterials))>
                                    <i class="bi bi-check-circle me-1"></i> Save Actuals & Complete Production
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ─── FLASH MESSAGES ─── --}}
        @if(session('error'))
            <div class="alert alert-danger alert-modern alert-dismissible fade show mt-3 mb-4" role="alert" id="errorAlert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-modern alert-dismissible fade show mt-3 mb-4" role="alert" id="successAlert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-modern alert-dismissible fade show mt-3 mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('warning') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-modern alert-dismissible fade show mt-3 mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('info') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        {{-- ─── LINKED SALE ALERT ─── --}}
        @if($sale)
            <div class="linked-sale-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <i class="bi bi-cart-plus" style="color: #4f46e5; font-size: 1.25rem;"></i>
                    <div>
                        <strong>Linked Sale Order:</strong>
                        <a href="{{ route('admin.sales.show', $sale->id) }}" class="sale-link">
                            #{{ $sale->sale_no }}
                        </a>
                        <span class="badge bg-{{ $sale->is_produced ? 'success' : 'warning' }} ms-2">
                            <i class="bi bi-{{ $sale->is_produced ? 'check-circle-fill' : 'clock' }} me-1"></i>
                            {{ $sale->is_produced ? 'Produced' : 'Pending Production' }}
                        </span>
                        <span class="badge bg-{{ $isUSD ? 'success' : 'warning' }} ms-2">
                            <i class="bi bi-currency-exchange me-1"></i>
                            {{ $currencyCode }}
                        </span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted" style="font-size: 0.85rem;">
                        <i class="bi bi-person me-1"></i> {{ $sale->customer->name ?? 'N/A' }}
                    </span>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        <i class="bi bi-cash me-1"></i>
                        {{ $currencySymbol }}{{ number_format($sale->grand_total ?? 0, 2) }}
                    </span>
                    <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view') }}
                    </a>
                </div>
            </div>
        @endif

        {{-- ─── STATS CARDS ─── --}}
        <div class="stats-grid">
            <div class="stat-card-modern">
                <div class="stat-icon blue">
                    <i class="bi bi-hash"></i>
                </div>
                <div class="stat-value blue">{{ number_format($productionOrder->quantity_ordered) }}</div>
                <div class="stat-label">Ordered Quantity</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon purple">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div class="stat-value purple">
                    {{ $productionOrder->quantity_planned !== null ? number_format((float) $productionOrder->quantity_planned) : '—' }}
                </div>
                <div class="stat-label">Planned Production</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-value green">{{ number_format($productionOrder->quantity_produced) }}</div>
                <div class="stat-label">Good / Finished Qty</div>
            </div>
            @if($productionOrder->quantity_manufactured !== null)
                <div class="stat-card-modern">
                    <div class="stat-icon blue"><i class="bi bi-boxes"></i></div>
                    <div class="stat-value blue">{{ number_format((float) $productionOrder->quantity_manufactured) }}</div>
                    <div class="stat-label">Manufactured Qty</div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon orange"><i class="bi bi-trash3"></i></div>
                    <div class="stat-value orange">{{ number_format((float) $productionOrder->quantity_rejected) }}</div>
                    <div class="stat-label">Rejected / Scrap Qty</div>
                </div>
            @endif
            <div class="stat-card-modern">
                <div class="stat-icon orange">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-value orange">{{ round($progress) }}%</div>
                <div class="stat-label">{{ __('ui.progress') }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon purple">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-value purple">{{ $currencySymbol }}{{ number_format($totalCostInCurrency, 2) }}</div>
                <div class="stat-label">Total Cost ({{ $currencyCode }})</div>
            </div>
        </div>

        {{-- ─── MAIN CONTENT GRID ─── --}}
        <div class="row g-4">

            {{-- ─── LEFT COLUMN: Order Information ─── --}}
            <div class="col-lg-4 col-md-6">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-info-circle"></i> Order Information
                        </h6>
                        <span class="text-muted" style="font-size: 0.6rem; font-weight: 600;">
                            <i class="bi bi-clock"></i> {{ $productionOrder->created_at->format('d M Y, H:i') }}
                        </span>
                    </div>
                    <div class="card-body-modern">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="detail-label">Order Number</div>
                                <div class="detail-value">{{ $productionOrder->order_number }}</div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.status') }}</div>
                                <div class="detail-value">
                                    <span class="status-badge {{ $productionOrder->status }}" style="font-size: 0.65rem; padding: 2px 12px;">
                                        <span class="pulse-dot"></span>
                                        {{ $productionOrder->status_label }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">{{ __('ui.product') }}</div>
                                <div class="detail-value">{{ $productionOrder->product->name ?? 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 0.15rem;">
                                    Category: {{ $productionOrder->product->category->name ?? 'Uncategorized' }}
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">{{ __('ui.bom') }}</div>
                                <div class="detail-value">{{ $productionOrder->bom->name ?? 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 0.15rem;">
                                    Code: {{ $productionOrder->bom->code ?? 'N/A' }} · Version: {{ $productionOrder->bom->version ?? '1.0' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.start_date') }}</div>
                                <div class="detail-value">
                                    {{ $productionOrder->start_date ? $productionOrder->start_date->format('d M, Y') : '-' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.completion_date') }}</div>
                                <div class="detail-value">
                                    {{ $productionOrder->completion_date ? $productionOrder->completion_date->format('d M, Y') : '-' }}
                                </div>
                            </div>
                            @if($productionOrder->notes)
                                <div class="col-12">
                                    <div class="detail-label">{{ __('ui.notes') }}</div>
                                    <div class="detail-value" style="font-weight: 400; font-size: 0.85rem; color: #475569;">
                                        {{ $productionOrder->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── MIDDLE COLUMN: Cost Summary ─── --}}
            <div class="col-lg-4 col-md-6">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-calculator"></i> Cost Summary
                            <span class="badge bg-{{ $isUSD ? 'success' : 'warning' }} ms-1">
                                <i class="bi bi-currency-exchange me-1"></i>
                                {{ $currencyCode }}
                            </span>
                        </h6>
                        <span class="badge bg-secondary" style="font-size: 0.6rem; font-weight: 700;">
                            <i class="bi bi-box me-1"></i>
                            {{ number_format($baseQuantity) }} units
                        </span>
                    </div>
                    <div class="card-body-modern">
                        <div class="cost-summary">

                            {{-- ─── PROGRESS RING ─── --}}
                            <div class="progress-ring-container">
                                <div class="progress-ring-wrapper">
                                    <svg width="140" height="140" viewBox="0 0 140 140">
                                        <circle class="ring-bg" cx="70" cy="70" r="60"/>
                                        <circle class="ring-fg {{ $progress >= 75 ? 'green' : ($progress >= 50 ? 'orange' : 'red') }}"
                                                cx="70" cy="70" r="60"
                                                stroke-dasharray="376.99"
                                                stroke-dashoffset="{{ 376.99 - (376.99 * $progress / 100) }}"/>
                                    </svg>
                                    <div class="progress-ring-center">
                                        <div class="percentage">{{ round($progress) }}%</div>
                                        <div class="label">Order Fulfillment</div>
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <div class="progress-stat-item">
                                        <span class="label">Ordered</span>
                                        <span class="value">{{ number_format($productionOrder->quantity_ordered) }}</span>
                                    </div>
                                    <div class="progress-stat-item">
                                        <span class="label">Good / Finished</span>
                                        <span class="value">{{ number_format($productionOrder->quantity_produced) }}</span>
                                    </div>
                                    <div class="progress-stat-item">
                                        <span class="label">Production Variance</span>
                                        @php
                                            $productionVariance = (float) ($productionOrder->quantity_manufactured ?? $productionOrder->quantity_produced) - (float) ($productionOrder->quantity_planned ?? $productionOrder->quantity_ordered);
                                        @endphp
                                        <span class="value {{ $productionVariance > 0 ? 'text-success' : ($productionVariance < 0 ? 'text-warning' : '') }}">
                                            {{ $productionVariance > 0 ? '+' : '' }}{{ number_format($productionVariance, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <hr style="margin: 1rem 0; border-color: #f1f5f9;">

                            {{-- ─── PER UNIT COSTS ─── --}}
                            <div class="bg-per-unit">
                                <span style="font-size: 0.65rem; text-transform: uppercase; color: #4f46e5; font-weight: 700;">
                                    <i class="bi bi-box me-1"></i> Per Unit Costs
                                </span>
                                @if($productionOrder->bom)
                                    <span style="font-size: 0.6rem; color: #6b7280;">
                                        <i class="bi bi-file-text me-1"></i>
                                        {{ $productionOrder->bom->code ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>

                            <div class="cost-item">
                                <span>
                                    Material Cost / Unit
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($materialCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($materialCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>
                                    {{ ($usesStandardWorkCost ?? false) ? 'Standard Work / Profit (' . number_format($workPercentage ?? 40, 0) . '%) — Not a production cost' : 'Labor Cost / Unit' }}
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($laborCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($laborCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>
                                    Overhead Cost / Unit
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($overheadCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($overheadCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>

                            <div style="border-top: 2px dashed #e5e7eb; margin: 0.75rem 0;"></div>

                            {{-- ─── TOTAL COSTS ─── --}}
                            <div class="bg-total">
                                <span style="font-size: 0.65rem; text-transform: uppercase; color: #065f46; font-weight: 700;">
                                    <i class="bi bi-calculator me-1"></i> {{ ($hasActualConsumption ?? false) ? 'Actual FIFO Costs' : 'Estimated Costs' }}
                                    <span style="font-weight: 400; color: #6b7280; font-size: 0.6rem;">
                                        ({{ number_format($baseQuantity) }} units)
                                    </span>
                                </span>
                            </div>

                            <div class="cost-item">
                                <span>{{ __('ui.total_material_cost') }}</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalMaterialCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalMaterialCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>{{ ($usesStandardWorkCost ?? false) ? 'Standard Work / Profit (' . number_format($workPercentage ?? 40, 0) . '%) — Not a production cost' : 'Total Labor Cost' }}</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalLaborCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalLaborCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>Total Overhead Cost</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalOverheadCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalOverheadCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            @if(($otherDirectCostInCurrency ?? 0) != 0)
                                <div class="cost-item">
                                    <span>Other Direct Cost</span>
                                    <span class="fw-semibold">
                                        {{ $isUSD ? '$' : '؋' }}{{ number_format($otherDirectCostInCurrency ?? 0, 2) }}
                                        @if(!$isUSD)
                                            <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                                (${{ number_format($otherDirectCostUsd ?? 0, 2) }} USD)
                                            </small>
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="cost-item cost-total" style="margin-top: 0.75rem; border-radius: var(--border-radius-sm);">
                                <span>{{ __('ui.total_cost') }}</span>
                                <span>
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>

                            @if($quantityProduced > 0)
                                <div class="cost-item" style="color: #059669; border-top: 1px solid #f1f5f9; margin-top: 0.5rem; padding-top: 0.75rem;">
                                    <span>Actual Cost per Unit (Produced)</span>
                                    <span class="fw-semibold" style="color: #059669;">
                                        {{ $isUSD ? '$' : '؋' }}{{ number_format($costPerUnitInCurrency ?? 0, 4) }}
                                    </span>
                                </div>
                            @endif

                            {{-- ─── EXCHANGE RATE ─── --}}
                            @if(!$isUSD && isset($exchangeRate))
                                <div class="cost-item" style="border-top: 2px solid #f1f5f9; margin-top: 0.5rem; padding-top: 0.75rem;">
                                    <span style="font-size: 0.75rem; color: #6b7280;">
                                        <i class="bi bi-arrow-left-right me-1"></i> {{ __('ui.exchange_rate') }}
                                    </span>
                                    <span style="font-size: 0.8rem; color: #6b7280; font-weight: 600;">
                                        1 USD = {{ number_format($exchangeRate, 2) }} {{ $currencyCode }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── RIGHT COLUMN: Material Status ─── --}}
            <div class="col-lg-4 col-md-12">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-box-seam"></i> Material Status
                        </h6>
                        <span class="badge bg-primary ms-2" style="font-weight: 700;">{{ $productionOrder->materials->count() }}</span>
                    </div>
                    <div class="card-body-modern p-0">
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table-modern">
                                <thead>
                                <tr>
                                    <th>{{ __('ui.material') }}</th>
                                    <th class="text-end">Required</th>
                                    <th class="text-end">{{ __('ui.available') }}</th>
                                    <th class="text-center">{{ __('ui.status') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($productionOrder->materials as $material)
                                    @php
                                        $isAvailable = $material->shortage_quantity == 0;
                                        $shortagePercent = $material->required_quantity > 0
                                            ? ($material->shortage_quantity / $material->required_quantity) * 100
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a;">
                                                {{ $material->product->name ?? 'N/A' }}
                                            </div>
                                            <div style="font-size: 0.6rem; color: #94a3b8;">
                                                {{ $material->unit }}
                                            </div>
                                        </td>
                                        <td class="text-end" style="font-weight: 600; color: #0f172a;">
                                            {{ number_format($material->required_quantity, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="{{ $isAvailable ? 'text-success' : 'text-danger' }}" style="font-weight: 600;">
                                                {{ number_format($material->available_quantity, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($isAvailable)
                                                <span class="badge-available">
                                                    <i class="bi bi-check-circle me-1"></i> {{ __('ui.available') }}
                                                </span>
                                            @else
                                                <span class="badge-shortage" title="Shortage: {{ number_format($material->shortage_quantity, 2) }} {{ $material->unit }}">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    {{ number_format($shortagePercent, 0) }}% short
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inboxes" style="display: block; font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                            No materials defined
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ─── MATERIAL BREAKDOWN (Below the grid) ─── --}}
        @if(!empty($materialDetails) && count($materialDetails) > 0)
            <div class="card-modern mt-4">
                <div class="card-header-modern">
                    <h6>
                        <i class="bi bi-box-seam me-2" style="color: #4f46e5;"></i>
                        {{ ($hasActualConsumption ?? false) ? 'Material Requirement & Actual FIFO Cost' : 'Material Breakdown' }}
                    </h6>
                    <span class="badge bg-primary ms-2" style="font-weight: 700;">{{ count($materialDetails) }}</span>
                </div>
                <div class="card-body-modern p-0">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                            <tr>
                                <th>{{ __('ui.material') }}</th>
                                <th class="text-end">{{ __('ui.quantity') }}</th>
                                <th class="text-end">{{ __('ui.unit') }}</th>
                                <th class="text-end">Cost/Unit (USD)</th>
                                <th class="text-end">{{ __('ui.total_cost_usd') }}</th>
                                <th class="text-end">Total Cost ({{ $currencyCode }})</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($materialDetails as $material)
                                <tr>
                                    <td>{{ $material['material_name'] }}</td>
                                    <td class="text-end">{{ number_format($material['required_quantity'], 4) }}</td>
                                    <td class="text-end">{{ $material['unit'] ?? 'unit' }}</td>
                                    <td class="text-end">${{ number_format($material['cost_per_unit_usd'], 4) }}</td>
                                    <td class="text-end">${{ number_format($material['total_cost_usd'], 2) }}</td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($material['total_cost_usd'] * $exchangeRate, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">
                                    {{ ($hasActualConsumption ?? false) ? 'Actual FIFO Material Cost' : 'Total Material Cost' }}
                                </td>
                                <td class="text-end fw-bold">${{ number_format($totalMaterialCostUsd ?? 0, 2) }}</td>
                                <td class="text-end fw-bold">{{ $currencySymbol }}{{ number_format($totalMaterialCostInCurrency ?? 0, 2) }}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($productionVariance) && ($productionVariance['has_actual'] ?? false))
            <div class="card-modern mt-4">
                <div class="card-header-modern">
                    <h6>
                        <i class="bi bi-clipboard-data me-2" style="color:#4f46e5;"></i>
                        Planned vs Actual Production Variance
                    </h6>
                    <span class="badge bg-light text-dark border">Actual FIFO consumption</span>
                </div>
                <div class="card-body-modern">
                    @php($output = $productionVariance['output'] ?? [])
                    <div class="row g-3 mb-4">
                        <div class="col-md-2 col-6"><div class="text-muted small">Planned</div><strong>{{ number_format((float) ($output['planned_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Manufactured</div><strong>{{ number_format((float) ($output['manufactured_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Good</div><strong class="text-success">{{ number_format((float) ($output['good_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Rejected</div><strong class="text-warning">{{ number_format((float) ($output['rejected_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Output Variance</div><strong>{{ number_format((float) ($output['manufactured_variance_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Yield</div><strong>{{ number_format((float) ($output['yield_percentage'] ?? 0), 2) }}%</strong></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Planned</th>
                                    <th class="text-end">Actual</th>
                                    <th class="text-end">Variance</th>
                                    <th class="text-end">Actual Waste</th>
                                    <th class="text-end">Cost Variance (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionVariance['materials'] ?? [] as $row)
                                    <tr>
                                        <td>{{ $row['material_name'] }}</td>
                                        <td class="text-end">{{ number_format((float) $row['planned_quantity'], 4) }} {{ $row['unit'] }}</td>
                                        <td class="text-end">{{ number_format((float) $row['actual_quantity'], 4) }} {{ $row['unit'] }}</td>
                                        <td class="text-end {{ $row['variance_quantity'] > 0 ? 'text-danger' : ($row['variance_quantity'] < 0 ? 'text-success' : '') }}">
                                            {{ $row['variance_quantity'] > 0 ? '+' : '' }}{{ number_format((float) $row['variance_quantity'], 4) }}
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row['actual_wastage_quantity'], 4) }}</td>
                                        <td class="text-end {{ $row['cost_variance_usd'] > 0 ? 'text-danger' : ($row['cost_variance_usd'] < 0 ? 'text-success' : '') }}">
                                            {{ $row['cost_variance_usd'] > 0 ? '+' : '' }}${{ number_format((float) $row['cost_variance_usd'], 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Material Cost Variance</td>
                                    <td class="text-end fw-bold">${{ number_format((float) data_get($productionVariance, 'summary.material_cost_variance_usd', 0), 4) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── CHECK FOR ERRORS AND SHOW ALERT ───
            @if(session('error'))
            var errorMessage = {!! json_encode(session('error')) !!};
            alert('❌ ' + errorMessage);

            var errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            @endif

            // ─── CHECK FOR SUCCESS AND SHOW ALERT ───
            @if(session('success'))
            var successMessage = {!! json_encode(session('success')) !!};
            alert('✅ ' + successMessage);

            var successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            @endif

            @if(isset($errors) && $errors->has('quantity_planned'))
            const startModalElement = document.getElementById('startProductionModal');
            if (startModalElement && window.bootstrap) {
                new bootstrap.Modal(startModalElement).show();
            }
            @endif

            @if(isset($errors) && (
                $errors->has('quantity_manufactured')
                || $errors->has('quantity_produced')
                || $errors->has('quantity_rejected')
                || $errors->has('materials')
                || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'materials.'))
            ))
            const completionModalElement = document.getElementById('completeProductionModal');
            if (completionModalElement && window.bootstrap) {
                new bootstrap.Modal(completionModalElement).show();
            }
            @endif

            // ─── LIVE BOM VS ACTUAL MATERIAL VARIANCE ───
            const syncCompletionSummary = function() {
                let over = 0;
                let below = 0;
                let onPlan = 0;
                const epsilon = 0.000001;

                document.querySelectorAll('.actual-consumption-input').forEach(function(input) {
                    const planned = Number(input.dataset.planned || 0);
                    const actual = Number(input.value);
                    if (!Number.isFinite(actual)) {
                        return;
                    }

                    const difference = actual - planned;
                    if (difference > epsilon) {
                        over++;
                    } else if (difference < -epsilon) {
                        below++;
                    } else {
                        onPlan++;
                    }
                });

                const overTarget = document.getElementById('completionOverCount');
                const belowTarget = document.getElementById('completionBelowCount');
                const onPlanTarget = document.getElementById('completionOnPlanCount');

                if (overTarget) overTarget.textContent = String(over);
                if (belowTarget) belowTarget.textContent = String(below);
                if (onPlanTarget) onPlanTarget.textContent = String(onPlan);
            };

            const syncMaterialVariance = function(input) {
                const target = document.getElementById(input.dataset.varianceTarget);
                const status = document.getElementById(input.dataset.varianceStatusTarget);
                const row = input.closest('[data-material-row]');

                if (!target || !status) {
                    return;
                }

                const planned = Number(input.dataset.planned || 0);
                const actual = Number(input.value);
                const unit = input.dataset.unit || '';

                target.classList.remove('text-danger', 'text-success', 'text-muted');
                if (row) {
                    row.classList.remove('is-over-plan', 'is-below-plan', 'is-on-plan');
                }

                if (!Number.isFinite(actual)) {
                    target.textContent = '—';
                    target.classList.add('text-muted');
                    status.textContent = 'Actual − planned';
                    syncCompletionSummary();
                    return;
                }

                const difference = actual - planned;
                const epsilon = 0.000001;
                const sign = difference > epsilon ? '+' : '';

                target.textContent = sign + difference.toFixed(4) + (unit ? ' ' + unit : '');

                if (difference > epsilon) {
                    target.classList.add('text-danger');
                    status.textContent = 'Over BOM plan';
                    if (row) row.classList.add('is-over-plan');
                } else if (difference < -epsilon) {
                    target.classList.add('text-success');
                    status.textContent = 'Below BOM plan';
                    if (row) row.classList.add('is-below-plan');
                } else {
                    target.classList.add('text-muted');
                    status.textContent = 'On BOM plan';
                    if (row) row.classList.add('is-on-plan');
                }

                syncCompletionSummary();
            };

            const syncAutomaticRollConsumption = function() {
                const manufacturedInput = document.getElementById('quantity_manufactured');
                const manufactured = Number(manufacturedInput ? manufacturedInput.value : 0);

                document.querySelectorAll('.auto-roll-consumption').forEach(function(input) {
                    const planned = Number(input.dataset.planned || 0);
                    const plannedRun = Number(input.dataset.plannedRun || 0);
                    const calculated = plannedRun > 0 && Number.isFinite(manufactured)
                        ? planned * (manufactured / plannedRun)
                        : planned;

                    input.value = Math.max(calculated, 0).toFixed(6);
                    const display = input.parentElement.querySelector('.auto-roll-consumption-display');
                    if (display) {
                        display.textContent = Number(input.value).toFixed(4) + ' ' + (input.dataset.unit || '');
                    }
                    syncMaterialVariance(input);
                });

                document.querySelectorAll('.auto-roll-wastage').forEach(function(input) {
                    const plannedWaste = Number(input.dataset.plannedWastage || 0);
                    const plannedRun = Number(input.dataset.plannedRun || 0);
                    const calculatedWaste = plannedRun > 0 && Number.isFinite(manufactured)
                        ? plannedWaste * (manufactured / plannedRun)
                        : plannedWaste;

                    input.value = Math.max(calculatedWaste, 0).toFixed(6);
                    const display = input.parentElement.querySelector('.auto-roll-wastage-display');
                    if (display) {
                        display.textContent = Number(input.value).toFixed(4) + ' kg';
                    }
                });
            };

            document.querySelectorAll('.actual-consumption-input').forEach(function(input) {
                if (input.dataset.autoRoll !== '1') {
                    input.addEventListener('input', function() {
                        syncMaterialVariance(input);
                    });
                }
                syncMaterialVariance(input);
            });

            syncAutomaticRollConsumption();

            document.querySelectorAll('.use-bom-plan').forEach(function(button) {
                button.addEventListener('click', function() {
                    const row = button.closest('[data-material-row]');
                    const input = row ? row.querySelector('.actual-consumption-input') : null;
                    if (!input) return;

                    input.value = input.dataset.planned || '0';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.focus();
                });
            });

            const syncOutputCheck = function() {
                const manufacturedInput = document.getElementById('quantity_manufactured');
                const goodInput = document.getElementById('quantity_produced');
                const rejectedInput = document.getElementById('quantity_rejected');
                const check = document.getElementById('completionOutputCheck');
                const value = document.getElementById('completionOutputCheckValue');

                if (!manufacturedInput || !goodInput || !rejectedInput || !check || !value) {
                    return true;
                }

                const manufactured = Number(manufacturedInput.value);
                const good = Number(goodInput.value);
                const rejected = Number(rejectedInput.value);
                const validNumbers = [manufactured, good, rejected].every(Number.isFinite);
                const balanced = validNumbers && Math.abs(manufactured - (good + rejected)) <= 0.01;

                check.classList.remove('is-valid', 'is-invalid');
                check.classList.add(balanced ? 'is-valid' : 'is-invalid');
                value.textContent = balanced
                    ? 'Balanced'
                    : 'Difference: ' + (validNumbers ? (manufactured - good - rejected).toFixed(2) : '—');

                return balanced;
            };

            ['quantity_manufactured', 'quantity_produced', 'quantity_rejected'].forEach(function(id) {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('input', function() {
                        syncOutputCheck();
                        if (id === 'quantity_manufactured') {
                            syncAutomaticRollConsumption();
                        }
                    });
                }
            });
            syncOutputCheck();
            syncAutomaticRollConsumption();

            const productionCompletionForm = document.getElementById('productionCompletionForm');
            if (productionCompletionForm) {
                productionCompletionForm.addEventListener('submit', function(event) {
                    if (!syncOutputCheck()) {
                        event.preventDefault();
                        const manufacturedInput = document.getElementById('quantity_manufactured');
                        if (manufacturedInput) manufacturedInput.focus();
                    }
                });
            }

            // ─── OPTIONAL PHYSICAL REEL DECLARATION ───
            document.querySelectorAll('.reel-selection-toggle').forEach(function(toggle) {
                const panel = document.getElementById(toggle.dataset.target);
                const syncPanel = function() {
                    if (panel) {
                        panel.style.display = toggle.checked ? '' : 'none';
                    }
                };

                toggle.addEventListener('change', syncPanel);
                syncPanel();
            });

            // ─── CONFIRM DIALOG FOR START PRODUCTION ───
            const startForm = document.getElementById('startProductionForm');
            if (startForm) {
                startForm.addEventListener('submit', function(e) {
                    const confirmMessage = '⚠️ Start Production?\n\nThis will calculate and consume raw material using the Planned Production Quantity you entered. The planned quantity can be above or below the customer order but must be supported by current stock.\n\nYou will enter the real finished quantity when production ends. Continue?';

                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // ─── AUTO-REFRESH for pending/in-progress orders ───
            @if($productionOrder->status === 'pending' || $productionOrder->status === 'in_progress')
            let refreshInterval = setInterval(function() {
                if (!document.hidden) {
                    fetch(window.location.href + '?check_status=1', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status_changed) {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.log('Status check failed:', error);
                        });
                }
            }, 30000);

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    clearInterval(refreshInterval);
                } else {
                    refreshInterval = setInterval(function() {
                        if (!document.hidden) {
                            fetch(window.location.href + '?check_status=1', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status_changed) {
                                        location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.log('Status check failed:', error);
                                });
                        }
                    }, 30000);
                }
            });
            @endif
        });
    </script>
@endsection
