<!doctype html>
{{-- Admin shell: Tabler fluid vertical layout. application/grid/form extend this. --}}
@php
    $primaryColor = $settings->colors->primary_color ?? null;
    $primaryRgb = $primaryColor && preg_match('/^#([0-9a-f]{6})$/i', $primaryColor) ? implode(',', sscanf($primaryColor, '#%02x%02x%02x')) : null;
    $themeDefault = in_array($settings->theme->mode_default ?? 'auto', ['light', 'dark']) ? $settings->theme->mode_default : null;
@endphp
<html lang="{{ getLang() }}" dir="ltr" data-bs-navbar-position="vertical" data-bs-layout="fluid" @if ($themeDefault) data-bs-theme="{{ $themeDefault }}" @endif>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ pageTitle($__env) }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset($settings->media->favicon) }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toggle-master/bootstrap-toggle.min.css') }}" />
    @stack('styles_libs')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-panel.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/extra/css/custom.css') }}">
    @if ($primaryRgb)
        <style>
            :root {
                --tblr-primary: {{ $primaryColor }};
                --tblr-primary-rgb: {{ $primaryRgb }};
            }
        </style>
    @endif
    @stack('styles')
</head>

<body>
    <script src="{{ asset('assets/vendor/libs/tabler/js/tabler-theme.min.js') }}"></script>
    <div class="page">
        @include('backend.includes.sidebar')
        <div class="page-wrapper">
            @include('backend.includes.header')
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            @include('backend.includes.breadcrumb')
                            <h2 class="page-title">@yield('title')</h2>
                        </div>
                        @hasSection('page-actions')
                            <div class="col-auto ms-auto d-print-none">
                                <div class="btn-list">@yield('page-actions')</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>
            @include('backend.includes.footer')
        </div>
    </div>
    @include('backend.includes.scripts')
</body>

</html>
