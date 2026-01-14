<?php

namespace App\Http\Controllers;

use \Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function pageLogin()
    {
        return inertia('Auth/Login');
    }

    public function login(Request $request)
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'owner') {
                return redirect()->intended('/dashboard');
            } elseif (Auth::user()->role === 'admin-grosir') {
                return redirect()->intended('/grosir');
            } elseif (Auth::user()->role === 'admin-ecer') {
                return redirect()->intended('/ecer');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
