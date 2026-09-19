<?php
// app/Http/Controllers/Admin/WorkOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of work orders.
     */
    public function index(Request $request)
    {
        $query = WorkOrder::with(['productionOrder', 'assignedTo']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('production_order_id')) {
            $query->where('production_order_id', $request->production_order_id);
        }

        $workOrders = $query->latest()->paginate(15);

        $stats = [
            'total' => WorkOrder::count(),
            'pending' => WorkOrder::where('status', 'pending')->count(),
            'in_progress' => WorkOrder::where('status', 'in_progress')->count(),
            'completed' => WorkOrder::where('status', 'completed')->count(),
        ];

        $productionOrders = ProductionOrder::whereIn('status', ['pending', 'in_progress'])->get();

        return view('admin.work-orders.index', compact('workOrders', 'stats', 'productionOrders'));
    }

    /**
     * Show the form for creating a new work order.
     */
    public function create(Request $request)
    {
        $productionOrders = ProductionOrder::whereIn('status', ['pending', 'in_progress'])
            ->with('product')
            ->get();

        $users = User::where('is_active', true)->get();

        $selectedProductionOrder = null;
        if ($request->filled('production_order_id')) {
            $selectedProductionOrder = ProductionOrder::find($request->production_order_id);
        }

        return view('admin.work-orders.create', compact('productionOrders', 'users', 'selectedProductionOrder'));
    }

    /**
     * Store a newly created work order.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_order_id' => 'required|exists:production_orders,id',
            'operation_type' => 'required|in:printing,cutting,gluing,folding,lamination,quality_check',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_time' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $workOrder = WorkOrder::create([
                'production_order_id' => $request->production_order_id,
                'work_order_number' => $this->generateWorkOrderNumber(),
                'operation_type' => $request->operation_type,
                'assigned_to' => $request->assigned_to,
                'status' => 'pending',
                'estimated_time' => $request->estimated_time ?? 0,
                'notes' => $request->notes,
            ]);

            return redirect()->route('admin.work-orders.show', $workOrder)
                ->with('success', 'Work Order created successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create work order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified work order.
     */
    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['productionOrder', 'productionOrder.product', 'assignedTo']);
        return view('admin.work-orders.show', compact('workOrder'));
    }

    /**
     * Show the form for editing the specified work order.
     */
    public function edit(WorkOrder $workOrder)
    {
        $productionOrders = ProductionOrder::whereIn('status', ['pending', 'in_progress'])->get();
        $users = User::where('is_active', true)->get();

        return view('admin.work-orders.edit', compact('workOrder', 'productionOrders', 'users'));
    }

    /**
     * Update the specified work order.
     */
    public function update(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'operation_type' => 'required|in:printing,cutting,gluing,folding,lamination,quality_check',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_time' => 'nullable|numeric|min:0',
            'actual_time' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $workOrder->update([
                'operation_type' => $request->operation_type,
                'assigned_to' => $request->assigned_to,
                'estimated_time' => $request->estimated_time ?? 0,
                'actual_time' => $request->actual_time ?? 0,
                'notes' => $request->notes,
            ]);

            return redirect()->route('admin.work-orders.show', $workOrder)
                ->with('success', 'Work Order updated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update work order: ' . $e->getMessage());
        }
    }

    /**
     * Start a work order.
     */
    public function start(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'pending') {
            return back()->with('error', 'Only pending work orders can be started.');
        }

        $workOrder->status = 'in_progress';
        $workOrder->started_at = now();
        $workOrder->save();

        return redirect()->route('admin.work-orders.show', $workOrder)
            ->with('success', 'Work Order started!');
    }

    /**
     * Complete a work order.
     */
    public function complete(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress work orders can be completed.');
        }

        $request->validate([
            'actual_time' => 'required|numeric|min:0',
        ]);

        $workOrder->status = 'completed';
        $workOrder->actual_time = $request->actual_time;
        $workOrder->completed_at = now();
        $workOrder->save();

        // Check if all work orders for this production order are completed
        $productionOrder = $workOrder->productionOrder;
        $pendingWorkOrders = $productionOrder->workOrders()
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        if ($pendingWorkOrders === 0 && $productionOrder->status === 'in_progress') {
            // All work orders completed - production order can be completed
            // We'll let the user manually complete the production order
        }

        return redirect()->route('admin.work-orders.show', $workOrder)
            ->with('success', 'Work Order completed!');
    }

    /**
     * Delete a work order.
     */
    public function destroy(WorkOrder $workOrder)
    {
        if (!in_array($workOrder->status, ['pending'])) {
            return back()->with('error', 'Only pending work orders can be deleted.');
        }

        $workOrder->delete();

        return redirect()->route('admin.work-orders.index')
            ->with('success', 'Work Order deleted successfully!');
    }

    /**
     * Generate work order number.
     */
    private function generateWorkOrderNumber()
    {
        $year = date('Y');
        $month = date('m');
        $last = WorkOrder::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->work_order_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "WO-{$year}{$month}-{$newNumber}";
    }

    /**
     * DataTable for work orders.
     */
    public function data(Request $request)
    {
        $query = WorkOrder::with(['productionOrder', 'assignedTo']);

        if ($request->filled('production_order_id')) {
            $query->where('production_order_id', $request->production_order_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('work_order_number', function ($row) {
                return '<a href="' . route('admin.work-orders.show', $row) . '" class="fw-bold text-primary">' . e($row->work_order_number) . '</a>';
            })
            ->addColumn('production_order', function ($row) {
                return $row->productionOrder->order_number ?? 'N/A';
            })
            ->addColumn('operation_type', function ($row) {
                return $row->operation_label;
            })
            ->addColumn('assigned_to', function ($row) {
                return $row->assignedTo->name ?? 'Unassigned';
            })
            ->addColumn('status', function ($row) {
                $badges = [
                    'pending' => '<span class="badge bg-warning">Pending</span>',
                    'in_progress' => '<span class="badge bg-info">In Progress</span>',
                    'completed' => '<span class="badge bg-success">Completed</span>',
                ];
                return $badges[$row->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('actions', function ($row) {
                $actions = '
                    <div class="btn-group">
                        <a href="' . route('admin.work-orders.show', $row) . '" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="' . route('admin.work-orders.edit', $row) . '" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                ';

                if ($row->status === 'pending') {
                    $actions .= '
                        <form action="' . route('admin.work-orders.start', $row) . '" method="POST" class="d-inline">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm(\'Start this work order?\')">
                                <i class="bi bi-play-fill"></i>
                            </button>
                        </form>
                    ';
                }

                if ($row->status === 'pending') {
                    $actions .= '
                        <form action="' . route('admin.work-orders.destroy', $row) . '" method="POST" class="d-inline">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this work order?\')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    ';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['work_order_number', 'status', 'actions'])
            ->make(true);
    }
}
