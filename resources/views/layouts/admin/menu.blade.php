<aside id="layout-menu" class="layout-menu menu-vertical menu bg-white py-0">
    <div class="app-brand demo">
        <a href="{{ url('/') }}" class="app-brand-link gap-xl-0 gap-2">
            <span class="app-brand-logo demo me-1">
                <span class="text-primary">
                    <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" height="50">
                </span>
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">RAHE <br>ARYA</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="menu-toggle-icon d-xl-inline-block align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-3" style="margin-top: 15px;">

        {{-- ============================================================ --}}
        {{-- 1. DASHBOARD --}}
        {{-- ============================================================ --}}
        @can('view dashboard')
            <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-house-door"></i>
                    <div>{{ __('ui.dashboard') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 2. SALES SECTION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.sales') }}</span>
        </li>

        {{-- Sales Orders --}}
        @can('view sales')
            <li class="menu-item {{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">
                <a href="{{ route('admin.sales.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-cart-check-fill"></i>
                    <div>{{ __('ui.sales_orders') }}</div>
                </a>
            </li>
        @endcan

        {{-- Customers --}}
        @can('view customers')
            <li class="menu-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                <a href="{{ route('admin.customers.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-people-fill"></i>
                    <div>{{ __('ui.customers') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 3. PRODUCTION SECTION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.production') }}</span>
        </li>

        {{-- Bill of Materials (BOM) --}}
        @can('view bom')
            <li class="menu-item {{ request()->routeIs('admin.bom.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-list-ul"></i>
                    <div>{{ __('ui.bill_of_materials') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.bom.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.bom.index') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-table"></i>
                            <div>{{ __('ui.bom_list') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.bom.create') ? 'active' : '' }}">
                        <a href="{{ route('admin.bom.create') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-plus-circle"></i>
                            <div>{{ __('ui.create_bom') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.bom.calculator') ? 'active' : '' }}">
                        <a href="{{ route('admin.bom.calculator') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-calculator"></i>
                            <div>{{ __('ui.bom_calculator') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcan

        {{-- Production Planning --}}
        @can('view production planning')
            <li class="menu-item {{ request()->routeIs('admin.production-planning.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-calendar-check"></i>
                    <div>{{ __('ui.production_planning') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.production-planning.mrp') ? 'active' : '' }}">
                        <a href="{{ route('admin.production-planning.mrp') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-graph-up-arrow"></i>
                            <div>{{ __('ui.mrp_calculator') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.production-planning.schedule') ? 'active' : '' }}">
                        <a href="{{ route('admin.production-planning.schedule') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-clock"></i>
                            <div>{{ __('ui.production_schedule') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.production-planning.capacity') ? 'active' : '' }}">
                        <a href="{{ route('admin.production-planning.capacity') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-pie-chart"></i>
                            <div>{{ __('ui.capacity_planning') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcan

        {{-- Production Orders --}}
        @can('view production orders')
            <li class="menu-item {{ request()->routeIs('admin.production-orders.*') ? 'active' : '' }}">
                <a href="{{ route('admin.production-orders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-gear"></i>
                    <div>{{ __('ui.production_orders') }}</div>
                </a>
            </li>
        @endcan

        {{-- Work Orders --}}
        @can('view work orders')
            <li class="menu-item {{ request()->routeIs('admin.work-orders.*') ? 'active' : '' }}">
                <a href="{{ route('admin.work-orders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-tools"></i>
                    <div>{{ __('ui.work_orders') }}</div>
                </a>
            </li>
        @endcan

        {{-- Quality Control --}}
        @can('view quality control')
            <li class="menu-item {{ request()->routeIs('admin.quality.*') ? 'active' : '' }}">
                <a href="{{ route('admin.quality.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-check2-circle"></i>
                    <div>{{ __('ui.quality_control') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 4. INVENTORY & PURCHASING --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.inventory_purchasing') }}</span>
        </li>

        {{-- Purchase Orders --}}
        @can('view purchase orders')
            <li class="menu-item {{ request()->routeIs('admin.purchase-orders*') ? 'active' : '' }}">
                <a href="{{ route('admin.purchase-orders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-cart-plus-fill"></i>
                    <div>{{ __('ui.purchase_orders') }}</div>
                </a>
            </li>
        @endcan

        {{-- Stock / Inventory --}}
        @can('view stock')
            <li class="menu-item {{ request()->routeIs('admin.stock*') ? 'active' : '' }}">
                <a href="{{ route('admin.stock.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-box-seam-fill"></i>
                    <div>{{ __('ui.stock_inventory') }}</div>
                </a>
            </li>
        @endcan

        {{-- Products --}}
        @can('view products')
            <li class="menu-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <a href="{{ route('admin.products.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-box"></i>
                    <div>{{ __('ui.products') }}</div>
                </a>
            </li>
        @endcan

        {{-- Stock Movements --}}
        @can('view stock movements')
            <li class="menu-item {{ request()->routeIs('admin.stock-movements.*') ? 'active' : '' }}">
                <a href="{{ route('admin.stock-movements.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-arrow-left-right"></i>
                    <div>{{ __('ui.stock_movements') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 5. FINANCE SECTION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.finance') }}</span>
        </li>

        {{-- Transactions --}}
        @can('view transactions')
            <li class="menu-item {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                <a href="{{ route('admin.transactions.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-cash-stack"></i>
                    <div>{{ __('ui.transactions') }}</div>
                </a>
            </li>
        @endcan

        {{-- Expenses --}}
        @can('view expenses')
            <li class="menu-item {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                <a href="{{ route('admin.expenses.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-wallet"></i>
                    <div>{{ __('ui.expenses') }}</div>
                </a>
            </li>
        @endcan

        {{-- Cost Analysis --}}
        @can('view cost analysis')
            <li class="menu-item {{ request()->routeIs('admin.cost-analysis.*') ? 'active' : '' }}">
                <a href="{{ route('admin.cost-analysis.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-calculator-fill"></i>
                    <div>{{ __('ui.cost_analysis') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 6. PARTNERS SECTION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.partners') }}</span>
        </li>

        {{-- Suppliers --}}
        @can('view suppliers')
            <li class="menu-item {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                <a href="{{ route('admin.suppliers.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-truck"></i>
                    <div>{{ __('ui.suppliers') }}</div>
                </a>
            </li>
        @endcan

        {{-- Agents --}}
        @can('view agents')
            <li class="menu-item {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">
                <a href="{{ route('admin.agents.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-person-badge"></i>
                    <div>{{ __('ui.agents') }}</div>
                </a>
            </li>
        @endcan

        {{-- Sarafs --}}
        @can('view sarafs')
            <li class="menu-item {{ request()->routeIs('admin.sarafs.*') ? 'active' : '' }}">
                <a href="{{ route('admin.sarafs.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-person-lines-fill"></i>
                    <div>{{ __('ui.sarafan') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 7. REPORTS & ANALYTICS SECTION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.reports_analytics') }}</span>
        </li>

        {{-- Production Reports --}}
        @can('view production reports')
            <li class="menu-item {{ request()->routeIs('admin.reports.production.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.production.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-file-bar-graph"></i>
                    <div>{{ __('ui.production_reports') }}</div>
                </a>
            </li>
        @endcan

        {{-- Inventory Reports --}}
        @can('view inventory reports')
            <li class="menu-item {{ request()->routeIs('admin.reports.inventory.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.inventory.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-file-spreadsheet"></i>
                    <div>{{ __('ui.inventory_reports') }}</div>
                </a>
            </li>
        @endcan

        {{-- Financial Reports --}}
        @can('view financial reports')
            <li class="menu-item {{ request()->routeIs('admin.reports.financial.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.financial.index') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-file-earmark-text"></i>
                    <div>{{ __('ui.financial_reports') }}</div>
                </a>
            </li>
        @endcan

        {{-- ============================================================ --}}
        {{-- 8. ADMINISTRATION --}}
        {{-- ============================================================ --}}
        <li class="menu-header small text-uppercase fw-semibold text-muted px-3 mt-2 mb-1">
            <span class="menu-header-text">{{ __('ui.administration') }}</span>
        </li>

        {{-- User Management --}}
        @canany(['view users', 'view roles', 'view permissions'])
            <li
                class="menu-item {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-shield-lock-fill"></i>
                    <div>{{ __('ui.user_management') }}</div>
                </a>
                <ul class="menu-sub">
                    @can('view users')
                        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-person-badge"></i>
                                <div>{{ __('ui.users') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view roles')
                        <li class="menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-shield-lock"></i>
                                <div>{{ __('ui.roles') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view permissions')
                        <li class="menu-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.permissions.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-ui-checks-grid"></i>
                                <div>{{ __('ui.permissions') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Configuration --}}
        @can('view settings')
            <li
                class="menu-item {{ request()->routeIs('admin.currencies.*') || request()->routeIs('admin.account-categories.*') || request()->routeIs('admin.account-sub-categories.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.audit.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-sliders2"></i>
                    <div>{{ __('ui.configuration') }}</div>
                </a>
                <ul class="menu-sub">
                    @can('view currencies')
                        <li class="menu-item {{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.currencies.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-currency-dollar"></i>
                                <div>{{ __('ui.currencies') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view account categories')
                        <li class="menu-item {{ request()->routeIs('admin.account-categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.account-categories.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-grid-3x3-gap-fill"></i>
                                <div>{{ __('ui.account_categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view account sub categories')
                        <li class="menu-item {{ request()->routeIs('admin.account-sub-categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.account-sub-categories.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-grid-1x2-fill"></i>
                                <div>{{ __('ui.account_sub_categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view settings')
                        <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-gear"></i>
                                <div>{{ __('ui.system_settings') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view audit logs')
                        <li class="menu-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.audit.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-clock-history"></i>
                                <div>{{ __('ui.audit_logs') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan

        {{-- App Settings --}}
        @can('view app settings')
            <li
                class="menu-item {{ request()->routeIs('admin.app.company.*') || request()->routeIs('admin.app.categories.*') || request()->routeIs('admin.app.units.*') || request()->routeIs('admin.app.invoice-templates.*') || request()->routeIs('admin.app.bom-settings.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-gear-wide-connected"></i>
                    <div>{{ __('ui.app_settings') }}</div>
                </a>
                <ul class="menu-sub">
                    @can('view company settings')
                        <li class="menu-item {{ request()->routeIs('admin.app.company.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.app.company.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-building"></i>
                                <div>{{ __('ui.company_settings') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view categories')
                        <li class="menu-item {{ request()->routeIs('admin.app.categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.app.categories.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-tags"></i>
                                <div>{{ __('ui.categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view units')
                        <li class="menu-item {{ request()->routeIs('admin.app.units.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.app.units.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-rulers"></i>
                                <div>{{ __('ui.units') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view invoice templates')
                        <li class="menu-item {{ request()->routeIs('admin.app.invoice-templates.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.app.invoice-templates.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-receipt-cutoff"></i>
                                <div>{{ __('ui.invoice_templates') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view bom settings')
                        <li class="menu-item {{ request()->routeIs('admin.app.bom-settings.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.app.bom-settings.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-gear-fill"></i>
                                <div>{{ __('ui.bom_settings') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan

    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary rounded-1 p-2">
        <i class="ri ri-menu-line icon-base"></i>
        <i class="ri ri-arrow-right-s-line icon-base"></i>
    </a>
</div>
