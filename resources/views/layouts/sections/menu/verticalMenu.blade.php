@php
  use Illuminate\Support\Facades\Route;
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="icon-base bx bx-chevron-left icon-sm d-flex align-items-center justify-content-center"></i>
    </a>
  </div>

  <div class="menu-divider mt-0"></div>
  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1 flex-grow-1" style="overflow-y: auto;">
    @foreach ($menuData->menu as $menu)
      {{-- adding active and open class if child is active --}}

      {{-- menu headers --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
      @else
        {{-- active menu method --}}
        @php
          $activeClass = null;
          $currentRouteName = Route::currentRouteName();

          if ($currentRouteName === $menu->slug) {
              $activeClass = 'active';
          } elseif (isset($menu->submenu)) {
              if (gettype($menu->slug) === 'array') {
                  foreach ($menu->slug as $slug) {
                      if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
                          $activeClass = 'active open';
                      }
                  }
              } else {
                  if (str_contains($currentRouteName, $menu->slug) and strpos($currentRouteName, $menu->slug) === 0) {
                      $activeClass = 'active open';
                  }
              }
          }
        @endphp

        {{-- main menu --}}
        <li class="menu-item {{ $activeClass }}">
          <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
            class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
            @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
            @isset($menu->badge)
              <div class="badge rounded-pill bg-{{ $menu->badge[0] }} text-uppercase ms-auto">{{ $menu->badge[1] }}</div>
            @endisset
          </a>

          {{-- submenu --}}
          @isset($menu->submenu)
            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
          @endisset
        </li>
      @endif
    @endforeach
  </ul>

  <!-- Bottom User Profile Card -->
  <div class="p-3 border-top mt-auto d-flex align-items-center justify-content-between"
    style="background: rgba(0, 0, 0, 0.015);">
    <div class="d-flex align-items-center">
      <div class="avatar avatar-online me-2" style="width: 36px; height: 36px;">
        <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-36 h-auto rounded-circle">
      </div>
      <div class="lh-sm">
        <div class="fw-semibold text-truncate text-body" style="font-size: 0.85rem; max-width: 120px;">
          {{ Auth::check() ? Auth::user()->name : 'Admin User' }}</div>
        <div class="text-muted text-truncate" style="font-size: 0.75rem; max-width: 120px;">
          {{ Auth::check() ? Auth::user()->email : 'admin@deliverease.com' }}</div>
      </div>
    </div>
    <a href="{{ route('auth-logout') }}" class="text-muted p-1 hover-primary" title="Logout">
      <i class="icon-base bx bx-log-out fs-4" style="transform: scaleX(-1);"></i>
    </a>
  </div>

</aside>
