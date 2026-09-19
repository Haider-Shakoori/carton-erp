<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PermissionsController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $permissions = Permission::all()->sortBy('name');
        return view('admin.permissions.index', compact('roles', 'permissions'));
    }

    public function data(Request $request)
    {
        $permissions = Permission::select(['id', 'name']);

        return DataTables::of($permissions)
            ->addIndexColumn()
            ->addColumn('group', function ($row) {
                $parts = explode(' ', $row->name);
                return isset($parts[1]) ? ucfirst($parts[1]) : 'General';
            })
            ->addColumn('actions', function ($row) {
                $actions = '';
                if (auth()->user()->can('update permissions')) {
                    $actions .= '<button class="btn btn-sm btn-outline-warning edit-permission-btn me-1"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasAddPermission">
                            <i class="bi bi-pencil"></i>
                        </button>';
                }
                if (auth()->user()->can('delete permissions')) {
                    $actions .= '<form method="POST" action="' . route('admin.permissions.destroy', $row) . '" class="d-inline" onsubmit="return confirm(\'Are you sure?\')">'
                        . csrf_field() . method_field('DELETE') .
                        '<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>';
                }
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name'
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created.');
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted.');
    }

    public function assign(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.permissions.index')->with('success', 'Permissions updated for ' . $role->name);
    }

    public function rolePermissionsJson($roleId)
{
    $role = Role::findOrFail($roleId);
    return response()->json([
        'permissions' => $role->permissions->pluck('name')
    ]);
}



}
