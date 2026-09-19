<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountSubCategory;
use App\Models\AccountCategory;
use Yajra\DataTables\DataTables;

class AccountSubCategoriesController extends Controller
{
    public function index(Request $request)
{
    if ($request->ajax()) {
        $query = AccountSubCategory::with('accountCategory');

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('account_category_id', $request->category_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category', fn($row) => $row->accountCategory->name ?? '-')
            ->addColumn('actions', fn($row) => view('admin.account-sub-categories.partials.actions', compact('row'))->render())
            ->rawColumns(['actions'])
            ->make(true);
    }

    $categories = AccountCategory::orderBy('name')->get();
    return view('admin.account-sub-categories.index', compact('categories'));
}


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_category_id' => 'required|exists:account_categories,id'
        ]);

        AccountSubCategory::create($request->only('name', 'account_category_id'));
        return response()->json(['success' => true]);
    }


    public function show($id)
    {
        $subcategory = AccountSubCategory::findOrFail($id);
        return view('admin.account-sub-categories.show', compact('subcategory'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_category_id' => 'required|exists:account_categories,id'
        ]);

        $subcategory = AccountSubCategory::findOrFail($id);
        $subcategory->update($request->only('name', 'account_category_id'));
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $subcategory = AccountSubCategory::findOrFail($id);
        $subcategory->delete();
        return response()->json(['success' => true]);
    }
}
