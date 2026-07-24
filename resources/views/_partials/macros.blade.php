@php
$width = $width ?? '32';
@endphp
<div class="d-flex align-items-center justify-content-center bg-primary rounded text-white" style="width: {{ $width }}px; height: {{ $width }}px; border-radius: 8px !important;">
    <img src="{{ asset('assets/img/favicon/app_icon.png') }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
</div>