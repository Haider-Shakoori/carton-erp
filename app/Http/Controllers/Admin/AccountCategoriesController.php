<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountCategory;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class AccountCategoriesController extends Controller
{
    public function index()
    {
        $accountCategories = AccountCategory::all();
        return view('admin.account-categories.index', compact('accountCategories'));
    }

    public function data()
{
    return DataTables::of(AccountCategory::query())
        ->addIndexColumn()
        ->editColumn('bi_icon', function ($row) {
            return $row->bi_icon ?? '-';
        })
        ->editColumn('bi_icon_color', function ($row) {
            return $row->bi_icon_color ?? '#333';
        })
        ->addColumn('actions', function ($row) {
            return view('admin.account-categories.partials.actions', compact('row'))->render();
        })
        ->rawColumns(['actions'])
        ->make(true);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:account_categories,name',
            'code_prefix' => 'required|string',
            'bi_icon' => 'nullable|string',
            'bi_icon_color' => 'nullable|string',
        ]);

        AccountCategory::create($validated);
        return back()->with('success', 'Account category created successfully.');
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code_prefix' => 'required|string|max:10',
                'bi_icon' => 'nullable|string',
                'bi_icon_color' => 'nullable|string'
            ]);

            $category = AccountCategory::findOrFail($id);
            $category->update([
                'name' => $request->name,
                'code_prefix' => $request->code_prefix,
                'bi_icon' => $request->bi_icon,
                'bi_icon_color' => $request->bi_icon_color
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong.'], 500);
        }
    }


    public function destroy($id)
{
    $category = AccountCategory::findOrFail($id);
    $category->delete();

    return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
}

}
