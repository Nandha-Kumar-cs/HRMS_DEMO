<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAccessController extends Controller
{
    public function index()
    {
        $modules  = config('magdyn.modules', []);
        $settings = DB::table('module_mobile_access')->pluck('enabled', 'module');
        return view('settings.mobile-access', compact('modules', 'settings'));
    }

    public function update(Request $request)
    {
        $modules = array_keys(config('magdyn.modules', []));
        foreach ($modules as $module) {
            DB::table('module_mobile_access')->updateOrInsert(
                ['module' => $module],
                ['enabled' => $request->boolean("modules.{$module}"), 'updated_at' => now()]
            );
        }
        return redirect()->back()->with('success', 'Mobile access settings saved.');
    }

    /**
     * API: return which modules are mobile-enabled (used by PWA/SW).
     */
    public function api()
    {
        $settings = DB::table('module_mobile_access')->pluck('enabled', 'module');
        return response()->json($settings);
    }
}
