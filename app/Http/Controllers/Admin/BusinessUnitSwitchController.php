<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Support\Business\BusinessUnitContext;
use Illuminate\Http\RedirectResponse;

class BusinessUnitSwitchController extends Controller
{
    public function __invoke(
        BusinessUnit $businessUnit,
        BusinessUnitContext $context
    ): RedirectResponse {
        try {
            $context->switchTo($businessUnit);

            return back()->with('success', 'Switched to '.$businessUnit->name.'.');
        } catch (\RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
