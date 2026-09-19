<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UsersController extends Controller
{

    public function index()
    {
        $permissions = Permission::all()->groupBy(function ($perm) {
            // Extract everything *after* the first word (the action)
            return trim(Str::after($perm->name, explode(' ', $perm->name, 2)[0]));
        });
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('permissions', 'roles'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
            'repeat_password' => 'required|same:password',
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array'
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->account_type = 'admin';
        $user->created_by = auth()->id();
        $user->password = Hash::make($request->password);
        $user->is_active = $request->has('is_active');
        $user->save();

        // Attach selected role
        $user->assignRole(Role::findOrFail($request->role_id));

        // Attach permissions (create if missing)
        if ($request->filled('permissions')) {
            foreach ($request->permissions as $permName) {
                $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName]);
                $user->givePermissionTo($permission);
            }
        }

        return redirect()->back()->with('success', 'User created successfully!');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('permissions')->findOrFail($id);
        $allPermissions = Permission::all(); // Make sure this is included
        return view('admin.users.show', compact('user', 'allPermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit($id)
    {
        $user = User::select('*')->findOrFail($id);
        return response()->json($user);
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->is_active = $request->has('is_active'); // important: checkbox fix

        $user->save();

        return response()->json(['message' => 'User updated']);
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the currently authenticated user
        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Check for protected roles
        $protectedRoles = ['admin', 'superadmin'];

        foreach ($protectedRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();

                if ($role && $role->users()->count() <= 1) {
                    return response()->json([
                        'message' => "Cannot delete the last {$roleName} user.",
                    ], 403);
                }
            }
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function updateStatus(string $id)
    {
        $user = User::find($id);

        if ($user) {
            $user->is_active = $user->is_active ? 0 : 1;
            $user->save();

            return redirect()->back()->with('success', 'User status updated successfully!');
        }

        return redirect()->back()->with('error', 'User not found!');
    }

    public function updatePassword(Request $request, string $id)
    {
        $user = User::find($id);
        if ($user) {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return redirect()->back()->with('success', 'Password updated successfully!');
        }

        return redirect()->back()->with('error', 'User not found!');
    }

    public function assignPermissions(Request $request, string $id)
    {
        $user = User::find($id);
        $permission = Permission::findOrFail($request->permission_id);

        if ($request->action === 'assign') {
            $user->givePermissionTo($permission);
            return response()->json(['message' => 'Permission assigned.']);
        }

        if ($request->action === 'revoke') {
            $user->revokePermissionTo($permission);
            return response()->json(['message' => 'Permission revoked.']);
        }

        return response()->json(['message' => 'Invalid action.'], 400);
    }


    public function updatePermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Permissions updated successfully.');
    }




    public function fetchUsers(Request $request)
    {
        $users = User::withCount('permissions');

        if ($request->filled('account_type')) {
            $users->where('account_type', $request->account_type);
        } else {
            $users->where('account_type', 'admin');
        }

        // Filter by search term (name/email)
        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $users->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Sort users by creation date
        $users->orderBy('created_at', 'asc');

        return DataTables::of($users)
            ->addColumn('name_with_profile', function ($user) {
                $initials = strtoupper(substr($user->name, 0, 1));
                $bgColor = $user->profile_bg ?? '#6c757d';
                $userUrl = route('admin.users.show', $user->id);
                return '
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background-color: ' . $bgColor . ';
                        color: white;
                        font-weight: bold;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font-size: 18px;">
                        ' . $initials . '
                    </div>
                    <div>
                        <a href="' . $userUrl . '" style="font-weight: bold; color: inherit;">
                            ' . e($user->name) . '
                        </a>
                    </div>
                </div>';
            })
            ->addColumn('actions', function ($user) {
                $url = route('admin.users.show', $user->id);
                return '
                <div class="d-flex align-items-center gap-2">
                    <a href="' . $url . '" class="btn btn-icon btn-outline-dribbble waves-effect">
                        <i class="bi bi-gear"></i>
                    </a>
                    <button class="btn btn-icon btn-outline-primary edit-user-btn" data-id="' . $user->id . '">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-icon btn-outline-danger delete-user-btn" data-id="' . $user->id . '">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>';
            })
            ->addColumn('account_type', function ($user) {
                if ($user->account_type === 'admin') {
                    return '<span class="badge rounded-pill bg-info"><i class="bi bi-person-workspace me-2"></i>Admin</span>';
                } else {
                    return '<span class="badge rounded-pill bg-primary"><i class="bi bi-building me-2"></i>Client</span>';
                }
            })
            ->addColumn('is_active', function ($user) {
                if ($user->is_active) {
                    return '<span class="badge rounded-pill bg-success"><i class="bi bi-check-circle-fill me-2"></i>Active</span>';
                } else {
                    return '<span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle-fill me-2"></i>Deactivated</span>';
                }
            })
            ->addColumn('created_at', function ($user) {
                return '<span title="' . $user->created_at->format('F j, Y') . '">' . $user->created_at->format('F j, Y') . '</span>';
            })
            ->addColumn('total_permissions', function ($user) {
                return '<span class="badge rounded-pill bg-danger">' . $user->permissions_count . ' Permissions</span>';
            })
            ->rawColumns(['name_with_profile', 'actions', 'account_type', 'is_active', 'created_at', 'total_permissions'])
            ->make(true);
    }
}
