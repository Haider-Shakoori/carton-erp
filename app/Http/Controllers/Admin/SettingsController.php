<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{

    public function index()
    {
        $setting = Setting::firstOrCreate([]);
        $currencies = Currency::all();
        $businessUnits = BusinessUnit::query()->active()->get();

        return view('admin.settings.index', compact('setting', 'currencies', 'businessUnits'));
    }

    public function update(Request $request)
    {
        $setting = Setting::firstOrCreate([]);
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
            'separate_business_units_enabled' => 'nullable|boolean',
            'default_business_unit_id' => 'nullable|required_if:separate_business_units_enabled,1|exists:business_units,id',
        ]);

        $data['separate_business_units_enabled'] = $request->boolean('separate_business_units_enabled');

        if (! $data['separate_business_units_enabled']) {
            session()->forget(\App\Support\Business\BusinessUnitContext::SESSION_KEY);
        }

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
