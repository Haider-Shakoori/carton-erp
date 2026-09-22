{{-- resources/views/admin/dashboard/index.blade.php --}}

@extends('layouts.admin.base')

@section('title', __('ui.dashboard'))

@section('css')
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/apexcharts/apexcharts-7.1.0.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <style>
        /* ─── ROOT VARIABLES ─── */
        :root {
            --db-primary: #4f46e5;
            --db-primary-dark: #4338ca;
            --db-primary-light: #818cf8;
            --db-success: #10b981;
            --db-danger: #ef4444;
            --db-warning: #f59e0b;
            --db-info: #3b82f6;
            --db-purple: #8b5cf6;
            --db-pink: #ec4899;
            --db-blue: #2563eb;
            --db-teal: #14b8a6;
            --db-orange: #f97316;
            --db-rose: #f43f5e;
            --db-cyan: #06b6d4;
            --db-gray-50: #f8fafc;
            --db-gray-100: #f1f5f9;
            --db-gray-200: #e2e8f0;
            --db-gray-300: #cbd5e1;
            --db-gray-400: #94a3b8;
            --db-gray-500: #64748b;
            --db-gray-600: #475569;
            --db-gray-700: #334155;
            --db-gray-800: #1e293b;
            --db-gray-900: #0f172a;
            --db-radius: 16px;
            --db-radius-sm: 10px;
            --db-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.06);
            --db-shadow-hover: 0 1px 3px rgba(0,0,0,0.04), 0 12px 48px rgba(0,0,0,0.1);
        }

        /* ─── PAGE HEADER ─── */
        .db-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .db-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--db-gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .db-header h1 .accent {
            background: linear-gradient(135deg, var(--db-primary), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .db-header .subtitle {
            color: var(--db-gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .db-header .db-controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .db-header .db-controls .timeframe-select {
            padding: 0.45rem 1rem;
            border-radius: var(--db-radius-sm);
            border: 1.5px solid var(--db-gray-200);
            background: white;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--db-gray-700);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .db-header .db-controls .timeframe-select:focus {
            border-color: var(--db-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }
        .db-header .db-controls .btn-refresh {
            padding: 0.45rem 1rem;
            border-radius: var(--db-radius-sm);
            border: 1.5px solid var(--db-gray-200);
            background: white;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--db-gray-600);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .db-header .db-controls .btn-refresh:hover {
            background: var(--db-gray-50);
            border-color: var(--db-gray-300);
        }
        .db-header .db-controls .btn-refresh.spinning i {
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ─── STATS GRID ─── */
        .db-stats-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1400px) {
            .db-stats-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 992px) {
            .db-stats-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .db-stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .db-stats-grid { grid-template-columns: 1fr; }
        }

        /* ─── STAT CARD ─── */
        .db-stat-card {
            background: white;
            border-radius: var(--db-radius);
            padding: 1rem 1rem 0.85rem 1rem;
            box-shadow: var(--db-shadow);
            border: 1px solid var(--db-gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .db-stat-card:hover {
            box-shadow: var(--db-shadow-hover);
            transform: translateY(-3px);
        }
        .db-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .db-stat-card.primary::before { background: linear-gradient(90deg, var(--db-primary), var(--db-primary-light)); }
        .db-stat-card.success::before { background: linear-gradient(90deg, var(--db-success), #34d399); }
        .db-stat-card.danger::before { background: linear-gradient(90deg, var(--db-danger), #f87171); }
        .db-stat-card.warning::before { background: linear-gradient(90deg, var(--db-warning), #fbbf24); }
        .db-stat-card.info::before { background: linear-gradient(90deg, var(--db-info), #60a5fa); }
        .db-stat-card.purple::before { background: linear-gradient(90deg, var(--db-purple), #a78bfa); }
        .db-stat-card.blue::before { background: linear-gradient(90deg, var(--db-blue), #60a5fa); }
        .db-stat-card.pink::before { background: linear-gradient(90deg, var(--db-pink), #f472b6); }
        .db-stat-card.teal::before { background: linear-gradient(90deg, var(--db-teal), #2dd4bf); }
        .db-stat-card.orange::before { background: linear-gradient(90deg, var(--db-orange), #fb923c); }
        .db-stat-card.rose::before { background: linear-gradient(90deg, var(--db-rose), #fb7185); }
        .db-stat-card.cyan::before { background: linear-gradient(90deg, var(--db-cyan), #22d3ee); }

        /* ─── STAT CARD HEADER ─── */
        .db-stat-card .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
        }
        .db-stat-card .stat-title {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--db-gray-500);
        }
        .db-stat-card .stat-icon {
            width: 28px;
            height: 28px;
            border-radius: var(--db-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .db-stat-card:hover .stat-icon {
            transform: scale(1.05);
        }
        .db-stat-card .stat-icon.primary { background: #eef2ff; color: var(--db-primary); }
        .db-stat-card .stat-icon.success { background: #d1fae5; color: var(--db-success); }
        .db-stat-card .stat-icon.danger { background: #fecaca; color: var(--db-danger); }
        .db-stat-card .stat-icon.warning { background: #fef3c7; color: var(--db-warning); }
        .db-stat-card .stat-icon.info { background: #dbeafe; color: var(--db-info); }
        .db-stat-card .stat-icon.purple { background: #ede9fe; color: var(--db-purple); }
        .db-stat-card .stat-icon.blue { background: #dbeafe; color: var(--db-blue); }
        .db-stat-card .stat-icon.pink { background: #fce7f3; color: var(--db-pink); }
        .db-stat-card .stat-icon.teal { background: #ccfbf1; color: var(--db-teal); }
        .db-stat-card .stat-icon.orange { background: #fef3c7; color: var(--db-orange); }
        .db-stat-card .stat-icon.rose { background: #ffe4e6; color: var(--db-rose); }
        .db-stat-card .stat-icon.cyan { background: #cffafe; color: var(--db-cyan); }

        /* ─── STAT AMOUNTS ─── */
        .db-stat-card .stat-amounts {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            margin-top: 0.2rem;
        }

        /* ─── STAT CURRENCY ITEM ─── */
        .db-stat-card .stat-currency-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.15rem 0.3rem;
            border-radius: 4px;
            transition: background 0.2s ease;
        }
        .db-stat-card .stat-currency-item:hover {
            background: var(--db-gray-50);
        }

        .db-stat-card .stat-currency-item .currency-info {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .db-stat-card .stat-currency-item .currency-symbol {
            font-weight: 700;
            font-size: 0.75rem;
        }
        .db-stat-card .stat-currency-item .currency-symbol.usd {
            color: #059669;
        }
        .db-stat-card .stat-currency-item .currency-symbol.afn {
            color: #d97706;
        }
        .db-stat-card .stat-currency-item .currency-code {
            font-weight: 600;
            font-size: 0.6rem;
            color: var(--db-gray-400);
        }

        .db-stat-card .stat-currency-item .currency-amount {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--db-gray-800);
        }
        .db-stat-card .stat-currency-item .currency-amount.positive {
            color: #065f46;
        }
        .db-stat-card .stat-currency-item .currency-amount.negative {
            color: #991b1b;
        }

        /* ─── STAT CHANGE ─── */
        .db-stat-card .stat-change {
            font-size: 0.55rem;
            font-weight: 700;
            padding: 0.05rem 0.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.15rem;
            margin-top: 0.3rem;
        }
        .db-stat-card .stat-change.positive { background: #d1fae5; color: #065f46; }
        .db-stat-card .stat-change.negative { background: #fecaca; color: #991b1b; }
        .db-stat-card .stat-change.neutral { background: var(--db-gray-100); color: var(--db-gray-500); }
        .db-stat-card .stat-change i { font-size: 0.5rem; }

        /* ─── STAT PROGRESS ─── */
        .db-stat-card .stat-progress {
            width: 100%;
            height: 2px;
            background: var(--db-gray-100);
            border-radius: 10px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .db-stat-card .stat-progress .stat-progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }
        .db-stat-card .stat-progress .stat-progress-bar.primary { background: var(--db-primary); }
        .db-stat-card .stat-progress .stat-progress-bar.success { background: var(--db-success); }
        .db-stat-card .stat-progress .stat-progress-bar.danger { background: var(--db-danger); }
        .db-stat-card .stat-progress .stat-progress-bar.warning { background: var(--db-warning); }
        .db-stat-card .stat-progress .stat-progress-bar.purple { background: var(--db-purple); }
        .db-stat-card .stat-progress .stat-progress-bar.teal { background: var(--db-teal); }

        /* ─── CURRENCY BALANCES ─── */
        .currency-balances-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1200px) {
            .currency-balances-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .currency-balances-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .currency-balances-grid { grid-template-columns: 1fr; }
        }

        .currency-balance-card {
            background: white;
            border-radius: var(--db-radius);
            padding: 0.85rem 1rem;
            box-shadow: var(--db-shadow);
            border: 1px solid var(--db-gray-200);
            transition: all 0.3s ease;
        }
        .currency-balance-card:hover {
            box-shadow: var(--db-shadow-hover);
            transform: translateY(-2px);
        }

        .currency-balance-card .balance-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding-bottom: 0.5rem;
            border-bottom: 1.5px solid var(--db-gray-100);
            margin-bottom: 0.5rem;
        }

        .currency-balance-card .balance-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--db-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .currency-balance-card .balance-icon.customer { background: #dbeafe; color: #1e40af; }
        .currency-balance-card .balance-icon.supplier { background: #fef3c7; color: #92400e; }
        .currency-balance-card .balance-icon.agent { background: #ede9fe; color: #7c3aed; }
        .currency-balance-card .balance-icon.expense { background: #fce7f3; color: #db2777; }
        .currency-balance-card .balance-icon.saraf { background: #d1fae5; color: #065f46; }

        .currency-balance-card .balance-title .label {
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--db-gray-800);
            display: block;
        }
        .currency-balance-card .balance-title .description {
            font-size: 0.55rem;
            color: var(--db-gray-400);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .currency-balance-card .balance-body {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            max-height: 180px;
            overflow-y: auto;
        }
        .currency-balance-card .balance-body::-webkit-scrollbar {
            width: 3px;
        }
        .currency-balance-card .balance-body::-webkit-scrollbar-track {
            background: var(--db-gray-50);
            border-radius: 10px;
        }
        .currency-balance-card .balance-body::-webkit-scrollbar-thumb {
            background: var(--db-gray-300);
            border-radius: 10px;
        }

        .currency-balance-card .balance-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.3rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .currency-balance-card .balance-item:hover {
            background: var(--db-gray-50);
        }
        .currency-balance-card .balance-item.positive {
            border-left-color: #10b981;
        }
        .currency-balance-card .balance-item.negative {
            border-left-color: #ef4444;
        }
        .currency-balance-card .balance-item.zero {
            border-left-color: #94a3b8;
        }

        .currency-balance-card .balance-item .balance-currency {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            min-width: 80px;
        }
        .currency-balance-card .balance-item .balance-currency .currency-symbol {
            font-weight: 700;
            font-size: 0.75rem;
        }
        .currency-balance-card .balance-item .balance-currency .currency-symbol.usd {
            color: #059669;
        }
        .currency-balance-card .balance-item .balance-currency .currency-symbol.afn {
            color: #d97706;
        }
        .currency-balance-card .balance-item .balance-currency .currency-code {
            font-weight: 600;
            font-size: 0.65rem;
            color: var(--db-gray-500);
        }

        .currency-balance-card .balance-item .balance-amount {
            font-weight: 700;
            font-size: 0.85rem;
            flex: 1;
            text-align: right;
            padding: 0 0.5rem;
        }
        .currency-balance-card .balance-item .balance-amount.positive {
            color: #065f46;
        }
        .currency-balance-card .balance-item .balance-amount.negative {
            color: #991b1b;
        }
        .currency-balance-card .balance-item .balance-amount.zero {
            color: var(--db-gray-400);
        }

        .currency-balance-card .balance-item .balance-status {
            font-size: 0.5rem;
            font-weight: 700;
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .currency-balance-card .balance-item .balance-status.positive {
            background: #d1fae5;
            color: #065f46;
        }
        .currency-balance-card .balance-item .balance-status.negative {
            background: #fecaca;
            color: #991b1b;
        }
        .currency-balance-card .balance-item .balance-status.zero {
            background: var(--db-gray-100);
            color: var(--db-gray-500);
        }

        .currency-balance-card .balance-empty {
            color: var(--db-gray-400);
            font-size: 0.7rem;
            padding: 0.5rem 0;
            text-align: center;
        }
        .currency-balance-card .balance-empty i {
            font-size: 0.9rem;
            display: inline-block;
            margin-right: 0.25rem;
        }

        /* ─── CHARTS ─── */
        .db-charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1200px) {
            .db-charts-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .db-charts-grid { grid-template-columns: 1fr; }
        }

        .db-chart-card {
            background: white;
            border-radius: var(--db-radius);
            padding: 1.25rem 1.25rem 0.75rem 1.25rem;
            box-shadow: var(--db-shadow);
            border: 1px solid var(--db-gray-200);
            transition: all 0.3s ease;
        }
        .db-chart-card:hover {
            box-shadow: var(--db-shadow-hover);
        }
        .db-chart-card .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .db-chart-card .chart-header h6 {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--db-gray-700);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .db-chart-card .chart-header .chart-controls {
            display: flex;
            gap: 0.25rem;
        }
        .db-chart-card .chart-header .chart-controls .chart-period-btn {
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            border: none;
            font-size: 0.55rem;
            font-weight: 600;
            color: var(--db-gray-400);
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .db-chart-card .chart-header .chart-controls .chart-period-btn:hover {
            color: var(--db-gray-600);
            background: var(--db-gray-50);
        }
        .db-chart-card .chart-header .chart-controls .chart-period-btn.active {
            color: var(--db-primary);
            background: #eef2ff;
        }
        .db-chart-card .chart-wrapper {
            position: relative;
        }
        #mainChart, #secondaryChart {
            width: 100%;
            min-height: 250px;
        }

        /* ─── RECENT ACTIVITY & ALERTS ─── */
        .db-secondary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 992px) {
            .db-secondary-grid { grid-template-columns: 1fr; }
        }

        .db-activity-card, .db-alerts-card {
            background: white;
            border-radius: var(--db-radius);
            padding: 1.25rem;
            box-shadow: var(--db-shadow);
            border: 1px solid var(--db-gray-200);
            transition: all 0.3s ease;
        }
        .db-activity-card:hover, .db-alerts-card:hover {
            box-shadow: var(--db-shadow-hover);
        }

        .db-activity-card .activity-header,
        .db-alerts-card .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .db-activity-card .activity-header h6,
        .db-alerts-card .alerts-header h6 {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--db-gray-700);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .db-activity-card .activity-header .activity-filter {
            display: flex;
            gap: 0.25rem;
        }
        .db-activity-card .activity-header .activity-filter .filter-btn {
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            border: none;
            font-size: 0.55rem;
            font-weight: 600;
            color: var(--db-gray-400);
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .db-activity-card .activity-header .activity-filter .filter-btn:hover {
            color: var(--db-gray-600);
            background: var(--db-gray-50);
        }
        .db-activity-card .activity-header .activity-filter .filter-btn.active {
            color: var(--db-primary);
            background: #eef2ff;
        }
        .db-alerts-card .alerts-header .alert-count {
            font-size: 0.55rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            background: #fecaca;
            color: #991b1b;
        }

        .db-activity-list {
            max-height: 350px;
            overflow-y: auto;
        }
        .db-activity-list::-webkit-scrollbar {
            width: 4px;
        }
        .db-activity-list::-webkit-scrollbar-track {
            background: var(--db-gray-100);
            border-radius: 10px;
        }
        .db-activity-list::-webkit-scrollbar-thumb {
            background: var(--db-gray-300);
            border-radius: 10px;
        }

        .db-activity-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--db-gray-100);
            animation: fadeInUp 0.3s ease forwards;
            opacity: 0;
        }
        .db-activity-item:last-child { border-bottom: none; }
        .db-activity-item .activity-icon {
            width: 30px;
            height: 30px;
            border-radius: var(--db-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        .db-activity-item .activity-icon.green { background: #d1fae5; color: #065f46; }
        .db-activity-item .activity-icon.blue { background: #dbeafe; color: #1e40af; }
        .db-activity-item .activity-icon.yellow { background: #fef3c7; color: #92400e; }
        .db-activity-item .activity-icon.red { background: #fecaca; color: #991b1b; }
        .db-activity-item .activity-icon.purple { background: #ede9fe; color: #7c3aed; }
        .db-activity-item .activity-icon.gray { background: var(--db-gray-100); color: var(--db-gray-500); }

        .db-activity-item .activity-content { flex: 1; min-width: 0; }
        .db-activity-item .activity-content .title {
            font-weight: 600;
            color: var(--db-gray-800);
            font-size: 0.7rem;
        }
        .db-activity-item .activity-content .description {
            font-size: 0.6rem;
            color: var(--db-gray-500);
            margin-top: 0.05rem;
        }
        .db-activity-item .activity-content .description .amount { font-weight: 700; }
        .db-activity-item .activity-time {
            font-size: 0.55rem;
            color: var(--db-gray-400);
            white-space: nowrap;
        }
        .db-activity-item .activity-status {
            font-size: 0.45rem;
            font-weight: 700;
            padding: 0.05rem 0.35rem;
            border-radius: 10px;
            text-transform: uppercase;
        }
        .db-activity-item .activity-status.draft { background: var(--db-gray-100); color: var(--db-gray-500); }
        .db-activity-item .activity-status.confirmed { background: #dbeafe; color: #1e40af; }
        .db-activity-item .activity-status.shipped { background: #fef3c7; color: #92400e; }
        .db-activity-item .activity-status.delivered { background: #d1fae5; color: #065f46; }
        .db-activity-item .activity-status.arrived { background: #d1fae5; color: #065f46; }
        .db-activity-item .activity-status.shipping { background: #ede9fe; color: #7c3aed; }
        .db-activity-item .activity-status.processed { background: #d1fae5; color: #065f46; }
        .db-activity-item .activity-status.rejected { background: #fecaca; color: #991b1b; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .db-activity-item:nth-child(1) { animation-delay: 0.05s; }
        .db-activity-item:nth-child(2) { animation-delay: 0.10s; }
        .db-activity-item:nth-child(3) { animation-delay: 0.15s; }
        .db-activity-item:nth-child(4) { animation-delay: 0.20s; }
        .db-activity-item:nth-child(5) { animation-delay: 0.25s; }
        .db-activity-item:nth-child(6) { animation-delay: 0.30s; }
        .db-activity-item:nth-child(7) { animation-delay: 0.35s; }
        .db-activity-item:nth-child(8) { animation-delay: 0.40s; }
        .db-activity-item:nth-child(9) { animation-delay: 0.45s; }
        .db-activity-item:nth-child(10) { animation-delay: 0.50s; }

        /* ─── ALERTS ─── */
        .db-alert-item {
            display: flex;
            gap: 0.6rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid var(--db-gray-100);
            align-items: flex-start;
        }
        .db-alert-item:last-child { border-bottom: none; }
        .db-alert-item .alert-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .db-alert-item .alert-icon.warning { background: #fef3c7; color: #92400e; }
        .db-alert-item .alert-icon.danger { background: #fecaca; color: #991b1b; }
        .db-alert-item .alert-icon.info { background: #dbeafe; color: #1e40af; }
        .db-alert-item .alert-icon.success { background: #d1fae5; color: #065f46; }
        .db-alert-item .alert-content { flex: 1; }
        .db-alert-item .alert-content .alert-title {
            font-weight: 600;
            color: var(--db-gray-800);
            font-size: 0.7rem;
        }
        .db-alert-item .alert-content .alert-message {
            font-size: 0.65rem;
            color: var(--db-gray-500);
        }
        .db-alert-item .alert-content .alert-link {
            font-size: 0.6rem;
            color: var(--db-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .db-alert-item .alert-content .alert-link:hover { text-decoration: underline; }

        /* ─── EMPTY STATES ─── */
        .db-empty {
            text-align: center;
            padding: 1.5rem 1rem;
            color: var(--db-gray-400);
        }
        .db-empty i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 0.35rem;
            color: var(--db-gray-300);
        }
        .db-empty .title {
            font-weight: 600;
            color: var(--db-gray-600);
            font-size: 0.8rem;
        }
        .db-empty .sub-text {
            font-size: 0.65rem;
            color: var(--db-gray-400);
        }

        /* ─── LOADING OVERLAY ─── */
        .db-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .db-loading-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
        .db-loading-overlay .loader { text-align: center; }
        .db-loading-overlay .loader .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--db-gray-200);
            border-top-color: var(--db-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 0.75rem;
        }
        .db-loading-overlay .loader p {
            color: var(--db-gray-500);
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* ─── CHART LOADING ─── */
        .chart-loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.7);
            z-index: 10;
            border-radius: var(--db-radius-sm);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .chart-loading-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
        .chart-loading-overlay .spinner {
            width: 28px;
            height: 28px;
            border: 3px solid var(--db-gray-200);
            border-top-color: var(--db-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @media (max-width: 768px) {
            .db-header { flex-direction: column; }
            .db-header .db-controls { width: 100%; }
            .db-header .db-controls .timeframe-select { flex: 1; }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ─── LOADING OVERLAY ─── --}}
        <div class="db-loading-overlay" id="loadingOverlay">
            <div class="loader">
                <div class="spinner"></div>
                <p>{{ __('ui.loading_dashboard_data') }}</p>
            </div>
        </div>

        {{-- ─── HEADER ─── --}}
        <div class="db-header">
            <div>
                <h1>
                    <i class="bi bi-grid-3x3-gap-fill" style="color: var(--db-primary);"></i>
                    <span class="accent">{{ __('ui.dashboard') }}</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-clock-history me-1"></i>
                    {{ __('ui.dashboard_subtitle') }}
                    <span class="text-muted" id="lastUpdated"></span>
                </p>
                @if(($businessUnitModeEnabled ?? false) && $activeBusinessUnit)
                    <div class="mt-2">
                        <span class="badge rounded-pill text-bg-light border">
                            <i class="bi {{ $activeBusinessUnit->icon ?: 'bi-building' }} me-1"></i>
                            {{ $activeBusinessUnit->name }} workspace
                        </span>
                        <span class="small text-muted ms-2">Customers and employees are shared.</span>
                    </div>
                @endif
            </div>

            <div class="db-controls">
                <select class="timeframe-select" id="timeframeSelect">
                    <option value="today">{{ __('ui.today') }}</option>
                    <option value="week">{{ __('ui.this_week') }}</option>
                    <option value="month" selected>{{ __('ui.this_month') }}</option>
                    <option value="quarter">{{ __('ui.this_quarter') }}</option>
                    <option value="year">{{ __('ui.this_year') }}</option>
                </select>

                <button class="btn-refresh" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>{{ __('ui.refresh') }}</span>
                </button>
            </div>
        </div>

        {{-- ─── STATS GRID ─── --}}
        <div class="db-stats-grid" id="statsGrid">
            <!-- Stats will be populated by JavaScript -->
        </div>

        {{-- ─── CURRENCY BALANCES ─── --}}
        <div id="currencyBalancesContainer">
            <div class="currency-balances-grid">
                @foreach ([
                    'customer' => ['label' => __('ui.customers'), 'icon' => 'bi-people', 'color' => 'customer', 'desc' => __('ui.receivables')],
                    'supplier' => ['label' => __('ui.suppliers'), 'icon' => 'bi-truck', 'color' => 'supplier', 'desc' => __('ui.payables')],
                    'agent' => ['label' => __('ui.agents'), 'icon' => 'bi-person-badge', 'color' => 'agent', 'desc' => __('ui.commissions')],
                    'expense' => ['label' => __('ui.expenses'), 'icon' => 'bi-receipt', 'color' => 'expense', 'desc' => __('ui.expense_accounts')],
                    'saraf' => ['label' => __('ui.saraf'), 'icon' => 'bi-currency-exchange', 'color' => 'saraf', 'desc' => __('ui.exchange_balances')]
                ] as $key => $type)
                    <div class="currency-balance-card">
                        <div class="balance-header">
                            <div class="balance-icon {{ $type['color'] }}">
                                <i class="bi {{ $type['icon'] }}"></i>
                            </div>
                            <div class="balance-title">
                                <span class="label">{{ $type['label'] }}</span>
                                <span class="description">{{ $type['desc'] }}</span>
                            </div>
                        </div>
                        <div class="balance-body" data-account-type="{{ $key }}">
                            <div class="balance-empty">{{ __('ui.no_transactions') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ─── CHARTS ─── --}}
        <div class="db-charts-grid">
            <div class="db-chart-card">
                <div class="chart-header">
                    <h6><i class="bi bi-bar-chart-fill" style="color: var(--db-primary);"></i> {{ __('ui.revenue_profit') }}</h6>
                    <div class="chart-controls">
                        <button class="chart-period-btn active" data-chart="main" data-period="daily">{{ __('ui.day') }}</button>
                        <button class="chart-period-btn" data-chart="main" data-period="weekly">{{ __('ui.week') }}</button>
                        <button class="chart-period-btn" data-chart="main" data-period="monthly">{{ __('ui.month') }}</button>
                        <button class="chart-period-btn" data-chart="main" data-period="yearly">{{ __('ui.year') }}</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-loading-overlay" id="mainChartLoading">
                        <div class="spinner"></div>
                    </div>
                    <div id="mainChart"></div>
                </div>
            </div>

            <div class="db-chart-card">
                <div class="chart-header">
                    <h6><i class="bi bi-pie-chart-fill" style="color: var(--db-success);"></i> {{ __('ui.customer_distribution') }}</h6>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-loading-overlay" id="secondaryChartLoading">
                        <div class="spinner"></div>
                    </div>
                    <div id="secondaryChart"></div>
                </div>
            </div>
        </div>

        {{-- ─── SECONDARY GRID ─── --}}
        <div class="db-secondary-grid">
            {{-- Recent Activity --}}
            <div class="db-activity-card">
                <div class="activity-header">
                    <h6><i class="bi bi-clock-history" style="color: var(--db-primary);"></i> {{ __('ui.recent_activity') }}</h6>
                    <div class="activity-filter">
                        <button class="filter-btn active" data-filter="all">{{ __('ui.all') }}</button>
                        <button class="filter-btn" data-filter="sales">{{ __('ui.sales') }}</button>
                        <button class="filter-btn" data-filter="purchases">{{ __('ui.purchases') }}</button>
                    </div>
                </div>
                <div class="db-activity-list" id="activityList">
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-hourglass-split fs-4 d-block mb-1"></i>
                        <span class="small">{{ __('ui.loading_activity') }}</span>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            <div class="db-alerts-card">
                <div class="alerts-header">
                    <h6><i class="bi bi-bell-fill" style="color: var(--db-warning);"></i> {{ __('ui.alerts_notifications') }}</h6>
                    <span class="alert-count" id="alertCount">0</span>
                </div>
                <div id="alertsList">
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-hourglass-split fs-4 d-block mb-1"></i>
                        <span class="small">{{ __('ui.loading_alerts') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
@section('js')
    <script src="{{ asset('vendor/apexcharts/apexcharts-7.1.0.min.js') }}"></script>
    @php
        $dashboardTranslations = [
            'revenue' => __('ui.revenue'), 'cogs' => __('ui.cogs'), 'gross_profit' => __('ui.gross_profit'),
            'expenses' => __('ui.expenses'), 'net_profit' => __('ui.net_profit'), 'inventory_value' => __('ui.inventory_value'),
            'receivables' => __('ui.receivables'), 'payables' => __('ui.payables'), 'receivable' => __('ui.receivable'),
            'payable' => __('ui.payable'), 'zero' => __('ui.zero'), 'no_transactions' => __('ui.no_transactions'),
            'no_data' => __('ui.no_data_available'), 'no_period_data' => __('ui.no_data_selected_period'),
            'revenue_afn' => __('ui.revenue_afn'), 'revenue_usd' => __('ui.revenue_usd'), 'profit_afn' => __('ui.profit_afn'),
            'total' => __('ui.total'), 'no_recent_activity' => __('ui.no_recent_activity'), 'activity' => __('ui.activity'),
            'no_alerts' => __('ui.no_alerts'), 'running_smoothly' => __('ui.everything_running_smoothly'),
            'view_details' => __('ui.view_details'), 'last_updated' => __('ui.last_updated'),
        ];
    @endphp
    <script>
        $(document).ready(function() {
            const i18n = @json($dashboardTranslations);
            // ─── STATE ───
            let state = {
                timeframe: 'month',
                chartPeriod: 'daily',
                activityFilter: 'all',
                charts: {},
            };

            // ─── HELPER: Format Currency ───
            function formatCurrency(value, symbol) {
                if (value === null || value === undefined || isNaN(value)) {
                    return (symbol || '$') + '0.00';
                }
                return (symbol || '$') + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
// ─── RENDER STATS ───
            function renderStats(data) {
                const sales = data.sales || {};
                const cogs = data.cogs || {};
                const grossProfit = data.gross_profit || {};
                const expenses = data.expenses || {};
                const netProfit = data.net_profit || {};
                const inventory = data.inventory || {};
                const receivables = data.receivables || {};
                const payables = data.payables || {};

                // ─── Helper to format currency ───
                function formatCurrency(value) {
                    if (value === null || value === undefined || isNaN(value)) {
                        return '0.00';
                    }
                    return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }

                // ─── Helper to create currency display item ───
                function currencyItem(symbol, code, amount, isPositive) {
                    const symbolClass = code === 'USD' ? 'usd' : 'afn';
                    const amountClass = isPositive ? 'positive' : 'negative';
                    return `
            <div class="stat-currency-item">
                <div class="currency-info">
                    <span class="currency-symbol ${symbolClass}">${symbol}</span>
                    <span class="currency-code">${code}</span>
                </div>
                <span class="currency-amount ${amountClass}">${formatCurrency(amount)}</span>
            </div>
        `;
                }

                // ─── Helper to create stat card ───
                function createStatCard(config) {
                    const {
                        id,
                        color,
                        icon,
                        title,
                        currencyItems,
                        change,
                        changeType,
                        progress,
                        progressColor
                    } = config;

                    let changeHtml = '';
                    if (change !== undefined && change !== null) {
                        const changeIcon = changeType === 'positive' ? 'bi-arrow-up' : 'bi-arrow-down';
                        const changeClass = changeType || 'neutral';
                        changeHtml = `
                <div class="stat-change ${changeClass}">
                    <i class="bi ${changeIcon}"></i> ${Math.abs(change).toFixed(1)}%
                </div>
            `;
                    }

                    let progressHtml = '';
                    if (progress !== undefined && progress !== null) {
                        progressHtml = `
                <div class="stat-progress">
                    <div class="stat-progress-bar ${progressColor || color}" style="width: ${Math.min(progress, 100)}%;"></div>
                </div>
            `;
                    }

                    return `
            <div class="db-stat-card ${color}" id="${id}">
                <div class="stat-header">
                    <span class="stat-title">${title}</span>
                    <div class="stat-icon ${color}">
                        <i class="bi ${icon}"></i>
                    </div>
                </div>
                <div class="stat-amounts">
                    ${currencyItems}
                </div>
                ${changeHtml}
                ${progressHtml}
            </div>
        `;
                }

                // ─── BUILD STATS CARDS ───
                const statsConfigs = [
                    {
                        id: 'statRevenue',
                        color: 'primary',
                        icon: 'bi-currency-dollar',
                        title: i18n.revenue,
                        currencyItems: `
                ${currencyItem('$', 'USD', sales.usd || 0, (sales.usd || 0) >= 0)}
                ${currencyItem('؋', 'AFN', sales.afn || 0, (sales.afn || 0) >= 0)}
            `,
                        change: sales.growth || 0,
                        changeType: (sales.growth || 0) >= 0 ? 'positive' : 'negative',
                        progress: Math.min(Math.abs(sales.growth || 0) * 2, 100),
                        progressColor: 'primary'
                    },
                    {
                        id: 'statCogs',
                        color: 'danger',
                        icon: 'bi-box-seam',
                        title: i18n.cogs,
                        currencyItems: `
                ${currencyItem('$', 'USD', cogs.total || 0, false)}
                ${currencyItem('؋', 'AFN', (cogs.total || 0) * 66, false)}
            `,
                        change: cogs.growth || 0,
                        changeType: (cogs.growth || 0) >= 0 ? 'positive' : 'negative',
                        progress: Math.min(Math.abs(cogs.growth || 0) * 2, 100),
                        progressColor: 'danger'
                    },
                    {
                        id: 'statGrossProfit',
                        color: 'success',
                        icon: 'bi-graph-up-arrow',
                        title: i18n.gross_profit,
                        currencyItems: `
                ${currencyItem('$', 'USD', grossProfit.usd || 0, (grossProfit.usd || 0) >= 0)}
                ${currencyItem('؋', 'AFN', grossProfit.afn || 0, (grossProfit.afn || 0) >= 0)}
            `,
                        change: grossProfit.growth || 0,
                        changeType: (grossProfit.growth || 0) >= 0 ? 'positive' : 'negative',
                        progress: Math.min(Math.abs(grossProfit.margin || 0) * 2, 100),
                        progressColor: 'success'
                    },
                    {
                        id: 'statExpenses',
                        color: 'warning',
                        icon: 'bi-receipt',
                        title: i18n.expenses,
                        currencyItems: `
                ${currencyItem('$', 'USD', expenses.usd || 0, false)}
                ${currencyItem('؋', 'AFN', expenses.afn || 0, false)}
            `,
                        change: expenses.growth || 0,
                        changeType: (expenses.growth || 0) >= 0 ? 'positive' : 'negative',
                        progress: Math.min(Math.abs(expenses.growth || 0) * 2, 100),
                        progressColor: 'warning'
                    },
                    {
                        id: 'statNetProfit',
                        color: 'purple',
                        icon: 'bi-trophy',
                        title: i18n.net_profit,
                        currencyItems: `
                ${currencyItem('$', 'USD', netProfit.usd || 0, (netProfit.usd || 0) >= 0)}
                ${currencyItem('؋', 'AFN', netProfit.afn || 0, (netProfit.afn || 0) >= 0)}
            `,
                        change: netProfit.growth || 0,
                        changeType: (netProfit.growth || 0) >= 0 ? 'positive' : 'negative',
                        progress: Math.min(Math.abs(netProfit.margin || 0) * 2, 100),
                        progressColor: 'purple'
                    },
                    {
                        id: 'statInventory',
                        color: 'teal',
                        icon: 'bi-archive',
                        title: i18n.inventory_value,
                        currencyItems: `
                ${currencyItem('$', 'USD', inventory.value || 0, (inventory.value || 0) >= 0)}
                ${currencyItem('؋', 'AFN', (inventory.value || 0) * 66, (inventory.value || 0) >= 0)}
            `,
                        change: null,
                        changeType: 'neutral'
                    },
                    {
                        id: 'statReceivables',
                        color: 'blue',
                        icon: 'bi-people',
                        title: i18n.receivables,
                        currencyItems: `
                ${currencyItem('$', 'USD', receivables.usd || 0, (receivables.usd || 0) >= 0)}
                ${currencyItem('؋', 'AFN', receivables.afn || 0, (receivables.afn || 0) >= 0)}
            `,
                        change: null,
                        changeType: 'neutral'
                    },
                    {
                        id: 'statPayables',
                        color: 'rose',
                        icon: 'bi-truck',
                        title: i18n.payables,
                        currencyItems: `
                ${currencyItem('$', 'USD', payables.usd || 0, false)}
                ${currencyItem('؋', 'AFN', payables.afn || 0, false)}
            `,
                        change: null,
                        changeType: 'neutral'
                    }
                ];

                // ─── RENDER CARDS ───
                const statsHtml = statsConfigs.map(config => createStatCard(config)).join('');
                $('#statsGrid').html(statsHtml);

                // ─── Render Currency Balances ───
                renderCurrencyBalances(data.currency_balances || {});

                // ─── Update Alert Count ───
                const alerts = data.alerts || [];
                $('#alertCount').text(alerts.length || 0);
            }

            // ─── RENDER CURRENCY BALANCES ───
            function renderCurrencyBalances(balances) {
                const accountTypes = ['customer', 'supplier', 'agent', 'expense', 'saraf'];

                accountTypes.forEach(type => {
                    const container = $('.balance-body[data-account-type="' + type + '"]');
                    if (!container.length) return;

                    const typeBalances = balances[type] || [];

                    if (typeBalances.length === 0) {
                        container.html(`<div class="balance-empty"><i class="bi bi-inboxes me-1"></i> ${i18n.no_transactions}</div>`);
                        return;
                    }

                    let html = '';
                    typeBalances.forEach(b => {
                        const isPositive = b.balance > 0.01;
                        const isNegative = b.balance < -0.01;
                        const colorClass = isPositive ? 'positive' : (isNegative ? 'negative' : 'zero');
                        const sign = isPositive ? '+' : (isNegative ? '-' : '');
                        const status = isPositive ? i18n.receivable : (isNegative ? i18n.payable : i18n.zero);
                        const balanceDisplay = Math.abs(b.balance).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                        html += `
                            <div class="balance-item">
                                <span class="currency-symbol">${b.currency_symbol || '$'}</span>
                                <span class="currency-code">${b.currency_code || 'USD'}</span>
                                <span class="balance-amount ${colorClass}">${sign}${balanceDisplay}</span>
                                <span class="balance-status ${colorClass}">${status}</span>
                            </div>
                        `;
                    });

                    container.html(html);
                });
            }

            // ─── LOAD DASHBOARD ───
            function loadDashboard() {
                showLoading();
                $.ajax({
                    url: '{{ route("admin.dashboard.stats") }}',
                    data: { timeframe: state.timeframe },
                    success: function(response) {
                        if (response.success) {
                            renderStats(response.data);
                            // Load charts
                            loadCharts('main', state.chartPeriod);
                            loadActivity(state.activityFilter);
                            renderAlerts(response.data.alerts);
                            updateLastUpdated();
                        }
                    },
                    error: function(xhr) {
                        console.error('Dashboard error:', xhr);
                        $('#statsGrid').html(`
                            <div class="text-center text-danger py-4" style="grid-column: 1 / -1;">
                                <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>
                                <p class="fw-semibold">Error loading dashboard data</p>
                                <p class="small text-muted">Please try refreshing the page</p>
                            </div>
                        `);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }

            // ─── LOAD CHARTS ───

            function loadCharts(type, period) {
                $('#mainChartLoading').addClass('show');
                $('#secondaryChartLoading').addClass('show');

                // Clear previous chart content
                $('#mainChart').html('');
                $('#secondaryChart').html('');

                // Load main chart
                $.ajax({
                    url: '{{ route("admin.dashboard.charts") }}',
                    data: {
                        type: type,
                        period: period
                    },
                    success: function(response) {
                        $('#mainChartLoading').removeClass('show');
                        if (response.success) {
                            renderMainChart(response.data);
                        } else {
                            $('#mainChart').html(`
                    <div class="text-center text-danger py-5">
                        <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                        <p>${response.error || 'Error loading chart data'}</p>
                    </div>
                `);
                        }
                    },
                    error: function(xhr) {
                        $('#mainChartLoading').removeClass('show');
                        $('#mainChart').html(`
                <div class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                    <p>Error loading chart data</p>
                </div>
            `);
                        console.error('Chart error:', xhr);
                    }
                });

                // Load secondary chart
                $.ajax({
                    url: '{{ route("admin.dashboard.charts") }}',
                    data: {
                        type: 'customers',
                        period: period
                    },
                    success: function(response) {
                        $('#secondaryChartLoading').removeClass('show');
                        if (response.success) {
                            renderSecondaryChart(response.data);
                        } else {
                            $('#secondaryChart').html(`
                    <div class="text-center text-danger py-5">
                        <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                        <p>${response.error || 'Error loading chart data'}</p>
                    </div>
                `);
                        }
                    },
                    error: function() {
                        // Fallback to category data
                        $('#secondaryChartLoading').removeClass('show');
                        $.ajax({
                            url: '{{ route("admin.dashboard.charts") }}',
                            data: {
                                type: 'categories',
                                period: period
                            },
                            success: function(res) {
                                if (res.success) {
                                    renderSecondaryChart(res.data);
                                } else {
                                    $('#secondaryChart').html(`
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-pie-chart fs-2 d-block mb-2"></i>
                                <p>${i18n.no_data}</p>
                            </div>
                        `);
                                }
                            },
                            error: function() {
                                $('#secondaryChart').html(`
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-pie-chart fs-2 d-block mb-2"></i>
                            <p>${i18n.no_data}</p>
                        </div>
                    `);
                            }
                        });
                    }
                });
            }

            // ─── RENDER MAIN CHART ───
            function renderMainChart(data) {
                if (!data || data.length === 0) {
                    // Show empty state
                    document.querySelector('#mainChart').innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-bar-chart-line fs-1 d-block mb-2"></i>
                <p>${i18n.no_period_data}</p>
            </div>
        `;
                    return;
                }

                const labels = data.map(item => item.label || 'N/A');
                const afnRevenue = data.map(item => item.afn_revenue || item.afn || 0);
                const usdRevenue = data.map(item => item.usd_revenue || item.usd || 0);
                const profit = data.map(item => item.profit || 0);

                const options = {
                    series: [
                        {
                            name: i18n.revenue_afn,
                            data: afnRevenue,
                            color: '#f59e0b'
                        },
                        {
                            name: i18n.revenue_usd,
                            data: usdRevenue,
                            color: '#4f46e5'
                        },
                        {
                            name: i18n.profit_afn,
                            data: profit,
                            color: '#10b981'
                        }
                    ],
                    chart: {
                        type: 'area',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        },
                        fontFamily: 'Inter, sans-serif',
                        foreColor: '#64748b',
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 100],
                        },
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        padding: {
                            top: 10,
                            right: 10,
                            bottom: 10,
                            left: 10
                        }
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            style: {
                                colors: '#94a3b8',
                                fontSize: '10px',
                                fontFamily: 'Inter, sans-serif'
                            },
                            rotate: -45,
                            rotateAlways: false,
                            hideOverlappingLabels: true,
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#94a3b8',
                                fontSize: '10px',
                                fontFamily: 'Inter, sans-serif'
                            },
                            formatter: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(1) + 'K';
                                }
                                return value.toFixed(0);
                            }
                        },
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(value) {
                                return value.toFixed(2);
                            }
                        },
                    },
                    legend: {
                        position: 'top',
                        fontSize: '10px',
                        fontFamily: 'Inter, sans-serif',
                        labels: {
                            colors: '#475569'
                        },
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                if (state.charts.main) {
                    state.charts.main.updateOptions(options);
                    state.charts.main.updateSeries(options.series);
                } else {
                    const chart = new ApexCharts(document.querySelector('#mainChart'), options);
                    chart.render();
                    state.charts.main = chart;
                }
            }
            // ─── LOAD SECONDARY CHART ───
            function loadSecondaryChart() {
                $.ajax({
                    url: '{{ route("admin.dashboard.charts") }}',
                    data: { type: 'customers', period: 'monthly' },
                    success: function(response) {
                        if (response.success) {
                            renderSecondaryChart(response.data);
                        }
                    },
                    error: function() {
                        $.ajax({
                            url: '{{ route("admin.dashboard.charts") }}',
                            data: { type: 'categories', period: 'monthly' },
                            success: function(res) {
                                if (res.success) renderSecondaryChart(res.data);
                            }
                        });
                    }
                });
            }

// ─── RENDER SECONDARY CHART ───
            function renderSecondaryChart(data) {
                if (!data || !data.labels || data.labels.length === 0) {
                    document.querySelector('#secondaryChart').innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                <p>${i18n.no_data}</p>
            </div>
        `;
                    return;
                }

                const options = {
                    series: data.values || [],
                    chart: {
                        type: 'donut',
                        height: 250,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        },
                        fontFamily: 'Inter, sans-serif',
                    },
                    labels: data.labels || [],
                    colors: data.colors || ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#3b82f6'],
                    legend: {
                        position: 'bottom',
                        fontSize: '10px',
                        fontFamily: 'Inter, sans-serif',
                        labels: {
                            colors: '#475569'
                        },
                        itemMargin: {
                            horizontal: 5,
                            vertical: 5
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: i18n.total,
                                        formatter: function() {
                                            const total = data.values ? data.values.reduce((a, b) => a + b, 0) : 0;
                                            return total.toString();
                                        },
                                    },
                                },
                            },
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(value) {
                                return value.toString();
                            }
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                };

                if (state.charts.secondary) {
                    state.charts.secondary.updateOptions(options);
                    state.charts.secondary.updateSeries(options.series);
                } else {
                    const chart = new ApexCharts(document.querySelector('#secondaryChart'), options);
                    chart.render();
                    state.charts.secondary = chart;
                }
            }
            // ─── LOAD ACTIVITY ───
            function loadActivity(filter) {
                $.ajax({
                    url: '{{ route("admin.dashboard.recent") }}',
                    data: { type: filter, limit: 10 },
                    success: function(response) {
                        if (response.success) {
                            renderActivity(response.data);
                        }
                    },
                    error: function(xhr) {
                        console.error('Activity error:', xhr);
                    }
                });
            }

            // ─── RENDER ACTIVITY ───
            function renderActivity(items) {
                const container = $('#activityList');

                if (!items || items.length === 0) {
                    container.html(`
                        <div class="db-empty">
                            <i class="bi bi-inboxes"></i>
                            <div class="title">${i18n.no_recent_activity}</div>
                        </div>
                    `);
                    return;
                }

                let html = '';
                items.forEach(function(item, index) {
                    const colorMap = {
                        'sale': 'green',
                        'purchase': 'blue',
                        'transaction': 'purple',
                        'return': 'yellow',
                        'expense': 'yellow',
                    };
                    const iconColor = colorMap[item.type] || 'gray';
                    const currencySymbol = item.currency === 'AFN' ? '؋' : '$';

                    html += `
                        <div class="db-activity-item" style="animation-delay: ${(index * 0.05)}s;">
                            <div class="activity-icon ${iconColor}">
                                <i class="bi ${item.icon || 'bi-clock-history'}"></i>
                            </div>
                            <div class="activity-content">
                                <div class="title">${item.title || item.no || i18n.activity}</div>
                                <div class="description">
                                    ${item.description || ''}
                                    ${item.amount ? `<span class="amount">${currencySymbol}${parseFloat(item.amount).toFixed(2)}</span>` : ''}
                                </div>
                            </div>
                            <div class="activity-time">${item.date || ''}</div>
                            ${item.status ? `<span class="activity-status ${item.status}">${item.status}</span>` : ''}
                        </div>
                    `;
                });

                container.html(html);
            }

            // ─── RENDER ALERTS ───
            function renderAlerts(items) {
                const container = $('#alertsList');

                if (!items || items.length === 0) {
                    container.html(`
                        <div class="db-empty">
                            <i class="bi bi-check-circle"></i>
                            <div class="title">${i18n.no_alerts}</div>
                            <div class="sub-text">${i18n.running_smoothly}</div>
                        </div>
                    `);
                    return;
                }

                let html = '';
                items.forEach(function(alert) {
                    html += `
                        <div class="db-alert-item">
                            <div class="alert-icon ${alert.type || 'warning'}">
                                <i class="bi ${alert.icon || 'bi-exclamation-triangle'}"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">${alert.title}</div>
                                <div class="alert-message">${alert.message}</div>
                                ${alert.link ? `<a href="${alert.link}" class="alert-link">${i18n.view_details} →</a>` : ''}
                            </div>
                        </div>
                    `;
                });

                container.html(html);
            }

            // ─── LOADING FUNCTIONS ───
            function showLoading() {
                $('#loadingOverlay').addClass('show');
            }

            function hideLoading() {
                $('#loadingOverlay').removeClass('show');
            }

            function updateLastUpdated() {
                const now = new Date();
                const time = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#lastUpdated').text('· ' + i18n.last_updated + ': ' + time);
            }

            // ─── EVENT LISTENERS ───
            $('#timeframeSelect').on('change', function() {
                state.timeframe = $(this).val();
                loadDashboard();
            });

            $('#refreshBtn').on('click', function() {
                const btn = $(this);
                btn.addClass('spinning');
                loadDashboard().finally(() => {
                    btn.removeClass('spinning');
                });
            });

            $(document).on('click', '.chart-period-btn', function() {
                const period = $(this).data('period');
                $(this).closest('.chart-controls').find('.chart-period-btn').removeClass('active');
                $(this).addClass('active');

                state.chartPeriod = period;
                $('#mainChartLoading').addClass('show');
                loadCharts('main', period).finally(() => {
                    $('#mainChartLoading').removeClass('show');
                });
            });

            $(document).on('click', '.filter-btn', function() {
                const filter = $(this).data('filter');
                $(this).closest('.activity-filter').find('.filter-btn').removeClass('active');
                $(this).addClass('active');

                state.activityFilter = filter;
                loadActivity(filter);
            });

            // ─── AUTO-REFRESH ───
            let refreshInterval = setInterval(function() {
                if (!document.hidden) loadDashboard();
            }, 60000);

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    clearInterval(refreshInterval);
                } else {
                    refreshInterval = setInterval(function() {
                        if (!document.hidden) loadDashboard();
                    }, 60000);
                }
            });

            // ─── INITIAL LOAD ───
            loadDashboard();
        });
    </script>
@endsection
