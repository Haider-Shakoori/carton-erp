@extends('layouts.admin.base')

@section('title', 'Low Stock Notifications')

@section('page_title', 'Low Stock Notifications')

@section('css')
    <style>
        .low-stock-page .stat-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .low-stock-page .stat-summary .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .low-stock-page .stat-summary .stat-card .number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-800);
        }

        .low-stock-page .stat-summary .stat-card .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--gray-400);
            font-weight: 700;
            letter-spacing: 0.06em;
        }

        .low-stock-page .stat-summary .stat-card.danger .number {
            color: #ef4444;
        }

        .low-stock-page .stat-summary .stat-card.warning .number {
            color: #f59e0b;
        }

        .low-stock-page .stat-summary .stat-card.success .number {
            color: #10b981;
        }

        .low-stock-page .page-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .low-stock-page .page-actions .btn-export {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            text-decoration: none;
        }

        .low-stock-page .page-actions .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .low-stock-page .page-actions .btn-export i {
            font-size: 1rem;
        }

        .low-stock-item-full {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .low-stock-item-full:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--gray-300);
        }

        .low-stock-item-full .item-info .name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .low-stock-item-full .item-info .details {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .low-stock-item-full .item-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .low-stock-item-full.critical {
            border-left: 4px solid #ef4444;
        }

        .low-stock-item-full.low {
            border-left: 4px solid #f59e0b;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            font-weight: 700;
            color: var(--gray-700);
        }

        .empty-state p {
            color: var(--gray-500);
        }

        /* Toast notification for export */
        .toast-export-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }
    </style>
@endsection

@section('content')
    <div class="low-stock-page">
        <!-- Page Actions -->
        <div class="page-actions">
            <a href="{{ route('admin.notifications.low-stock.export') }}" class="btn-export" id="exportBtn">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                Export to CSV
            </a>
        </div>

        <!-- Summary Stats -->
        <div class="stat-summary" id="summaryStats">
            <div class="stat-card danger">
                <div class="number" id="criticalCount">0</div>
                <div class="label">Out of Stock</div>
            </div>
            <div class="stat-card warning">
                <div class="number" id="lowCount">0</div>
                <div class="label">Low Stock</div>
            </div>
            <div class="stat-card success">
                <div class="number" id="healthyCount">0</div>
                <div class="label">Healthy Stock</div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div id="lowStockList">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{{ __('ui.loading') }}</span>
                </div>
                <p class="mt-2 text-muted">Loading low stock items...</p>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            fetchLowStockData();

            // Export button click handler
            $('#exportBtn').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');

                // Show loading state
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Exporting...');
                btn.prop('disabled', true);

                // Check if there are items to export
                const itemsCount = parseInt($('#criticalCount').text()) + parseInt($('#lowCount').text());

                if (itemsCount === 0) {
                    showToast('No low stock items to export.', 'warning');
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                    return;
                }

                // Trigger download
                window.location.href = url;

                // Reset button after delay
                setTimeout(function() {
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                    showToast('Export completed successfully!', 'success');
                }, 2000);
            });

            function fetchLowStockData() {
                $.ajax({
                    url: '{{ route('admin.notifications.low-stock-data') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderLowStockItems(response.products);
                            updateSummaryStats(response.products);
                        }
                    },
                    error: function() {
                        $('#lowStockList').html(`
                            <div class="empty-state">
                                <i class="bi bi-exclamation-triangle" style="color: #ef4444;"></i>
                                <h4>Error Loading Data</h4>
                                <p>Failed to load low stock items. Please try again.</p>
                            </div>
                        `);
                    }
                });
            }

            function renderLowStockItems(products) {
                const container = $('#lowStockList');

                if (!products || products.length === 0) {
                    container.html(`
                        <div class="empty-state">
                            <i class="bi bi-check-circle"></i>
                            <h4>All Stock Levels Are Healthy</h4>
                            <p>No products are currently low on stock. Great job!</p>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-primary mt-3">
                                <i class="bi bi-box me-1"></i> View All Products
                            </a>
                        </div>
                    `);
                    return;
                }

                // Sort: critical first, then low
                const sorted = [...products].sort((a, b) => {
                    if (a.current_stock <= 0 && b.current_stock > 0) return -1;
                    if (a.current_stock > 0 && b.current_stock <= 0) return 1;
                    return a.current_stock - b.current_stock;
                });

                let html = '';
                sorted.forEach(product => {
                    const isCritical = product.current_stock <= 0;
                    const statusClass = isCritical ? 'critical' : 'low';
                    const statusText = isCritical ? 'Out of Stock' : `Low Stock (${product.current_stock} ${product.unit})`;
                    const badgeClass = isCritical ? 'danger' : 'warning';
                    const icon = isCritical ? 'bi-x-circle' : 'bi-exclamation-triangle';
                    const iconColor = isCritical ? '#ef4444' : '#f59e0b';

                    html += `
                        <div class="low-stock-item-full ${statusClass}">
                            <div class="item-info">
                                <div class="name">
                                    <i class="bi ${icon} me-2" style="color: ${iconColor};"></i>
                                    ${product.name}
                                    <span class="badge-cat ms-2">${product.category ? product.category.name : 'Uncategorized'}</span>
                                </div>
                                <div class="details">
                                    Unit: ${product.unit} |
                                    Min Alert: ${product.min_stock_alert > 0 ? product.min_stock_alert : 'Not set'}
                                </div>
                            </div>
                            <div class="item-actions">
                                <span class="stock-badge ${badgeClass}">${statusText}</span>
                                <!-- Edit button removed -->
                            </div>
                        </div>
                    `;
                });

                container.html(html);
            }

            function updateSummaryStats(products) {
                const critical = products.filter(p => p.current_stock <= 0).length;
                const low = products.filter(p => p.current_stock > 0).length;
                const total = products.length;
                const healthy = total - critical - low;

                $('#criticalCount').text(critical);
                $('#lowCount').text(low);
                $('#healthyCount').text(healthy > 0 ? healthy : 0);
            }

            // Toast notification helper
            function showToast(message, type = 'success') {
                const colors = {
                    success: 'linear-gradient(135deg, #10b981, #059669)',
                    error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                    warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                    info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
                };

                if (typeof Toastify !== 'undefined') {
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
                } else {
                    alert(message);
                }
            }
        });
    </script>
@endsection
