<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AppSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.feedback:view categories')->only('categories');
        $this->middleware('permission.feedback:create categories')->only(['storeCategory']);
        $this->middleware('permission.feedback:update categories')->only(['updateCategory']);
        $this->middleware('permission.feedback:delete categories')->only(['deleteCategory']);
    }

    public function categories(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(Category::latest()->get());
        }
        return view('admin.app_settings.categories.index');
    }

    public function company(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(\App\Models\Setting::first() ?? []);
        }
        return view('admin.app_settings.company_settings.index');
    }

    public function units(Request $request)
    {
        if ($request->ajax()) {
            return response()->json([]);
        }
        return view('admin.app_settings.units.index');
    }

    public function invoiceTemplates(Request $request)
    {
        if ($request->ajax()) {
            return response()->json([]);
        }
        return view('admin.app_settings.invoice_templates.index');
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ps' => 'required|string|max:255',
            'name_dr' => 'required|string|max:255',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()]);
        }

        $data = $request->only(['name_en', 'name_ps', 'name_dr']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadAndMinimize($request->file('image'));
        }

        Category::create($data);
        return response()->json(['status' => 200, 'message' => 'Category added successfully!']);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ps' => 'required|string|max:255',
            'name_dr' => 'required|string|max:255',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()]);
        }

        $data = $request->only(['name_en', 'name_ps', 'name_dr']);

        if ($request->hasFile('image')) {
            if ($category->image && File::exists(public_path($category->image))) {
                File::delete(public_path($category->image));
            }
            $data['image'] = $this->uploadAndMinimize($request->file('image'));
        }

        $category->update($data);
        return response()->json(['status' => 200, 'message' => 'Category updated successfully!']);
    }

    public function deleteCategory($id)
    {
        $category = Category::find($id);
        if ($category) {
            if ($category->image && File::exists(public_path($category->image))) {
                File::delete(public_path($category->image));
            }
            $category->delete();
            return response()->json(['status' => 200, 'message' => 'Category deleted.']);
        }
        return response()->json(['status' => 404, 'message' => 'Not found.']);
    }

    private function uploadAndMinimize($file)
    {
        $path = public_path('uploads/categories');
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        $filename = time() . '_' . uniqid() . '.webp';
        $extension = $file->getClientOriginalExtension();

        $image = match ($extension) {
            'png' => imagecreatefrompng($file),
            'webp' => imagecreatefromwebp($file),
            default => imagecreatefromjpeg($file),
        };

        if ($image) {
            imagewebp($image, $path . '/' . $filename, 70);
            imagedestroy($image);
            return 'uploads/categories/' . $filename;
        }

        $file->move($path, $filename);
        return 'uploads/categories/' . $filename;
    }
}
