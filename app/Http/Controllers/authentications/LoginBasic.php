<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class LoginBasic extends Controller
{
  public function index()
  {
    if (Auth::check()) {
      return redirect()->route('dashboard-analytics');
    }
    return view('content.authentications.auth-login-basic');
  }

  public function login(Request $request)
  {
    $request->validate([
      'login' => ['required', 'string'],
      'password' => ['required', 'string'],
    ]);

    $loginValue = $request->input('login');
    $password = $request->input('password');
    $remember = $request->filled('remember');

    // Check if input is email or mobile number
    $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

    $credentials = [
      $field => $loginValue,
      'password' => $password,
    ];

    if (Auth::attempt($credentials, $remember)) {
      $request->session()->regenerate();

      return redirect()->intended(route('dashboard-analytics'))
        ->with('success', 'Logged in successfully!');
    }

    return back()->withErrors([
      'login' => 'The provided credentials do not match our records.',
    ])->onlyInput('login');
  }

  public function logout(Request $request)
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('auth-login-basic')->with('success', 'Logged out successfully!');
  }
}
