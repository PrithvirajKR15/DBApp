<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  /**
   * Single post-login entry point: sends every user to the dashboard for their role.
   */
  public function index()
  {
    return redirect()->route(Auth::user()->role->home_route);
  }

  /**
   * Admin dashboard. Non-admins are redirected to their own dashboard.
   */
  public function admin()
  {
    $user = Auth::user();

    if (!$user->isAdmin()) {
      return redirect()->route($user->role->home_route);
    }

    return view('content.dashboard.dashboards-analytics');
  }

  public function storeAdmin()
  {
    return view('content.dashboard.store-dashboard');
  }

  public function user()
  {
    return view('content.dashboard.user-dashboard');
  }
}
