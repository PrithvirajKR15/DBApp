<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterBasic extends Controller
{
  public function index()
  {
    if (Auth::check()) {
      return redirect()->route('dashboard');
    }

    return view('content.authentications.auth-register-basic', [
      'roles' => Role::orderBy('id')->get(),
    ]);
  }

  public function register(Request $request)
  {
    $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
      'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
      'password' => ['required', 'string', 'min:6'],
      'role' => ['required', 'string', Rule::exists('roles', 'slug')],
    ]);

    $user = User::create([
      'name' => $request->name,
      'mobile' => $request->mobile,
      'email' => $request->email,
      'password' => Hash::make($request->password),
      'role_id' => Role::findBySlug($request->role)->id,
    ]);

    Auth::login($user);

    return redirect()->route('dashboard')->with('success', 'Registration successful!');
  }
}
