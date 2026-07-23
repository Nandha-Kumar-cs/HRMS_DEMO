<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleNotificationPref;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $modules     = config('magdyn.modules', []);
        $permissions = Permission::orderBy('module')->orderBy('feature')->get()->groupBy('module');
        return view('roles.create', compact('modules', 'permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'required|string|max:60|unique:roles,slug|alpha_dash',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create($data);
        $role->permissions()->sync($request->input('permissions', []));

        $this->syncNotifPrefs($role, $request);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created.");
    }

    public function edit(Role $role)
    {
        $modules     = config('magdyn.modules', []);
        $permissions = Permission::orderBy('module')->orderBy('feature')->get()->groupBy('module');
        $rolePerms   = $role->permissions->pluck('id')->toArray();
        $notifPrefs  = $role->notificationPrefs->keyBy(fn($p) => $p->module . '.' . $p->event);

        $notifEvents = $this->notifEvents();

        return view('roles.edit', compact('role', 'modules', 'permissions', 'rolePerms', 'notifPrefs', 'notifEvents'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($data);
        $role->permissions()->sync($request->input('permissions', []));

        $this->syncNotifPrefs($role, $request);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json(['error' => 'System roles cannot be deleted.'], 403);
        }
        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role deleted.']);
    }

    // ── Private helpers ──────────────────────────────────────────

    private function syncNotifPrefs(Role $role, Request $request): void
    {
        $notifEvents = $this->notifEvents();
        foreach ($notifEvents as $module => $events) {
            foreach ($events as $event => $label) {
                $key = "notif.{$module}.{$event}";
                RoleNotificationPref::updateOrCreate(
                    ['role_id' => $role->id, 'module' => $module, 'event' => $event],
                    [
                        'in_app' => $request->boolean("{$key}.in_app"),
                        'push'   => $request->boolean("{$key}.push"),
                        'email'  => $request->boolean("{$key}.email"),
                    ]
                );
            }
        }
    }

    private function notifEvents(): array
    {
        return [
            'payroll'  => ['payslip_generated' => 'Payslip Generated', 'payslip_regenerated' => 'Payslip Regenerated'],
            'leaves'   => ['leave_approved' => 'Leave Approved', 'leave_rejected' => 'Leave Rejected', 'leave_requested' => 'Leave Requested'],
            'loans'    => ['loan_approved' => 'Loan Approved', 'repayment_due' => 'Repayment Due'],
            'bonuses'  => ['bonus_approved' => 'Bonus Approved', 'bonus_rejected' => 'Bonus Rejected'],
            'training' => ['lesson_assigned' => 'Lesson Assigned', 'module_published' => 'Module Published'],
        ];
    }
}
