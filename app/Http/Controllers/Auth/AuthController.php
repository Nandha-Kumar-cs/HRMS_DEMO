<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);
        DB::enableQueryLog();
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            ActivityLog::record('login', 'Auth',
                'User logged in: ' . Auth::user()->name . ' (' . Auth::user()->email . ')',
                Auth::id(), Auth::user()->name
            );
            dd(DB::getQueryLog());
            // return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        ActivityLog::record('logout', 'Auth',
            'User logged out: ' . $user->name . ' (' . $user->email . ')',
            $user->id, $user->name
        );
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
