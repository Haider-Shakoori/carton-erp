<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}"
          type="image/x-icon">

    <!-- SEO -->
    <meta name="robots" content="noindex" />
    <meta name="description" content="Carton Manufacturing ERP" />
    <meta name="author" content="Fida Technologies" />

    <!-- Bootstrap 5 + Icons -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}" rel="stylesheet">

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/toastify/toastify.min.css') }}">

    <style>
        /* ============================================================
           ROOT VARIABLES - Modern Light Theme
           ============================================================ */
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --primary-bg: #eef2ff;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);

            --success: #10b981;
            --success-bg: #ecfdf5;
            --warning: #f59e0b;
            --warning-bg: #fffbeb;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --info: #3b82f6;
            --info-bg: #eff6ff;

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

            --radius: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.08);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.10);

            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --sidebar-width: 260px;
            --navbar-height: 70px;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
        }

        /* Toastify Custom Override */
        .toastify {
            border-radius: var(--radius-sm) !important;
            font-family: 'Inter', sans-serif !important;
            box-shadow: var(--shadow-lg) !important;
            padding: 12px 20px !important;
        }

        .toastify .toast-close {
            opacity: 0.6 !important;
        }

        .toastify .toast-close:hover {
            opacity: 1 !important;
        }

        /* ============================================================
           LAYOUT
           ============================================================ */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .app-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .app-main.collapsed {
            margin-left: 0;
        }

        .app-content {
            flex: 1;
            padding: 1.5rem 2rem 2rem;
            margin-top: var(--navbar-height);
        }

        @media (max-width: 992px) {
            .app-main {
                margin-left: 0;
            }

            .app-content {
                padding: 1rem;
            }
        }

        /* ============================================================
           SIDEBAR - Clean Glassmorphism
           ============================================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(226, 232, 240, 0.6);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .sidebar-brand img {
            height: 42px;
            width: auto;
        }

        .sidebar-brand .brand-text {
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .sidebar-brand .brand-text small {
            display: block;
            font-size: 0.6rem;
            font-weight: 600;
            -webkit-text-fill-color: var(--gray-400);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0.75rem 2rem;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        .nav-section {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-400);
            padding: 1rem 0.75rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }

        .nav-item-custom {
            border-radius: var(--radius-sm);
            transition: var(--transition);
            margin-bottom: 1px;
        }

        .nav-item-custom>a,
        .nav-item-custom>.nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.9rem;
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
        }

        .nav-item-custom>a i,
        .nav-item-custom>.nav-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
            color: var(--gray-400);
            transition: var(--transition);
        }

        .nav-item-custom>a:hover,
        .nav-item-custom>.nav-link:hover {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .nav-item-custom>a:hover i,
        .nav-item-custom>.nav-link:hover i {
            color: var(--primary);
        }

        .nav-item-custom.active>a,
        .nav-item-custom.active>.nav-link {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.30);
        }

        .nav-item-custom.active>a i,
        .nav-item-custom.active>.nav-link i {
            color: white;
        }

        .nav-item-custom .nav-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
            font-size: 0.7rem;
        }

        .nav-item-custom.open .nav-arrow {
            transform: rotate(180deg);
        }

        .nav-sub {
            padding-left: 2.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .nav-item-custom.open .nav-sub {
            max-height: 500px;
        }

        .nav-sub .nav-item-custom>a {
            padding: 0.4rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 400;
        }

        .nav-sub .nav-item-custom>a i {
            font-size: 0.8rem;
            width: 18px;
        }

        .nav-sub .nav-item-custom.active>a {
            background: var(--primary-bg);
            color: var(--primary);
            box-shadow: none;
        }

        .nav-sub .nav-item-custom.active>a i {
            color: var(--primary);
        }

        /* ============================================================
           LOW STOCK NOTIFICATION STYLES
           ============================================================ */
        .low-stock-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            font-size: 0.55rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Notification Dropdown */
        .notification-dropdown {
            width: 380px;
            max-height: 500px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-dropdown .dropdown-header {
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1;
            background: white;
        }

        .notification-dropdown .dropdown-header .view-all {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
        }

        .notification-dropdown .dropdown-header .view-all:hover {
            text-decoration: underline;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            transition: var(--transition);
            cursor: pointer;
        }

        .notification-item:hover {
            background: var(--gray-50);
        }

        .notification-item .notif-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .notification-item .notif-icon.danger {
            background: #fecaca;
            color: #991b1b;
        }

        .notification-item .notif-icon.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .notification-item .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notification-item .notif-content .notif-title {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-800);
        }

        .notification-item .notif-content .notif-message {
            font-size: 0.7rem;
            color: var(--gray-500);
            margin-top: 0.1rem;
        }

        .notification-item .notif-content .notif-meta {
            font-size: 0.6rem;
            color: var(--gray-400);
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-item .notif-content .notif-meta .stock-badge {
            padding: 0.05rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .notification-item .notif-content .notif-meta .stock-badge.danger {
            background: #fecaca;
            color: #991b1b;
        }

        .notification-item .notif-content .notif-meta .stock-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .notification-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--gray-400);
        }

        .notification-empty i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-300);
        }

        .notification-empty .title {
            font-weight: 600;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .notification-empty .sub-text {
            font-size: 0.75rem;
        }

        /* Low Stock Modal */
        .low-stock-modal .modal-content {
            border-radius: var(--radius);
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .low-stock-modal .modal-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 1.25rem 1.5rem;
        }

        .low-stock-modal .modal-header .modal-title {
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .low-stock-modal .modal-header .modal-title i {
            font-size: 1.5rem;
        }

        .low-stock-modal .modal-body {
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .low-stock-modal .modal-footer {
            border-top: 1px solid var(--gray-200);
            padding: 1rem 1.5rem;
        }

        .low-stock-modal .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            background: var(--gray-50);
            border-left: 4px solid #f59e0b;
            transition: var(--transition);
        }

        .low-stock-modal .low-stock-item:hover {
            background: var(--gray-100);
        }

        .low-stock-modal .low-stock-item .item-info {
            display: flex;
            flex-direction: column;
        }

        .low-stock-modal .low-stock-item .item-info .name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-800);
        }

        .low-stock-modal .low-stock-item .item-info .details {
            font-size: 0.7rem;
            color: var(--gray-500);
        }

        .low-stock-modal .low-stock-item .item-info .details .category {
            background: var(--gray-200);
            padding: 0.05rem 0.5rem;
            border-radius: 10px;
        }

        .low-stock-modal .low-stock-item .stock-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .low-stock-modal .low-stock-item .stock-status .badge {
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .low-stock-modal .low-stock-item .stock-status .badge.danger {
            background: #fecaca;
            color: #991b1b;
        }

        .low-stock-modal .low-stock-item .stock-status .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .low-stock-modal .low-stock-item .stock-status .stock-count {
            font-weight: 700;
            font-size: 0.9rem;
            min-width: 40px;
            text-align: right;
        }

        .low-stock-modal .low-stock-item.critical {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .low-stock-modal .low-stock-item.critical:hover {
            background: #fecaca;
        }

        .low-stock-modal .low-stock-item.low {
            border-left-color: #f59e0b;
        }

        /* ============================================================
           NAVBAR - Clean & Light
           ============================================================ */
        .navbar-custom {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: var(--navbar-height);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            z-index: 1040;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: left 0.3s ease;
        }

        .navbar-custom.full-width {
            left: 0;
        }

        .navbar-custom .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-custom .navbar-left .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--gray-600);
            padding: 0.25rem;
            cursor: pointer;
            transition: var(--transition);
            display: none;
        }

        .navbar-custom .navbar-left .sidebar-toggle:hover {
            color: var(--primary);
        }

        .navbar-custom .navbar-left .page-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-800);
        }

        .navbar-custom .navbar-left .page-title .accent {
            color: var(--primary);
        }

        .navbar-custom .navbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .business-unit-switcher .dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            min-height: 40px;
            padding: .4rem .8rem;
            border-radius: 10px;
            border: 1px solid var(--gray-200);
            background: white;
            color: var(--gray-700);
            font-size: .78rem;
            font-weight: 700;
            transition: var(--transition);
        }

        .business-unit-switcher .dropdown-toggle:hover {
            border-color: var(--primary-light);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .business-unit-switcher .business-unit-label {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.1;
        }

        .business-unit-switcher .business-unit-label small {
            color: var(--gray-400);
            font-size: .56rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .business-unit-switcher .business-unit-option {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
        }

        .business-unit-switcher .business-unit-option.active {
            background: var(--primary-bg);
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .business-unit-switcher .business-unit-label small {
                display: none;
            }

            .business-unit-switcher .dropdown-toggle {
                padding: .4rem .6rem;
            }
        }

        .nav-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }

        .nav-icon-btn:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .nav-icon-btn .badge-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--danger);
            border: 2px solid white;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.3rem 0.6rem 0.3rem 0.3rem;
            border-radius: 50px;
            border: 1px solid var(--gray-200);
            background: white;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--gray-700);
        }

        .user-dropdown-toggle:hover {
            border-color: var(--primary-light);
            box-shadow: var(--shadow-sm);
        }

        .user-dropdown-toggle .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .user-dropdown-toggle .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-dropdown-toggle .user-info .name {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-800);
        }

        .user-dropdown-toggle .user-info .role {
            font-size: 0.65rem;
            color: var(--gray-400);
        }

        .user-dropdown-toggle .chevron {
            font-size: 0.7rem;
            color: var(--gray-400);
            margin-left: 0.2rem;
        }

        @media (max-width: 992px) {
            .navbar-custom {
                left: 0;
                padding: 0 1rem;
            }

            .navbar-custom .navbar-left .sidebar-toggle {
                display: block;
            }

            .navbar-custom .navbar-left .page-title {
                font-size: 0.95rem;
            }

            .user-dropdown-toggle .user-info {
                display: none;
            }

            .user-dropdown-toggle .chevron {
                display: none;
            }
        }

        /* ============================================================
           DROPDOWN MENUS
           ============================================================ */
        .dropdown-menu-custom {
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 0.5rem;
            min-width: 200px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.4);
        }

        .dropdown-menu-custom .dropdown-item {
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--gray-600);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-menu-custom .dropdown-item:hover {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .dropdown-menu-custom .dropdown-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .dropdown-menu-custom .dropdown-divider {
            margin: 0.3rem 0;
            border-color: var(--gray-200);
        }

        /* ============================================================
           CARDS
           ============================================================ */
        .card-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            overflow: hidden;
        }

        .card-modern:hover {
            box-shadow: var(--shadow-md);
        }

        .card-modern .card-header {
            background: transparent;
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-modern .card-body {
            padding: 1.25rem;
        }

        /* ============================================================
           STATS CARDS
           ============================================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem 1.25rem 1rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .stat-card-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card-modern .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .stat-card-modern .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-card-modern .stat-icon.purple {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .stat-card-modern .stat-icon.blue {
            background: var(--info-bg);
            color: var(--info);
        }

        .stat-card-modern .stat-icon.green {
            background: var(--success-bg);
            color: var(--success);
        }

        .stat-card-modern .stat-icon.red {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .stat-card-modern .stat-icon.yellow {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .stat-card-modern .stat-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--gray-400);
        }

        .stat-card-modern .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .stat-card-modern .stat-change {
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.3rem;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.1rem 0.5rem;
            border-radius: 20px;
        }

        .stat-card-modern .stat-change.up {
            color: var(--success);
            background: var(--success-bg);
        }

        .stat-card-modern .stat-change.down {
            color: var(--danger);
            background: var(--danger-bg);
        }

        /* ============================================================
           TABLE
           ============================================================ */
        .table-modern {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .table-modern thead th {
            padding: 0.7rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .table-modern tbody td {
            padding: 0.7rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: var(--transition);
        }

        .table-modern tbody tr:hover {
            background: var(--gray-50);
        }

        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }

        /* ============================================================
           BADGES
           ============================================================ */
        .badge-modern {
            padding: 0.2rem 0.75rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge-modern.primary {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .badge-modern.success {
            background: var(--success-bg);
            color: var(--success);
        }

        .badge-modern.warning {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .badge-modern.danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .badge-modern.info {
            background: var(--info-bg);
            color: var(--info);
        }

        .badge-modern.gray {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .btn-modern {
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-modern-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.25);
        }

        .btn-modern-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(79, 70, 229, 0.35);
            color: white;
        }

        .btn-modern-outline {
            background: transparent;
            color: var(--gray-600);
            border: 1.5px solid var(--gray-200);
        }

        .btn-modern-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        .btn-modern-success {
            background: var(--success);
            color: white;
        }

        .btn-modern-success:hover {
            background: #059669;
            color: white;
        }

        .btn-modern-danger {
            background: var(--danger);
            color: white;
        }

        .btn-modern-danger:hover {
            background: #dc2626;
            color: white;
        }

        .btn-icon-sm {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: var(--gray-400);
            transition: var(--transition);
        }

        .btn-icon-sm:hover {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .btn-icon-sm.danger:hover {
            background: var(--danger-bg);
            color: var(--danger);
        }

        /* ============================================================
           FORMS
           ============================================================ */
        .form-control-modern {
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.5rem 0.85rem;
            font-size: 0.875rem;
            transition: var(--transition);
            background: white;
            width: 100%;
            color: var(--gray-800);
        }

        .form-control-modern:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
            outline: none;
        }

        .form-label-modern {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-500);
            margin-bottom: 0.3rem;
        }

        /* ============================================================
           PAGINATION
           ============================================================ */
        .pagination-modern {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .pagination-modern .page-link {
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-500);
            background: transparent;
            transition: var(--transition);
        }

        .pagination-modern .page-link:hover {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .pagination-modern .active .page-link {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.30);
        }

        .pagination-modern .disabled .page-link {
            color: var(--gray-300);
            cursor: not-allowed;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .stat-card-modern {
                padding: 1rem;
            }

            .stat-card-modern .stat-value {
                font-size: 1.2rem;
            }

            .table-modern {
                font-size: 0.75rem;
            }

            .table-modern thead th,
            .table-modern tbody td {
                padding: 0.5rem 0.6rem;
            }

            .notification-dropdown {
                width: 320px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }

            .stat-card-modern {
                padding: 0.75rem;
            }

            .stat-card-modern .stat-value {
                font-size: 1rem;
            }

            .stat-card-modern .stat-icon {
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }

            .stat-card-modern .stat-label {
                font-size: 0.55rem;
            }

            .notification-dropdown {
                width: 290px;
            }
        }

        /* ============================================================
           MISC UTILITIES
           ============================================================ */
        .text-gradient {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-gradient-primary {
            background: var(--primary-gradient);
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .gap-3 {
            gap: 1rem;
        }

        .flex-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-muted-light {
            color: var(--gray-400);
        }

        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1045;
            backdrop-filter: blur(4px);
        }

        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 992px) {
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>

    @yield('css')

    <!-- RTL Support Overrides -->
    @if ($isRtl)
        <style>
            .sidebar {
                left: auto;
                right: 0;
                border-right: none;
                border-left: 1px solid rgba(226, 232, 240, 0.6);
            }

            .sidebar.hidden {
                transform: translateX(100%);
            }

            .navbar-custom {
                left: 0;
                right: var(--sidebar-width);
            }

            .navbar-custom.full-width {
                right: 0;
            }

            .app-main {
                margin-left: 0;
                margin-right: var(--sidebar-width);
            }

            .app-main.collapsed {
                margin-right: 0;
            }

            .nav-sub {
                padding-left: 0;
                padding-right: 2.5rem;
            }

            .nav-item-custom .nav-arrow {
                margin-left: 0;
                margin-right: auto;
            }

            .nav-item-custom>a i,
            .nav-item-custom>.nav-link i {
                margin-left: 0;
                margin-right: 0;
            }
        </style>
    @endif

</head>

<body>

<div class="app-wrapper">

    <!-- ============================================================
SIDEBAR OVERLAY (Mobile)
============================================================ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ============================================================
SIDEBAR
============================================================ -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}">
            <div class="brand-text">
                ERP
                <small>{{ $setting->company_name ?? 'Sarafi System' }}</small>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <ul class="list-unstyled" style="margin:0;padding:0;">

                {{-- ============================================================ --}}
                {{-- 1. DASHBOARD --}}
                {{-- ============================================================ --}}
                @can('view dashboard')
                    <li class="nav-item-custom {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-house-door"></i>
                            <span>{{ __('ui.dashboard') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 2. SALES SECTION --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.sales') }}</li>

                @can('view sales')
                    <li class="nav-item-custom {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.sales.index') }}">
                            <i class="bi bi-cart-check"></i>
                            <span>{{ __('ui.sales_orders') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view customers')
                    <li class="nav-item-custom {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.customers.index') }}">
                            <i class="bi bi-people"></i>
                            <span>{{ __('ui.customers') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 3. PRODUCTION SECTION --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.production') }}</li>

                {{-- Bill of Materials (BOM) --}}
                @can('view bom')
                    <li class="nav-item-custom {{ request()->routeIs('bom.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-list-ul"></i>
                            <span>{{ __('ui.bill_of_materials') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('bom.index') ? 'active' : '' }}">
                                <a href="{{ route('bom.index') }}">
                                    <i class="bi bi-table"></i>
                                    <span>{{ __('ui.bom_list') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('bom.create') ? 'active' : '' }}">
                                <a href="{{ route('bom.create') }}">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>{{ __('ui.create_bom') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('bom.calculator') ? 'active' : '' }}">
                                <a href="{{ route('bom.calculator') }}">
                                    <i class="bi bi-calculator"></i>
                                    <span>{{ __('ui.bom_calculator') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- Production Orders --}}
                @can('view production orders')
                    <li class="nav-item-custom {{ request()->routeIs('production-orders.*') ? 'active' : '' }}">
                        <a href="{{ route('production-orders.index') }}">
                            <i class="bi bi-gear"></i>
                            <span>{{ __('ui.production_orders') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- Work Orders --}}
{{--                @can('view work orders')--}}
{{--                    <li class="nav-item-custom {{ request()->routeIs('work-orders.*') ? 'active' : '' }}">--}}
{{--                        <a href="{{ route('work-orders.index') }}">--}}
{{--                            <i class="bi bi-tools"></i>--}}
{{--                            <span>Work Orders</span>--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endcan--}}

                {{-- ============================================================ --}}
                {{-- 4. INVENTORY & PURCHASING --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.inventory_purchasing') }}</li>

                @can('view purchase orders')
                    <li class="nav-item-custom {{ request()->routeIs('admin.purchase-orders*') ? 'active' : '' }}">
                        <a href="{{ route('admin.purchase-orders.index') }}">
                            <i class="bi bi-cart-plus"></i>
                            <span>{{ __('ui.purchase_orders') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view stock')
                    <li class="nav-item-custom {{ request()->routeIs('admin.stock*') ? 'active' : '' }}">
                        <a href="{{ route('admin.stock.index') }}">
                            <i class="bi bi-box-seam"></i>
                            <span>{{ __('ui.stock_inventory') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view products')
                    <li class="nav-item-custom {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.products.index') }}">
                            <i class="bi bi-box"></i>
                            <span>{{ __('ui.products') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 5. FINANCE SECTION --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.finance') }}</li>

                @can('view transactions')
                    <li class="nav-item-custom {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.transactions.index') }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>{{ __('ui.transactions') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view expenses')
                    <li class="nav-item-custom {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.expenses.index') }}">
                            <i class="bi bi-wallet2"></i>
                            <span>{{ __('ui.expenses') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- HUMAN RESOURCES SECTION --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.human_resources') }}</li>

                {{-- Dashboard / HR Overview --}}
                @can('view hr dashboard')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.hr.dashboard') }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>{{ __('ui.hr_dashboard') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- Employees --}}
                @can('view employees')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.employees.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-people"></i>
                            <span>{{ __('ui.employees') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.employees.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.employees.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.employee_list') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.employees.create') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.employees.create') }}">
                                    <i class="bi bi-person-plus"></i>
                                    <span>{{ __('ui.add_employee') }}</span>
                                </a>
                            </li>
                            @can('view departments')
                                <li class="nav-item-custom {{ request()->routeIs('admin.hr.departments.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.hr.departments.index') }}">
                                        <i class="bi bi-building"></i>
                                        <span>{{ __('ui.departments') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view designations')
                                <li class="nav-item-custom {{ request()->routeIs('admin.hr.designations.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.hr.designations.index') }}">
                                        <i class="bi bi-briefcase"></i>
                                        <span>{{ __('ui.designations') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                {{-- Attendance --}}
                @can('view attendance')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.attendance.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-calendar-check"></i>
                            <span>{{ __('ui.attendance') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.attendance.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.attendance.index') }}">
                                    <i class="bi bi-calendar-week"></i>
                                    <span>{{ __('ui.daily_attendance') }}</span>
                                </a>
                            </li>
{{--                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.attendance.monthly') ? 'active' : '' }}">--}}
{{--                                <a href="{{ route('admin.hr.attendance.monthly') }}">--}}
{{--                                    <i class="bi bi-calendar-month"></i>--}}
{{--                                    <span>Monthly Attendance</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.attendance.report') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.attendance.report') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                    <span>{{ __('ui.attendance_report') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- Leave Management --}}
                @can('view leave requests')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.leaves.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-clock-history"></i>
                            <span>{{ __('ui.leave_management') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.leaves.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.leaves.index') }}">
                                    <i class="bi bi-list-check"></i>
                                    <span>{{ __('ui.leave_requests') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.leaves.create') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.leaves.create') }}">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>{{ __('ui.new_leave_request') }}</span>
                                </a>
                            </li>
                            @can('view leave types')
                                <li class="nav-item-custom {{ request()->routeIs('admin.hr.leaves.types') ? 'active' : '' }}">
                                    <a href="{{ route('admin.hr.leaves.types') }}">
                                        <i class="bi bi-tags"></i>
                                        <span>{{ __('ui.leave_types') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                {{-- Advances & Loans --}}
                @can('view employee advances')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.advances.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-coin"></i>
                            <span>{{ __('ui.advances_loans') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.advances.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.advances.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.all_advances') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.advances.create') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.advances.create') }}">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>{{ __('ui.new_advance') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- Payroll --}}
                @can('view payroll')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.payroll.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-wallet2"></i>
                            <span>{{ __('ui.payroll') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.payroll.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.payroll.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.payroll_list') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.payroll.generate') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.payroll.generate') }}">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>{{ __('ui.generate_payroll') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- HR Reports --}}
                @can('view hr reports')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>{{ __('ui.hr_reports') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.employees') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.reports.employees') }}">
                                    <i class="bi bi-people"></i>
                                    <span>{{ __('ui.employee_reports') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.attendance') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.reports.attendance') }}">
                                    <i class="bi bi-calendar-check"></i>
                                    <span>{{ __('ui.attendance_reports') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.payroll') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.reports.payroll') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                    <span>{{ __('ui.payroll_reports') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.leaves') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.reports.leaves') }}">
                                    <i class="bi bi-clock-history"></i>
                                    <span>{{ __('ui.leave_reports') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.reports.advances') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.reports.advances') }}">
                                    <i class="bi bi-coin"></i>
                                    <span>{{ __('ui.advances_reports') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- HR Settings --}}
                @can('view hr settings')
                    <li class="nav-item-custom {{ request()->routeIs('admin.hr.settings.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-gear"></i>
                            <span>{{ __('ui.hr_settings') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.settings.general') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.settings.general') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>{{ __('ui.general_settings') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.settings.attendance') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.settings.attendance') }}">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ __('ui.attendance_settings') }}</span>
                                </a>
                            </li>
                            <li class="nav-item-custom {{ request()->routeIs('admin.hr.settings.payroll') ? 'active' : '' }}">
                                <a href="{{ route('admin.hr.settings.payroll') }}">
                                    <i class="bi bi-cash"></i>
                                    <span>{{ __('ui.payroll_settings') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- ENTERPRISE CONTROL --}}
                {{-- ============================================================ --}}
                @canany(['view management reporting', 'view financial accounting', 'view purchase requests', 'view warehouses'])
                    <li class="nav-section">Enterprise Control</li>
                @endcanany

                @can('view management reporting')
                    <li class="nav-item-custom {{ request()->routeIs('admin.management-reporting.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.management-reporting.index') }}">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Management Reporting</span>
                        </a>
                    </li>
                @endcan

                @can('view financial accounting')
                    <li class="nav-item-custom {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.accounting.index') }}">
                            <i class="bi bi-journal-check"></i>
                            <span>Financial Accounting</span>
                        </a>
                    </li>
                @endcan

                @can('view purchase requests')
                    <li class="nav-item-custom {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.procurement.index') }}">
                            <i class="bi bi-cart-check"></i>
                            <span>Procure to Pay</span>
                        </a>
                    </li>
                @endcan

                @can('view warehouses')
                    <li class="nav-item-custom {{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.warehouses.index') }}">
                            <i class="bi bi-buildings"></i>
                            <span>Warehouses</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 6. PARTNERS --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.partners') }}</li>

                @can('view suppliers')
                    <li class="nav-item-custom {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.suppliers.index') }}">
                            <i class="bi bi-truck"></i>
                            <span>{{ __('ui.suppliers') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view agents')
                    <li class="nav-item-custom {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.agents.index') }}">
                            <i class="bi bi-person-badge"></i>
                            <span>{{ __('ui.agents') }}</span>
                        </a>
                    </li>
                @endcan

                @can('view sarafs')
                    <li class="nav-item-custom {{ request()->routeIs('admin.sarafs.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.sarafs.index') }}">
                            <i class="bi bi-person-lines-fill"></i>
                            <span>{{ __('ui.sarafan') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 7. SHAREHOLDERS --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.shareholders') }}</li>

                @can('view shareholders')
                    <li class="nav-item-custom {{ request()->routeIs('admin.shareholders.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-people-fill"></i>
                            <span>{{ __('ui.shareholders') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.shareholders.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.shareholders.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.all_shareholders') }}</span>
                                </a>
                            </li>
                            @can('create shareholders')
                                <li class="nav-item-custom {{ request()->routeIs('admin.shareholders.create') ? 'active' : '' }}">
                                    <a href="{{ route('admin.shareholders.create') }}">
                                        <i class="bi bi-person-plus"></i>
                                        <span>{{ __('ui.add_shareholder') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('view profit distributions')
                    <li class="nav-item-custom {{ request()->routeIs('admin.profit-distributions.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>{{ __('ui.profit_distribution') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.profit-distributions.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.profit-distributions.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.all_distributions') }}</span>
                                </a>
                            </li>
                            @can('create profit distributions')
                                <li class="nav-item-custom {{ request()->routeIs('admin.profit-distributions.create') ? 'active' : '' }}">
                                    <a href="{{ route('admin.profit-distributions.create') }}">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>{{ __('ui.new_distribution') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('view shareholder withdrawals')
                    <li class="nav-item-custom {{ request()->routeIs('admin.shareholder-withdrawals.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-cash"></i>
                            <span>{{ __('ui.withdrawals') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            <li class="nav-item-custom {{ request()->routeIs('admin.shareholder-withdrawals.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.shareholder-withdrawals.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>{{ __('ui.all_withdrawals') }}</span>
                                </a>
                            </li>
                            @can('approve shareholder withdrawals')
                                <li class="nav-item-custom {{ request()->routeIs('admin.shareholder-withdrawals.pending') ? 'active' : '' }}">
                                    <a href="{{ route('admin.shareholder-withdrawals.pending') }}">
                                        <i class="bi bi-clock-history"></i>
                                        <span>{{ __('ui.pending_approvals') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('edit shareholders')
                    <li class="nav-item-custom {{ request()->routeIs('admin.shareholder-settings.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.shareholder-settings.index') }}">
                            <i class="bi bi-sliders2"></i>
                            <span>{{ __('ui.shareholder_settings') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- ============================================================ --}}
                {{-- 8. ADMINISTRATION --}}
                {{-- ============================================================ --}}
                <li class="nav-section">{{ __('ui.administration') }}</li>

                @can('view users')
                    <li class="nav-item-custom {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-shield-lock"></i>
                            <span>{{ __('ui.user_management') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            @can('view users')
                                <li class="nav-item-custom {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.users.index') }}">
                                        <i class="bi bi-person-badge"></i>
                                        <span>{{ __('ui.users') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view roles')
                                <li class="nav-item-custom {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.roles.index') }}">
                                        <i class="bi bi-shield-lock"></i>
                                        <span>{{ __('ui.roles') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view permissions')
                                <li class="nav-item-custom {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.permissions.index') }}">
                                        <i class="bi bi-ui-checks-grid"></i>
                                        <span>{{ __('ui.permissions') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                {{-- Configuration --}}
                @can('view settings')
                    <li class="nav-item-custom {{ request()->routeIs('admin.currencies.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.audit.*') || request()->is('admin/account-categories*') || request()->is('admin/account-sub-categories*') ? 'open' : '' }}">
                        <a class="nav-link" data-toggle="submenu">
                            <i class="bi bi-sliders2"></i>
                            <span>{{ __('ui.configuration') }}</span>
                            <i class="bi bi-chevron-down nav-arrow"></i>
                        </a>
                        <ul class="list-unstyled nav-sub">
                            @can('view currencies')
                                <li class="nav-item-custom {{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.currencies.index') }}">
                                        <i class="bi bi-currency-dollar"></i>
                                        <span>{{ __('ui.currencies') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view account categories')
                                <li class="nav-item-custom {{ request()->is('admin/account-categories*') ? 'active' : '' }}">
                                    <a href="{{ url('admin/account-categories') }}">
                                        <i class="bi bi-grid-3x3-gap-fill"></i>
                                        <span>{{ __('ui.account_categories') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view account sub categories')
                                <li class="nav-item-custom {{ request()->is('admin/account-sub-categories*') ? 'active' : '' }}">
                                    <a href="{{ url('admin/account-sub-categories') }}">
                                        <i class="bi bi-grid-1x2-fill"></i>
                                        <span>{{ __('ui.account_sub_categories') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view settings')
                                <li class="nav-item-custom {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.settings.index') }}">
                                        <i class="bi bi-gear"></i>
                                        <span>{{ __('ui.system_settings') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view audit logs')
                                <li class="nav-item-custom {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.audit.index') }}">
                                        <i class="bi bi-clock-history"></i>
                                        <span>{{ __('ui.audit_logs') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

            </ul>
        </nav>
    </aside>

    <!-- ============================================================
MAIN CONTENT AREA
============================================================ -->
    <div class="app-main" id="appMain">

        <nav class="navbar-custom" id="navbarCustom">
            <div class="navbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="{{ __('ui.toggle_sidebar') }}">
                    <i class="bi bi-list"></i>
                </button>
                <span class="page-title">
                    @yield('page_title', __('ui.dashboard'))
                    <span class="accent">·</span>
                </span>
            </div>

            <div class="navbar-right">
                @if(($businessUnitModeEnabled ?? false) && ($businessUnits ?? collect())->isNotEmpty())
                    <div class="dropdown business-unit-switcher">
                        <button class="dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                id="businessUnitSwitcher">
                            <i class="bi {{ $activeBusinessUnit?->icon ?: 'bi-buildings' }}"></i>
                            <span class="business-unit-label">
                                <small>Business</small>
                                <span>{{ $activeBusinessUnit?->name ?? 'Select Business' }}</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                            <li class="px-2 py-1">
                                <div class="small text-muted fw-semibold">Switch business workspace</div>
                            </li>
                            @foreach($businessUnits as $businessUnit)
                                <li>
                                    <form action="{{ route('admin.business-units.switch', $businessUnit) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="dropdown-item business-unit-option {{ (int) ($activeBusinessUnit?->id ?? 0) === (int) $businessUnit->id ? 'active' : '' }}">
                                            <i class="bi {{ $businessUnit->icon ?: 'bi-building' }}"></i>
                                            <span class="flex-grow-1">{{ $businessUnit->name }}</span>
                                            @if((int) ($activeBusinessUnit?->id ?? 0) === (int) $businessUnit->id)
                                                <i class="bi bi-check2 ms-auto"></i>
                                            @endif
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                    <i class="bi bi-gear"></i> Business unit settings
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif

                <!-- Language Dropdown -->
                <div class="dropdown">
                    <button class="nav-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('assets/img/flags/' . app()->getLocale() . '.svg') }}"
                             alt="{{ app()->getLocale() }}"
                             style="width:22px;height:16px;border-radius:2px;object-fit:cover;">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                               href="{{ route('lang.switch', 'en') }}">
                                <img src="{{ asset('assets/img/flags/en.svg') }}"
                                     style="width:20px;height:14px;border-radius:2px;">
                                English
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === 'fa' ? 'active' : '' }}"
                               href="{{ route('lang.switch', 'fa') }}">
                                <img src="{{ asset('assets/img/flags/fa.svg') }}"
                                     style="width:20px;height:14px;border-radius:2px;">
                                فارسی
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === 'ps' ? 'active' : '' }}"
                               href="{{ route('lang.switch', 'ps') }}">
                                <img src="{{ asset('assets/img/flags/ps.svg') }}"
                                     style="width:20px;height:14px;border-radius:2px;">
                                پښتو
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="nav-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" id="notificationBell">
                        <i class="bi bi-bell"></i>
                        <span class="low-stock-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-custom dropdown-menu-end notification-dropdown" id="notificationDropdown">
                        <div class="dropdown-header">
                            <span><i class="bi bi-bell me-1"></i> {{ __('ui.notifications') }}</span>
                            <a class="view-all" href="{{ route('admin.notifications.low-stock') }}">
                                {{ __('ui.view_all') }} <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div id="notificationList">
                            <!-- Dynamic content -->
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <a class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                       href="#">
                        <div class="avatar">
                            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'A' }}
                        </div>
                        <div class="user-info">
                            <span class="name">{{ Auth::check() ? Auth::user()->name : __('ui.admin') }}</span>
                            <span class="role">{{ Auth::check() ? Auth::user()->account_type : __('ui.guest') }}</span>
                        </div>
                        <i class="bi bi-chevron-down chevron"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                        @if (Auth::check())
                            <li>
                                <a class="dropdown-item"
                                   href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}">
                                    <i class="bi bi-person"></i> {{ __('ui.my_profile') }}
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right"></i> {{ __('ui.logout') }}
                                </a>
                            </li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                  style="display:none;">
                                @csrf
                            </form>
                        @else
                            <li>
                                <a class="dropdown-item" href="{{ Route::has('login') ? route('login') : '#' }}">
                                    <i class="bi bi-box-arrow-in-right"></i> {{ __('ui.login') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <!-- ============================================================
    PAGE CONTENT
    ============================================================ -->


        <div class="app-content">
            @yield('content')
        </div>

    </div>
</div>

<!-- ============================================================
LOW STOCK MODAL (Shows on login)
============================================================ -->
<div class="modal fade low-stock-modal" id="lowStockModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Low Stock Alert
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" id="lowStockModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading low stock items...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('admin.notifications.low-stock') }}" class="btn btn-primary">
                    <i class="bi bi-eye me-1"></i> View All
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
SCRIPTS
============================================================ -->
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/toastify/toastify.min.js') }}"></script>

<script>
    // ─── Toastify Helper ───
    function showToast(message, type = 'success') {
        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
        };

        Toastify({
            text: message,
            duration: 3000,
            gravity: 'top',
            position: 'right',
            stopOnFocus: true,
            style: {
                background: colors[type] || colors.success,
                borderRadius: '8px',
                boxShadow: '0 8px 32px rgba(0,0,0,0.12)',
                padding: '12px 20px',
                fontFamily: 'Inter, sans-serif',
                fontWeight: '500'
            },
            close: true,
            className: 'toastify-custom'
        }).showToast();
    }

    $(document).ready(function() {

        // ─── Sidebar Toggle (Mobile) ───
        $('#sidebarToggle').on('click', function() {
            $('#sidebar').toggleClass('hidden');
            $('#sidebarOverlay').toggleClass('show');
        });

        $('#sidebarOverlay').on('click', function() {
            $('#sidebar').addClass('hidden');
            $('#sidebarOverlay').removeClass('show');
        });

        // ─── Submenu Toggle ───
        $('[data-toggle="submenu"]').on('click', function(e) {
            e.preventDefault();
            const parent = $(this).closest('.nav-item-custom');
            parent.toggleClass('open');
        });

        // ─── Auto-close sidebar on nav click (mobile) ───
        $('.sidebar-nav a:not([data-toggle="submenu"])').on('click', function() {
            if (window.innerWidth < 992) {
                $('#sidebar').addClass('hidden');
                $('#sidebarOverlay').removeClass('show');
            }
        });

        // ─── Toast notifications from session ───
        @if (session('success'))
        showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
        showToast('{{ session('error') }}', 'error');
        @endif

        @if (session('warning'))
        showToast('{{ session('warning') }}', 'warning');
        @endif

        @if (session('info'))
        showToast('{{ session('info') }}', 'info');
        @endif

        // ─── LOW STOCK NOTIFICATIONS ───

        // Fetch low stock notifications
        function fetchLowStockNotifications() {
            $.ajax({
                url: '{{ route('admin.notifications.low-stock-data') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateNotificationBadge(response.count);
                        renderNotificationDropdown(response.products);

                        // Show modal on login if there are low stock items
                        if (response.count > 0 && !sessionStorage.getItem('lowStockModalShown')) {
                            showLowStockModal(response.products);
                            sessionStorage.setItem('lowStockModalShown', 'true');
                        }
                    }
                },
                error: function() {
                    console.log('Failed to fetch low stock notifications');
                }
            });
        }

        // Update notification badge
        function updateNotificationBadge(count) {
            const badge = $('#notificationBadge');
            if (count > 0) {
                badge.text(count > 99 ? '99+' : count);
                badge.show();
            } else {
                badge.hide();
            }
        }

        // Render notification dropdown
        function renderNotificationDropdown(products) {
            const container = $('#notificationList');

            if (!products || products.length === 0) {
                container.html(`
                    <div class="notification-empty">
                        <i class="bi bi-check-circle"></i>
                        <div class="title">All Stock Levels Are Healthy</div>
                        <div class="sub-text">No low stock items to display</div>
                    </div>
                `);
                return;
            }

            let html = '';
            const itemsToShow = products.slice(0, 5);

            itemsToShow.forEach((product, index) => {
                const isCritical = product.current_stock <= 0;
                const iconClass = isCritical ? 'danger' : 'warning';
                const icon = isCritical ? 'bi-x-circle' : 'bi-exclamation-triangle';
                const stockText = isCritical ? 'Out of Stock' : `Low Stock (${product.current_stock} ${product.unit})`;
                const badgeClass = isCritical ? 'danger' : 'warning';

                html += `
                    <div class="notification-item" onclick="window.location.href='/admin/products/${product.id}/edit'">
                        <div class="notif-icon ${iconClass}">
                            <i class="bi ${icon}"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title">${product.name}</div>
                            <div class="notif-message">${product.category ? product.category.name : 'Uncategorized'}</div>
                            <div class="notif-meta">
                                <span class="stock-badge ${badgeClass}">${stockText}</span>
                                <span>${product.min_stock_alert > 0 ? `Min: ${product.min_stock_alert}` : ''}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            if (products.length > 5) {
                html += `
                    <div class="text-center py-2">
                        <small class="text-muted">+ ${products.length - 5} more items</small>
                    </div>
                `;
            }

            container.html(html);
        }

        // Show low stock modal
        function showLowStockModal(products) {
            const modalBody = $('#lowStockModalBody');

            if (!products || products.length === 0) {
                modalBody.html(`
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle" style="font-size: 3rem; color: #10b981;"></i>
                        <h5 class="mt-3">All Stock Levels Are Healthy</h5>
                        <p class="text-muted">No low stock items to display</p>
                    </div>
                `);
            } else {
                let html = '<div class="alert alert-warning mb-3">';
                html += `<i class="bi bi-exclamation-triangle me-2"></i>`;
                html += `<strong>${products.length}</strong> product(s) are running low on stock. Please review and restock soon.`;
                html += '</div>';

                // Group by critical and low
                const critical = products.filter(p => p.current_stock <= 0);
                const low = products.filter(p => p.current_stock > 0);

                if (critical.length > 0) {
                    html += `<h6 class="text-danger"><i class="bi bi-x-circle me-1"></i> Out of Stock (${critical.length})</h6>`;
                    critical.forEach(p => {
                        html += `
                            <div class="low-stock-item critical">
                                <div class="item-info">
                                    <span class="name">${p.name}</span>
                                    <span class="details">
                                        ${p.category ? p.category.name : 'Uncategorized'}
                                        <span class="category">${p.unit}</span>
                                    </span>
                                </div>
                                <div class="stock-status">
                                    <span class="badge danger">Out of Stock</span>
                                    <span class="stock-count" style="color: #ef4444;">0</span>
                                </div>
                            </div>
                        `;
                    });
                }

                if (low.length > 0) {
                    html += `<h6 class="text-warning mt-3"><i class="bi bi-exclamation-triangle me-1"></i> Low Stock (${low.length})</h6>`;
                    low.forEach(p => {
                        html += `
                            <div class="low-stock-item low">
                                <div class="item-info">
                                    <span class="name">${p.name}</span>
                                    <span class="details">
                                        ${p.category ? p.category.name : 'Uncategorized'}
                                        <span class="category">${p.unit}</span>
                                    </span>
                                </div>
                                <div class="stock-status">
                                    <span class="badge warning">${p.current_stock} left</span>
                                    <span class="stock-count">${p.current_stock}</span>
                                </div>
                            </div>
                        `;
                    });
                }

                modalBody.html(html);
            }

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('lowStockModal'));
            modal.show();
        }

        // ─── INITIALIZE LOW STOCK NOTIFICATIONS ───

        // Check if user just logged in (session flag)
        @if(session('just_logged_in'))
        sessionStorage.removeItem('lowStockModalShown');
        // Fetch and show notifications
        setTimeout(fetchLowStockNotifications, 500);
        @else
        // Normal fetch after page load
        setTimeout(fetchLowStockNotifications, 1000);
        @endif

        // Refresh notifications every 5 minutes
        setInterval(fetchLowStockNotifications, 300000);

        // Refresh when user returns to tab
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                fetchLowStockNotifications();
            }
        });

        // ─── Logout ping (safe, no undefined route) ───
        let unloading = false;
        window.addEventListener('beforeunload', function() {
            if (!unloading) {
                unloading = true;
                try {
                    navigator.sendBeacon('/logout-ping');
                } catch (e) {
                    // Silently fail if route doesn't exist
                }
            }
        });

        // ─── Online ping (safe) ───
        function sendPing() {
            try {
                fetch('{{ route('user.ping') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).catch(() => {});
            } catch (e) {
                // Silently fail
            }
        }

        setInterval(sendPing, 60000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) sendPing();
        });

    });
</script>
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };
</script>

@yield('js')

</body>

</html>
