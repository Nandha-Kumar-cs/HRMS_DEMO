<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select('id', 'name', 'email', 'role', 'created_at');
            return DataTables::of($users)
                ->addColumn('role_badge', function ($u) {
                    $colors = ['admin' => 'danger', 'manager' => 'primary', 'staff' => 'secondary'];
                    $color  = $colors[$u->role] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($u->role) . '</span>';
                })
                ->addColumn('action', function ($u) {
                    $edit   = '<a href="' . route('users.edit', $u) . '" class="btn btn-sm btn-outline-primary me-1"><i class="fa fa-pen-to-square"></i></a>';
                    $delete = auth()->id() === $u->id ? '' :
                        '<button class="btn btn-sm btn-outline-danger btn-delete-user" data-url="' . route('users.destroy', $u) . '"><i class="fa fa-trash"></i></button>';
                    return $edit . $delete;
                })
                ->rawColumns(['role_badge', 'action'])
                ->make(true);
        }
        return view('users.index');
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:admin,manager,staff',
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'     => 'required|in:admin,manager,staff',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $updateData = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account.']);
        }
        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }
}
