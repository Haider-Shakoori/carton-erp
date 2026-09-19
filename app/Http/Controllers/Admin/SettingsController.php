<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{

    public function index()
    {
        $setting = Setting::firstOrCreate([]);
    $currencies = Currency::all(); // Assuming you already have this model

    return view('admin.settings.index', compact('setting', 'currencies'));
    }

    public function update(Request $request)
    {
        $setting = Setting::first();
        $data = $request->validate([
            'company_name' => 'nullable|string',
            'contact' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'default_language' => 'required|string',
            'currency' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'note_en' => 'nullable|string',
            'note_fa' => 'nullable|string',
            'note_ps' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = $logo->getClientOriginalName();

            // Delete existing logo
            if (isset($setting->logo) && $setting->logo != '' && file_exists(public_path('images/' . $setting->logo))) {
                unlink(public_path('images/' . $setting->logo));
            }

            $logo->move(public_path('images'), $filename);
            $data['logo'] = $filename;
        }

        $setting->update($data);
        return back()->with('success', 'Settings updated successfully!');
    }
}
